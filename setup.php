<?php
declare(strict_types=1);

// No bootstrap, application session, credentials, or shell commands are loaded here.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
$nonce = base64_encode(random_bytes(18));
header("Content-Security-Policy: default-src 'none'; style-src 'nonce-$nonce'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");

$root = __DIR__;
$storage = $root . '/storage';
$tokenFile = $storage . '/.setup-token';
$installed = $storage . '/.installed';
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$stop = static function (int $status, string $message) use ($escape): never {
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>AeroBook setup</title><body><h1>AeroBook setup</h1><p>' . $escape($message) . '</p></body></html>';
    exit;
};

// Presence of .env is also a permanent guard, even if the success marker is removed.
if (file_exists($installed) || file_exists($root . '/.env')) {
    $stop(410, 'Installation is disabled. This application already has configuration or an installation lock.');
}
if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    $stop(405, 'Method not allowed.');
}
// Do not trust arbitrary X-Forwarded-Proto headers supplied by visitors.
if (empty($_SERVER['HTTPS']) || strtolower((string) $_SERVER['HTTPS']) === 'off') {
    $stop(403, 'Setup requires HTTPS. Enable SSL for this domain and open its HTTPS setup URL.');
}
if (!is_file($tokenFile) || !is_readable($tokenFile) || is_link($tokenFile)) {
    $stop(403, 'Setup is disabled. Follow the cPanel installation guide to enable it from your hosting account.');
}
$tokenHash = trim((string) file_get_contents($tokenFile, false, null, 0, 128));
if (!preg_match('/\A[a-f0-9]{64}\z/', $tokenHash)) {
    $stop(403, 'Setup activation is invalid. Create a new installation token from your hosting account.');
}
if (!extension_loaded('session')) {
    $stop(503, 'Enable the PHP session extension in cPanel before installing.');
}
require $root . '/config/installer.php';

try {
    $sessionPath = $storage . '/setup-sessions';
    if (!is_dir($sessionPath) && !mkdir($sessionPath, 0700)) {
        throw new RuntimeException('Session storage unavailable.');
    }
    session_name('AeroBookSetup');
    session_save_path($sessionPath);
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']);
    if (!session_start(['use_strict_mode' => true, 'use_only_cookies' => true])) {
        throw new RuntimeException('Session unavailable.');
    }
} catch (Throwable $exception) {
    $stop(503, 'Private session storage is unavailable. Check storage permissions in cPanel.');
}
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$authorized = isset($_SESSION['authorized_until'], $_SESSION['token_hash'])
    && $_SESSION['authorized_until'] > time() && hash_equals($tokenHash, (string) $_SESSION['token_hash']);
