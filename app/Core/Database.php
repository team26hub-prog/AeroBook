<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = $GLOBALS['config']['database'] ?? [];
        foreach (['host', 'name', 'username', 'port'] as $key) {
            if (!isset($config[$key]) || $config[$key] === '') {
                throw new RuntimeException('Missing database configuration: ' . $key);
            }
        }

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['name']);
        self::$connection = new PDO($dsn, $config['username'], $config['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // Keep MySQL's NOW()/CURRENT_TIMESTAMP values aligned with PHP's configured application timezone.
        $offset = (new \DateTimeImmutable('now'))->format('P');
        self::$connection->exec('SET time_zone = ' . self::$connection->quote($offset));

        return self::$connection;
    }
}
