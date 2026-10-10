<?php
declare(strict_types=1);

// Installer-only helpers. This file never loads application configuration or sessions.
final class AeroBookInstaller
{
    public static function requirements(string $root): array
    {
        $checks = ['PHP 8.1 or newer' => PHP_VERSION_ID >= 80100];
        foreach (['pdo', 'pdo_mysql', 'session', 'fileinfo'] as $extension) {
            $checks['PHP extension: ' . $extension] = extension_loaded($extension);
        }
        $checks['Application directory writable (for .env)'] = is_writable($root);
        $checks['Private storage directory writable'] = is_dir($root . '/storage') && is_writable($root . '/storage');
        $checks['Database schema readable'] = is_readable($root . '/database/schema.sql');
        return $checks;
    }

    public static function input(array $post): array
    {
        $values = [];
        foreach (['url', 'host', 'port', 'database', 'username', 'password', 'name', 'email', 'admin_password', 'confirm_password'] as $key) {
            if (!isset($post[$key]) || !is_string($post[$key]) || preg_match('/[\x00-\x1F\x7F]/', $post[$key])) {
                throw new DomainException('Complete every field using single-line values.');
            }
            $values[$key] = in_array($key, ['password', 'admin_password', 'confirm_password'], true) ? $post[$key] : trim($post[$key]);
        }
        $url = parse_url($values['url']);
        if (!filter_var($values['url'], FILTER_VALIDATE_URL) || !is_array($url)
            || ($url['scheme'] ?? '') !== 'https' || empty($url['host'])
            || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])
            || !in_array($url['path'] ?? '', ['', '/'], true)) {
            throw new DomainException('Use your HTTPS domain URL, without a path, credentials, query, or fragment.');
        }
        $values['url'] = rtrim($values['url'], '/');
        if (!preg_match('/\A[a-zA-Z0-9.-]{1,253}\z/', $values['host'])
            || !ctype_digit($values['port']) || (int) $values['port'] < 1 || (int) $values['port'] > 65535
            || !preg_match('/\A[a-zA-Z0-9_-]{1,64}\z/', $values['database'])
            || !preg_match('/\A[a-zA-Z0-9_-]{1,80}\z/', $values['username'])
            || $values['password'] === '' || strlen($values['password']) > 256) {
            throw new DomainException('Check the database host, port, full cPanel database/user names, and password.');
        }
        if ($values['name'] === '' || strlen($values['name']) > 150
            || strlen($values['email']) > 254 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Enter a valid administrator name and email address.');
        }
        if (strlen($values['admin_password']) < 12 || strlen($values['admin_password']) > 72
            || $values['admin_password'] !== $values['confirm_password']) {
            throw new DomainException('Administrator passwords must match and contain 12 to 72 bytes.');
        }
        return $values;
    }

    public static function schema(string $file): array
    {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new DomainException('The database schema could not be read. Deploy the complete application.');
        }
        $sql = preg_replace('/^[\t ]*--[^\r\n]*(?:\r?\n|$)/m', '', $sql);
        $statements = array_values(array_filter(array_map('trim', explode(';', $sql)), static fn ($s) => $s !== ''));
        $expected = ['users', 'airlines', 'airports', 'flights', 'bookings', 'passengers', 'seats', 'booking_seats', 'payments', 'e_tickets'];
        if (count($statements) !== count($expected) + 6) {
            throw new DomainException('The database schema is not the expected AeroBook schema.');
        }
        foreach ($expected as $index => $table) {
            if (!preg_match('/\ACREATE TABLE IF NOT EXISTS ' . $table . '\s*\(/', $statements[$index])) {
                throw new DomainException('The database schema is not the expected AeroBook schema.');
            }
        }
        // Accept only the fixed, local reference-data section after the ten tables.
        $dataPatterns = ['/\ASTART TRANSACTION\z/', '/\AINSERT INTO airlines\s*\(/', '/\AINSERT INTO airports\s*\(/', '/\AINSERT INTO flights\s*\(/', '/\AINSERT INTO seats\s*\(/', '/\ACOMMIT\z/'];
        foreach ($dataPatterns as $index => $pattern) {
            if (!preg_match($pattern, $statements[count($expected) + $index])) {
                throw new DomainException('The database schema is not the expected AeroBook schema.');
            }
        }
        return $statements;
    }

    public static function connection(array $values): PDO
    {
        return new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $values['host'], (int) $values['port'], $values['database']),
            $values['username'], $values['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10,
            ]);
    }

    public static function initialize(PDO $db, array $statements, array $values): void
    {
        $version = (string) $db->query('SELECT VERSION()')->fetchColumn();
        $maria = stripos($version, 'MariaDB') !== false;
        // MariaDB may prepend its legacy 5.5.5 compatibility version.
        $version = preg_replace('/^5\.5\.5-/', '', $version);
        preg_match('/^\d+\.\d+\.\d+/', $version, $match);
        if (!$match || version_compare($match[0], $maria ? '10.6.0' : '8.0.16', '<')) {
            throw new DomainException('Use MySQL 8.0.16 or newer, or MariaDB 10.6 or newer.');
        }
        if ((int) $db->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn() !== 0) {
            throw new DomainException('This database already contains tables or views. Nothing was changed. Use a new empty database for first-time installation; migrate an existing AeroBook installation separately.');
        }
        // MySQL DDL implicitly commits. Never DROP, truncate, replace, or retry over existing tables.
        foreach ($statements as $statement) {
            $db->exec($statement);
        }
        $insert = $db->prepare("INSERT INTO users (full_name, email, password_hash, role, status) VALUES (?, ?, ?, 'admin', 'active')");
        $insert->execute([$values['name'], strtolower($values['email']), password_hash($values['admin_password'], PASSWORD_DEFAULT)]);
    }

    public static function environment(array $values): string
    {
        $env = ['APP_NAME' => 'AeroBook', 'APP_ENV' => 'production', 'APP_DEBUG' => 'false',
            'APP_URL' => $values['url'], 'APP_TIMEZONE' => 'Asia/Karachi', 'DB_HOST' => $values['host'],
            'DB_PORT' => $values['port'], 'DB_DATABASE' => $values['database'],
            'DB_USERNAME' => $values['username'], 'DB_PASSWORD' => $values['password']];
        $content = '';
        foreach ($env as $key => $value) {
            // Compatible with config/bootstrap.php: outer quotes are stripped; inner characters stay literal.
            $content .= $key . '="' . $value . '"' . "\n";
        }
        return $content;
    }

    public static function exclusiveFile(string $path, string $content): void
    {
        $previousMask = umask(0077);
        try {
            $handle = fopen($path, 'x'); // Never overwrite an existing configuration or lock.
            if ($handle === false) {
                throw new RuntimeException('Cannot create private installation file.');
            }
            try {
                if (fwrite($handle, $content) !== strlen($content) || !fflush($handle)) {
                    throw new RuntimeException('Cannot complete private installation file.');
                }
                if (function_exists('fsync') && !fsync($handle)) {
                    throw new RuntimeException('Cannot flush private installation file.');
                }
            } finally {
                fclose($handle);
            }
        } finally {
            umask($previousMask);
        }
    }
}
