<?php
declare(strict_types=1);

// Uses only disposable aerobook_test_* databases and an ignored installer copy.
require __DIR__ . '/support/Harness.php';
require BASE_PATH . '/config/installer.php';
$environment = new TestEnvironment();
session_write_close();
$suite = new Suite();
$root = $environment->runtime . '/installer';
foreach (['', '/config', '/database', '/storage', '/public'] as $directory) {
    mkdir($root . $directory, 0700);
}
foreach (['setup.php', 'public/setup.php', 'config/installer.php', 'database/schema.sql'] as $file) {
    copy(BASE_PATH . '/' . $file, $root . '/' . $file);
}
$token = bin2hex(random_bytes(32));
file_put_contents($root . '/storage/.setup-token', hash('sha256', $token));
$freshName = 'aerobook_test_' . bin2hex(random_bytes(6));
$databaseUser = 'ab_test_' . bin2hex(random_bytes(6));
$databasePassword = bin2hex(random_bytes(24));
$server = $environment->server;
$createdDatabase = false;
$createdUser = false;
$process = null;
register_shutdown_function(static function () use (&$process, &$createdDatabase, &$createdUser, $server, $freshName, $databaseUser): void {
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    if ($createdUser && preg_match('/\Aab_test_[a-f0-9]{12}\z/', $databaseUser)) {
        $server->exec('DROP USER ' . $server->quote($databaseUser) . "@'localhost'");
    }
    if ($createdDatabase && preg_match('/\Aaerobook_test_[a-f0-9]{12}\z/', $freshName)) {
        $server->exec('DROP DATABASE `' . $freshName . '`');
    }
});
$server->exec('CREATE DATABASE `' . $freshName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$createdDatabase = true;
$server->exec('CREATE USER ' . $server->quote($databaseUser) . "@'localhost' IDENTIFIED BY " . $server->quote($databasePassword));
$createdUser = true;
$server->exec('GRANT ALL PRIVILEGES ON `' . $freshName . '`.* TO ' . $server->quote($databaseUser) . "@'localhost'");

$input = ['url' => 'https://aerobook.example', 'host' => $environment->env['DB_HOST'] ?? '127.0.0.1',
    'port' => $environment->env['DB_PORT'] ?? '3306', 'database' => $freshName,
    'username' => $databaseUser, 'password' => $databasePassword,
    'name' => 'Installer Admin', 'email' => 'ADMIN@example.test',
    'admin_password' => 'Unique installer password 123!', 'confirm_password' => 'Unique installer password 123!'];
$statements = AeroBookInstaller::schema(BASE_PATH . '/database/schema.sql');
$suite->add('setup: rejects DSN injection and dotenv line injection', static function () use ($input): void {
    foreach (['host' => 'localhost;dbname=other', 'database' => 'db;other', 'password' => "secret\nAPP_DEBUG=true", 'port' => '0'] as $key => $value) {
        rejects(fn () => AeroBookInstaller::input(array_replace($input, [$key => $value])), DomainException::class);
    }
});
$suite->add('setup: validates administrator and HTTPS origin', static function () use ($input): void {
    foreach (['url' => ['http://aerobook.example', 'https://user:pass@aerobook.example', 'https://aerobook.example/path', 'https://aerobook.example/?x=1'],
        'email' => ['invalid'], 'admin_password' => ['short', str_repeat('p', 73)], 'confirm_password' => ['different']] as $key => $badValues) {
        foreach ($badValues as $value) {
            rejects(fn () => AeroBookInstaller::input(array_replace($input, [$key => $value])), DomainException::class);
        }
    }
});
$suite->add('setup: preserves special characters through the actual dotenv parser', static function () use ($root, $input): void {
    $password = ' spaces #=$"quote\'back\\slash ';
    $values = AeroBookInstaller::input(array_replace($input, ['password' => $password]));
    $roundTrip = $root . '/round-trip';
    mkdir($roundTrip);
    copy(BASE_PATH . '/config/bootstrap.php', $roundTrip . '/bootstrap.php');
    file_put_contents($roundTrip . '/.env', AeroBookInstaller::environment($values));
    // Bootstrap starts its application session; use a stub in this isolated child.
    file_put_contents($roundTrip . '/check.php', '<?php namespace App\Core; class Session {static function start(){}} define("BASE_PATH",__DIR__); require __DIR__."/bootstrap.php"; echo hash("sha256",$GLOBALS["config"]["database"]["password"]);');
    $pipes = [];
    $child = proc_open([PHP_BINARY, $roundTrip . '/check.php'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fclose($pipes[0]);
    $result = stream_get_contents($pipes[1]);
    fclose($pipes[1]); fclose($pipes[2]);
    equal(proc_close($child), 0);
    equal($result, hash('sha256', $password));
});
$suite->add('setup: refuses existing data without changing it', static function () use ($environment, $input, $statements): void {
    $environment->reset();
    $before = $environment->db->query('SELECT * FROM users ORDER BY id')->fetchAll();
    rejects(fn () => AeroBookInstaller::initialize($environment->db, $statements, $input), DomainException::class);
    equal($environment->db->query('SELECT * FROM users ORDER BY id')->fetchAll(), $before);
    equal((int) $environment->db->query('SELECT COUNT(*) FROM flights')->fetchColumn(), 3);
});
$suite->add('setup: configuration writes never overwrite existing files', static function () use ($root): void {
    $path = $root . '/exclusive-test';
    AeroBookInstaller::exclusiveFile($path, 'original');
    set_error_handler(static fn (): bool => true);
    try { rejects(fn () => AeroBookInstaller::exclusiveFile($path, 'replacement')); }
    finally { restore_error_handler(); }
    equal(file_get_contents($path), 'original');
});
$suite->add('setup: partial DDL failure retains tables and refuses automatic retry', static function () use ($server, $environment, $input, $statements): void {
    $name = 'aerobook_test_' . bin2hex(random_bytes(6));
    $server->exec('CREATE DATABASE `' . $name . '`');
    try {
        $db = new PDO('mysql:host=' . ($environment->env['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($environment->env['DB_PORT'] ?? '3306') . ';dbname=' . $name,
            $environment->env['DB_USERNAME'] ?? '', $environment->env['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        rejects(fn () => AeroBookInstaller::initialize($db, [$statements[0], 'CREATE TABLE invalid SQL'], $input), PDOException::class);
        equal((int) $db->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()')->fetchColumn(), 1);
        rejects(fn () => AeroBookInstaller::initialize($db, $statements, $input), DomainException::class);
        equal((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(), 0);
    } finally {
        expect((bool) preg_match('/\Aaerobook_test_[a-f0-9]{12}\z/', $name));
        $server->exec('DROP DATABASE `' . $name . '`');
    }
});

// Simulate trusted server HTTPS termination in the fixture only; no proxy-header trust in production.
file_put_contents($root . '/router.php', '<?php if(!isset($_GET["http"])) $_SERVER["HTTPS"]="on"; require __DIR__.(str_starts_with($_SERVER["REQUEST_URI"],"/public/")?"/public/setup.php":"/setup.php");');
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
expect(is_resource($socket));
$address = stream_socket_get_name($socket, false);
fclose($socket);
$pipes = [];
$process = proc_open([PHP_BINARY, '-S', $address, '-t', $root, $root . '/router.php'],
    [0 => ['pipe', 'r'], 1 => ['file', $root . '/server.log', 'a'], 2 => ['file', $root . '/server.log', 'a']], $pipes, $root);
expect(is_resource($process));
fclose($pipes[0]);
$cookie = '';
$request = static function (string $path = '/setup.php', ?array $post = null) use ($address, &$cookie): array {
    $headers = "Connection: close\r\n";
    if ($cookie !== '') { $headers .= 'Cookie: ' . $cookie . "\r\n"; }
    if ($post !== null) { $headers .= "Content-Type: application/x-www-form-urlencoded\r\n"; }
    $context = stream_context_create(['http' => ['method' => $post === null ? 'GET' : 'POST', 'header' => $headers,
        'content' => $post === null ? '' : http_build_query($post), 'ignore_errors' => true, 'timeout' => 15]]);
    $body = file_get_contents('http://' . $address . $path, false, $context);
    $responseHeaders = $http_response_header ?? [];
    foreach ($responseHeaders as $header) {
        if (preg_match('/^Set-Cookie: (AeroBookSetup=[^;]*)/', $header, $match)) { $cookie = $match[1]; }
    }
    preg_match('/HTTP\/\S+ (\d+)/', $responseHeaders[0] ?? '', $status);
    return ['status' => (int) ($status[1] ?? 0), 'body' => (string) $body, 'headers' => implode("\n", $responseHeaders)];
};
for ($attempt = 0; $attempt < 50; $attempt++) {
    $ready = @stream_socket_client('tcp://' . $address, $errno, $error, .1);
    if ($ready) { fclose($ready); break; }
    usleep(100000);
}
$csrf = static function (array $response): string {
    preg_match('/name="csrf" value="([a-f0-9]{64})"/', $response['body'], $match);
    expect(isset($match[1]), 'Missing CSRF token');
    return $match[1];
};
$suite->add('setup HTTP: HTTPS, activation and CSRF gates deny access', static function () use ($request, $root, $token): void {
    equal($request('/setup.php?http=1')['status'], 403);
    rename($root . '/storage/.setup-token', $root . '/storage/.setup-token-disabled');
    equal($request()['status'], 403);
    rename($root . '/storage/.setup-token-disabled', $root . '/storage/.setup-token');
    equal($request('/setup.php', ['token' => $token, 'csrf' => 'invalid'])['status'], 403);
    expect(!file_exists($root . '/.env'));
});
$suite->add('setup HTTP: secrets hidden and token required', static function () use ($request, $csrf, $input): void {
    $page = $request();
    expect(str_contains($page['headers'], 'Cache-Control: no-store'));
    expect(str_contains($page['headers'], 'HttpOnly') || str_contains($page['headers'], 'Content-Security-Policy:'));
    expect(!str_contains($page['body'], 'name="database"'));
    $bad = $request('/setup.php', ['csrf' => $csrf($page), 'token' => str_repeat('0', 64)]);
    equal($bad['status'], 403);
    expect(!str_contains($bad['body'], $input['password']));
});
$suite->add('setup HTTP: connection failures hide credentials and allow safe retry', static function () use ($request, $csrf, $input, $token, $root, &$cookie): void {
    $cookie = '';
    $page = $request();
    $unlocked = $request('/setup.php', ['csrf' => $csrf($page), 'token' => $token]);
    $badPassword = 'secret-invalid-database-password';
    $result = $request('/setup.php', array_merge($input, ['password' => $badPassword, 'csrf' => $csrf($unlocked), 'action' => 'install']));
    equal($result['status'], 500);
    foreach ([$badPassword, 'SQLSTATE', 'Stack trace', $input['admin_password']] as $secret) {
        expect(!str_contains($result['body'], $secret));
    }
    expect(!file_exists($root . '/.env'));
    expect(!file_exists($root . '/storage/.installed'));
    expect(is_file($root . '/storage/.setup-token'));
});
$suite->add('setup HTTP: parallel installation is refused before database writes', static function () use ($request, $csrf, $input, $root): void {
    $page = $request();
    $mutex = fopen($root . '/storage/.setup-mutex', 'c');
    expect(is_resource($mutex) && flock($mutex, LOCK_EX));
    try {
        $result = $request('/setup.php', array_merge($input, ['csrf' => $csrf($page), 'action' => 'install']));
        equal($result['status'], 422);
        expect(str_contains($result['body'], 'Another installation is running'));
        expect(!file_exists($root . '/.env'));
        $db = AeroBookInstaller::connection($input);
        equal((int) $db->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()')->fetchColumn(), 0);
    } finally {
        flock($mutex, LOCK_UN); fclose($mutex);
    }
});
$suite->add('setup HTTP: authenticated installer completes and permanently locks', static function () use ($request, $csrf, $input, $token, $root, &$cookie): void {
    $cookie = ''; // New visitor avoids the intentional delay on the failed-auth session.
    $page = $request();
    $unlocked = $request('/setup.php', ['csrf' => $csrf($page), 'token' => $token]);
    equal($unlocked['status'], 200);
    expect(str_contains($unlocked['body'], 'name="database"'));
    $result = $request('/setup.php', array_merge($input, ['csrf' => $csrf($unlocked), 'action' => 'install']));
    equal($result['status'], 200);
    expect(str_contains($result['body'], 'Installation complete'));
    expect(!str_contains($result['body'], $input['password']));
    expect(!str_contains($result['body'], $input['admin_password']));
    expect(is_file($root . '/storage/.installed'));
    expect(!file_exists($root . '/storage/.setup-token'));
    expect(str_contains(file_get_contents($root . '/.env'), 'APP_DEBUG="false"'));
    $db = AeroBookInstaller::connection($input);
    equal((int) $db->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()')->fetchColumn(), 10);
    $admin = $db->query('SELECT * FROM users')->fetch();
    equal($admin['role'], 'admin'); equal($admin['status'], 'active'); equal($admin['email'], 'admin@example.test');
    expect(password_verify($input['admin_password'], $admin['password_hash']));
    equal((int) $db->query('SELECT COUNT(*) FROM flights')->fetchColumn(), 115);
    equal((int) $db->query('SELECT COUNT(*) FROM airports')->fetchColumn(), 4);
    equal((int) $db->query('SELECT COUNT(*) FROM seats')->fetchColumn(), 6900);
    equal($request()['status'], 410);
    equal($request('/public/setup.php')['status'], 410);
    // Redeployed code or a newly generated token cannot bypass either durable guard.
    file_put_contents($root . '/storage/.setup-token', hash('sha256', $token));
    equal($request()['status'], 410);
    rename($root . '/.env', $root . '/.env.saved');
    equal($request()['status'], 410);
    rename($root . '/.env.saved', $root . '/.env');
    unlink($root . '/storage/.installed');
    equal($request()['status'], 410);
    equal((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn(), 1);
});
exit($suite->run() > 0 ? 1 : 0);
