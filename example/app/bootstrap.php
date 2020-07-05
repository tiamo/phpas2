<?php

use Slim\App;

require __DIR__.'/../vendor/autoload.php';

$app = new App([
    'settings' => require __DIR__.'/../config/settings.php',
]);

$container = $app->getContainer();

// Set up dependencies
$dependencies = require __DIR__.'/dependencies.php';
$dependencies($container);

// Register middleware
$middleware = require __DIR__.'/middleware.php';
$middleware($app);

// Register routes
$routes = require __DIR__.'/routes.php';
$routes($app);
