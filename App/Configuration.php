<?php
// AI-GENERATED: Switch to DB authenticator and login URL (GitHub Copilot / ChatGPT), 2026-01-18

namespace App;

use App\Auth\DbAuthenticator;
use Framework\Auth\DummyAuthenticator;
use Framework\Core\ErrorHandler;
use Framework\DB\DefaultConventions;


/**
 * Class Configuration
 *
 * This class holds the main configuration settings for the application, including application name, framework version,
 * database connection settings, authentication, error handling, and other runtime configurations.
 */
class Configuration
{
    /**
     * Application name.
     */
    public const APP_NAME = 'ESN UNIZA Portal';

    /**
     * Version of the framework.
     */
    public const FW_VERSION = '3.0.6';

    /**
     * Database connection settings for ESN DB.
     */
    public const DB_HOST = '127.0.0.1';
    public const DB_PORT = 3306;
    public const DB_NAME = 'app';
    public const DB_USER = 'app';
    public const DB_PASS = 'secret';
    public const DB_CHARSET = 'utf8mb4';

    /**
     * URL for the login page. Users will be redirected here if authentication is required for an action.
     */
    public const LOGIN_URL = '?c=auth&a=loginForm';

    /**
     * Prefix for the default view files located in the App/Views directory. The view file format is
     * <ROOT_LAYOUT>.layout.view.php.
     */
    public const ROOT_LAYOUT = 'root';

    /**
     * Flag to determine whether to display all SQL queries after the application output for debugging purposes.
     */
    public const SHOW_SQL_QUERY = false;

    /**
     * Class name for the database naming conventions implementation. This should adhere to the IDbConvention interface.
     * The default implementation is DefaultConventions.
     */
    public const DB_CONVENTIONS_CLASS = DefaultConventions::class;

    /**
     * Flag to enable or disable detailed exception stack traces. This feature is intended for development purposes
     * only.
     */
    public const SHOW_EXCEPTION_DETAILS = true;

    /**
     * Class name for the authenticator. This class must implement the IAuthenticator interface. Comment out this line
     * if authentication is not required in the application.
     */
    public const AUTH_CLASS = DbAuthenticator::class;

    /**
     * Class name for the error handler. This class must implement the IHandleError interface.
     */
    public const ERROR_HANDLER_CLASS = ErrorHandler::class;

    /**
     * Directory for file uploads on the filesystem (uses OS-specific directory separators).
     * Example on Linux:  public/uploads/
     * Example on Windows: public\uploads\
     */
    public const UPLOAD_DIR = 'uploads' . DIRECTORY_SEPARATOR;

    /**
     * Public URL path prefix for uploaded files (always uses forward slashes for web URLs).
     * Example: /uploads/
     */
    public const UPLOAD_URL = '/uploads/';

    // Session key for storing the user identity
    public const IDENTITY_SESSION_KEY = 'fw.session.user.identity';

    public static function getDbHost(): string
    {
        return self::envString('APP_DB_HOST', self::DB_HOST);
    }

    public static function getDbPort(): int
    {
        return self::envInt('APP_DB_PORT', self::DB_PORT);
    }

    public static function getDbName(): string
    {
        return self::envString('APP_DB_NAME', self::DB_NAME);
    }

    public static function getDbUser(): string
    {
        return self::envString('APP_DB_USER', self::DB_USER);
    }

    public static function getDbPass(): string
    {
        return self::envString('APP_DB_PASS', self::DB_PASS);
    }

    public static function getDbCharset(): string
    {
        return self::envString('APP_DB_CHARSET', self::DB_CHARSET);
    }

    private static function envString(string $key, string $default): string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            Database::bootEnv();
            $value = getenv($key);
        }

        return ($value === false || $value === '') ? $default : $value;
    }

    private static function envInt(string $key, int $default): int
    {
        return (int)self::envString($key, (string)$default);
    }
}
