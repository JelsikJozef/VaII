<?php

declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$rootPath = dirname(__DIR__);

require_once $rootPath . '/Framework/Core/App.php';
$routerPath = $rootPath . '/Framework/Routing/Router.php';
require_once is_file($routerPath) ? $routerPath : $rootPath . '/Framework/Core/Router.php';

spl_autoload_register(static function (string $class) use ($rootPath): void {
    $normalized = $rootPath . '/' . str_replace('\\', '/', ltrim($class, '\\')) . '.php';
    if (is_file($normalized)) {
        require_once $normalized;
        return;
    }

    $directories = [
        $rootPath . '/App/Controllers',
        $rootPath . '/App/Models',
        $rootPath . '/Framework/Core',
        $rootPath . '/Framework/Routing',
        $rootPath . '/Framework/Database',
    ];

    $shortName = basename(str_replace('\\', '/', $class)) . '.php';
    foreach ($directories as $directory) {
        $path = $directory . '/' . $shortName;
        if (is_file($path)) {
            require_once $path;
            break;
        }
    }
});

$routesFile = $rootPath . '/App/config/routes.php';
if (is_file($routesFile)) {
    require $routesFile;
}

$app = new Framework\Core\App();
$app->run();

