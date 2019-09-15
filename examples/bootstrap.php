<?php

require_once __DIR__."/../vendor/autoload.php";
require_once __DIR__."/functions.php";

spl_autoload_register(
    function ($class) {
        spl_autoload(strtolower(str_replace("\\", "/", $class)));
    }
);

$config = require 'config.php';

// init storage provider
$storage = new \models\FileStorage($config['storage_path']);

$manager = new \AS2\Management();

if (! empty($config['log_path'])) {
    $manager->setLogger(new \models\FileLogger($config['log_path']));
}