$error = '';
$success = false;
$values = [];
$checks = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $stop(403, 'Your setup session expired. Reload this page and try again.');
    }
    if (!$authorized) {
        $token = $_POST['token'] ?? '';
        // Only a 256-bit hex token is accepted. Persisted per-session delay limits repeated attempts.
        $retryAt = (int) ($_SESSION['retry_at'] ?? 0);
        if ($retryAt > time()) {
            http_response_code(429);
            $error = 'Please wait a few seconds before trying again.';
        } elseif (!is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/', $token)
            || !hash_equals($tokenHash, hash('sha256', $token))) {
            $_SESSION['retry_at'] = time() + 5;
            http_response_code(403);
            $error = 'The installation token is invalid.';
        } else {
            session_regenerate_id(true);
            $_SESSION['authorized_until'] = time() + 900;
            $_SESSION['token_hash'] = $tokenHash;
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            unset($_SESSION['retry_at']);
            $authorized = true;
        }
        unset($token, $_POST['token']);
    } elseif (($_POST['action'] ?? '') === 'install') {
        $mutex = null;
        try {
            $values = AeroBookInstaller::input($_POST);
            $checks = AeroBookInstaller::requirements($root);
            if (in_array(false, $checks, true)) {
                throw new DomainException('Resolve the failed requirements below before installing.');
            }
            $statements = AeroBookInstaller::schema($root . '/database/schema.sql');
            $previousMask = umask(0077);
            try {
                $mutex = fopen($storage . '/.setup-mutex', 'c');
            } finally {
                umask($previousMask);
            }
            if ($mutex === false || !flock($mutex, LOCK_EX | LOCK_NB)) {
                throw new DomainException('Another installation is running. Wait for it to finish.');
            }
            // Recheck persistent guards after acquiring the lock, including token revocation.
            clearstatcache();
            if (file_exists($installed) || file_exists($root . '/.env')) {
                $stop(410, 'Installation is already disabled.');
            }
            if (!is_file($tokenFile) || !hash_equals($tokenHash, trim((string) file_get_contents($tokenFile)))) {
                throw new DomainException('Setup activation has been revoked. Reload this page.');
            }
            // Check write permissions before making any database changes.
            $probe = $root . '/.setup-write-' . bin2hex(random_bytes(12));
            AeroBookInstaller::exclusiveFile($probe, '');
            if (!unlink($probe)) {
                throw new RuntimeException('Cannot remove write probe.');
            }
            if (!is_dir($storage . '/payment-proofs') && !mkdir($storage . '/payment-proofs', 0700)) {
                throw new RuntimeException('Cannot create upload storage.');
            }
            $db = AeroBookInstaller::connection($values);
            AeroBookInstaller::initialize($db, $statements, $values);
            AeroBookInstaller::exclusiveFile($root . '/.env', AeroBookInstaller::environment($values));
            AeroBookInstaller::exclusiveFile($installed, 'Installed ' . gmdate('c') . "\n");
            // Both persistent guards survive every deployment; token removal is extra protection.
            @unlink($tokenFile);
            $_SESSION = [];
            session_destroy();
            setcookie('AeroBookSetup', '', ['expires' => time() - 3600, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']);
            $success = true;
        } catch (DomainException $exception) {
            $error = $exception->getMessage(); // Only our fixed, non-sensitive validation messages.
            http_response_code(422);
        } catch (Throwable $exception) {
            // Never show/log exception text, DSNs, SQL, passwords, or stack traces.
            http_response_code(500);
            $error = 'Installation could not finish. Check database access and file permissions in cPanel. No existing tables were deleted. If new tables or .env were created, follow the recovery instructions in CPANEL-DEPLOYMENT.txt before retrying.';
            error_log('AeroBook setup failed; inspect database access, schema state and filesystem permissions from cPanel.');
        } finally {
            if (is_resource($mutex)) {
                flock($mutex, LOCK_UN);
                fclose($mutex);
            }
            unset($db, $_POST['password'], $_POST['admin_password'], $_POST['confirm_password']);
        }
    }
}
if ($authorized && !$success) {
    $checks = AeroBookInstaller::requirements($root);
}
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install AeroBook</title>
<style nonce="<?= $escape($nonce) ?>">
*{box-sizing:border-box}body{margin:0;background:#edf5f7;color:#152e49;font:16px/1.5 system-ui,sans-serif}main{max-width:780px;margin:36px auto;padding:28px;background:white;border:1px solid #d7e5eb;border-top:4px solid #00A8CC;border-radius:16px}h1{margin:0 0 8px}h2{font-size:19px;margin:24px 0 12px}p{margin:8px 0 16px;color:#466078}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}label{display:block;font-weight:600;font-size:14px}input{display:block;width:100%;padding:11px;margin-top:5px;border:1px solid #b6cbd4;border-radius:8px;font:inherit}input:focus{outline:2px solid #00A8CC;outline-offset:2px}button,a{color:#08776d}button{margin-top:24px;border:0;border-radius:8px;padding:12px 22px;background:#0D9488;color:white;font:inherit;font-weight:650;cursor:pointer}button:disabled{opacity:.5;cursor:not-allowed}.notice{padding:14px;border-radius:8px;background:#fff2ed;color:#9b3216}.ok{background:#e6f6f1;color:#12634c}.check{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px solid #e8eff3}.fail{color:#a33421}.pass{color:#08776d}small{display:block;color:#466078;margin-top:4px}@media(max-width:600px){main{margin:14px;padding:20px}.grid{grid-template-columns:1fr}}
</style>
</head>
<body><main>
<h1>Install AeroBook</h1>
<?php if ($success): ?>
<p class="notice ok" role="status">Installation complete. Setup is permanently locked.</p>
<p>Your database schema, initial airline, airports, flight schedules, seats, and administrator account are ready. Sign in to review and manage schedules from the admin panel.</p>
<p><a href="<?= $escape($values['url'] . '/admin/login') ?>">Sign in as administrator</a></p>
<p>Delete both setup.php entry points from the hosting account as an extra precaution. Keep the installation lock and .env.</p>
<?php else: ?>
<p><?= $authorized ? 'Connect an empty database and create your administrator account.' : 'Enter the one-time token generated from your hosting account. Never share this token.' ?></p>
<?php if ($error !== ''): ?><p class="notice" role="alert"><?= $escape($error) ?></p><?php endif; ?>
<?php if ($authorized): ?>
<h2>Server requirements</h2>
<?php foreach ($checks as $label => $passed): ?><div class="check"><span><?= $escape($label) ?></span><strong class="<?= $passed ? 'pass' : 'fail' ?>"><?= $passed ? 'Ready' : 'Required' ?></strong></div><?php endforeach; ?>
<p><small>Database version is checked after connecting. MySQL 8.0.16+ or MariaDB 10.6+ is required. Apache rewrite rules and HTTPS must be enabled by your host.</small></p>
<?php endif; ?>
<form method="post" autocomplete="off">
<input type="hidden" name="csrf" value="<?= $escape($_SESSION['csrf']) ?>">
<?php if (!$authorized): ?>
<label>Installation token<input type="password" name="token" required minlength="64" maxlength="64" autocomplete="off"></label>
<button type="submit">Unlock setup</button>
<?php else: ?>
<input type="hidden" name="action" value="install">
<h2>Application</h2>
<label>HTTPS website URL<input type="url" name="url" placeholder="https://your-domain.com" value="<?= $escape($values['url'] ?? '') ?>" required></label>
<h2>MySQL connection</h2>
<p><small>Create an empty database and assign its user in cPanel first. Include the cPanel account prefix in both names. Installation will refuse any database that already contains tables or views.</small></p>
<div class="grid">
<label>Database host<input name="host" value="<?= $escape($values['host'] ?? 'localhost') ?>" required maxlength="253"></label>
<label>Database port<input type="number" name="port" value="<?= $escape($values['port'] ?? '3306') ?>" min="1" max="65535" required></label>
<label>Database name<input name="database" value="<?= $escape($values['database'] ?? '') ?>" required maxlength="64"></label>
<label>Database user<input name="username" value="<?= $escape($values['username'] ?? '') ?>" required maxlength="80"></label>
<label>Database password<input type="password" name="password" required maxlength="256" autocomplete="new-password"></label>
</div>
<h2>Administrator</h2>
<div class="grid">
<label>Full name<input name="name" value="<?= $escape($values['name'] ?? '') ?>" required maxlength="150"></label>
<label>Email address<input type="email" name="email" value="<?= $escape($values['email'] ?? '') ?>" required maxlength="254"></label>
<label>Password<input type="password" name="admin_password" minlength="12" maxlength="72" required autocomplete="new-password"></label>
<label>Confirm password<input type="password" name="confirm_password" minlength="12" maxlength="72" required autocomplete="new-password"></label>
</div>
<small>Use a unique password containing 12 to 72 bytes. This installation session expires after 15 minutes.</small>
<button type="submit" <?= in_array(false, $checks, true) ? 'disabled' : '' ?>>Install AeroBook</button>
<?php endif; ?>
</form>
<?php endif; ?>
</main></body></html>
