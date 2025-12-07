<?php

namespace App;

use App\Configuration;
use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            if (!getenv('APP_DB_HOST')) {
                self::bootEnv();
            }

            $host = getenv('APP_DB_HOST') ?: Configuration::DB_HOST;
            $port = (int)(getenv('APP_DB_PORT') ?: Configuration::DB_PORT);
            $dbName = getenv('APP_DB_NAME') ?: Configuration::DB_NAME;
            $user = getenv('APP_DB_USER') ?: Configuration::DB_USER;
            $pass = getenv('APP_DB_PASS') ?: Configuration::DB_PASS;
            $charset = getenv('APP_DB_CHARSET') ?: 'utf8mb4';

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $host,
                $port,
                $dbName,
                $charset
            );

            try {
                self::$pdo = new PDO(
                    $dsn,
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_STRINGIFY_FETCHES => false,
                    ]
                );
            } catch (PDOException $e) {
                die('Database connection error: ' . $e->getMessage());
            }
        }

        return self::$pdo;
    }

    private static function bootEnv(): void
    {
        $root = dirname(__DIR__);
        $envPath = $root . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1], " \t\"\'");
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}