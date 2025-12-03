<?php

declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$rootPath = dirname(__DIR__);

// Load the framework class loader so all classes (including Framework\Http\Request) are autoloaded correctly
require_once $rootPath . '/Framework/ClassLoader.php';

$app = new Framework\Core\App();
$app->run();
