<?php

namespace App;

use PDO;

/**
 * Simple application-wide database access helper.
 *
 * This class is responsible for:
 *  - Bootstrapping environment variables from the `.env` file.
 *  - Creating a single shared PDO instance configured using {@see Configuration}.
 *  - Returning this PDO instance to callers via {@see Database::getConnection()}.
 *
 * Usage:
 *
 *  $pdo = Database::getConnection();
 *  $stmt = $pdo->query('SELECT * FROM transactions');
 */
class Database
{
    /**
     * Lazily-initialized shared PDO connection.
     *
     * Once created, the same PDO instance is reused for the lifetime
     * of the PHP process (simple singleton-like behavior).
     */
    private static ?PDO $pdo = null;

    /**
     * Get a configured PDO connection to the application's database.
     *
     * - On first call, this method:
     *   - Loads environment variables from `.env` via {@see bootEnv()}.
     *   - Reads DB configuration values from {@see Configuration}.
     *   - Creates a PDO instance with sane defaults (exceptions enabled,
     *     associative fetch mode, native prepared statements).
     * - On subsequent calls, the already created PDO instance is returned.
     *
     * @return PDO Shared PDO connection instance.
     *
     * @throws \PDOException If creating the PDO instance fails.
     *                       (Note: in the current implementation this is
     *                       caught and `die()` is called instead.)
     */
    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            self::bootEnv();

            $host = Configuration::getDbHost();
            $port = Configuration::getDbPort();
            $dbName = Configuration::getDbName();
            $user = Configuration::getDbUser();
            $pass = Configuration::getDbPass();
            $charset = Configuration::getDbCharset();

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

    /**
     * Load environment variables from the project's `.env` file.
     *
     * Behavior:
     *  - Looks for `.env` in the project root (one level above `App/`).
     *  - Ignores empty lines and lines starting with `#`.
     *  - Parses lines in the form `KEY=VALUE`.
     *  - Trims surrounding whitespace and quotes from values.
     *  - Does not overwrite environment variables that are already set.
     *  - Sets values into `putenv()`, `$_ENV` and `$_SERVER`.
     *
     * This method is idempotent and cheap to call multiple times.
     */
    public static function bootEnv(): void
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
            $value = trim($parts[1], " \t\"'" );

            if (getenv($key) !== false && getenv($key) !== '') {
                continue;
            }

            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}