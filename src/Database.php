<?php

declare(strict_types=1);

/**
 * Thin singleton wrapper around PDO so every file shares one connection.
 */
final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $configPath = __DIR__ . '/../config/database.php';
            if (!file_exists($configPath)) {
                throw new RuntimeException(
                    'Missing config/database.php — copy config/database.php.example and fill it in.'
                );
            }

            $config = require $configPath;

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['charset']
            );

            self::$instance = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$instance;
    }
}
