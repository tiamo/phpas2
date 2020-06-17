<?php

use AS2\Management;
use models\FileLogger;
use models\FileStorage;

require_once __DIR__."/../vendor/autoload.php";
require_once __DIR__."/functions.php";

spl_autoload_register(
    function ($class) {
        spl_autoload(strtolower(str_replace("\\", "/", $class)));
    }
);

$config = require 'config.php';

// Initialize AS2 storage provider
$storage = new FileStorage($config['storage_path']);

// Initialize AS2 manager
$manager = new Management();

if (! empty($config['log_path'])) {
    $manager->setLogger(new FileLogger($config['log_path']));
}
