<?php

use Slim\App;

define('APP_DIR', __DIR__.'/../app');

require __DIR__.'/../vendor/autoload.php';

$app = new App([
    'settings' => require APP_DIR.'/settings.php',
]);

$container = $app->getContainer();

// Set up dependencies
$dependencies = require APP_DIR.'/dependencies.php';
$dependencies($container);

// Register middleware
$middleware = require APP_DIR.'/middleware.php';
$middleware($app);

// Register routes
$routes = require APP_DIR.'/routes.php';
$routes($app);

$app->run();
