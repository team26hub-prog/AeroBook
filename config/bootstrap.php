<?php
declare(strict_types=1);

$envFile = BASE_PATH . '/.env';
if (is_file($envFile) && is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key === '' || getenv($key) !== false) {
            continue;
        }
        if (strlen($value) >= 2 && (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))) {
            $value = substr($value, 1, -1);
        }
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

$env = static fn (string $key, ?string $default = null): ?string => (($value = getenv($key)) !== false) ? $value : $default;

$GLOBALS['config'] = [
    'app' => [
        'name' => $env('APP_NAME', 'AeroBook'),
        'environment' => $env('APP_ENV', 'development'),
        'debug' => filter_var($env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
        'base_url' => rtrim((string) $env('APP_URL', 'http://localhost'), '/'),
    ],
    'database' => [
        'host' => $env('DB_HOST', '127.0.0.1'),
        'name' => $env('DB_DATABASE', 'aerobook'),
        'username' => $env('DB_USERNAME', ''),
        'password' => $env('DB_PASSWORD', ''),
        'port' => $env('DB_PORT', '3306'),
    ],
];

\App\Core\Session::start();
