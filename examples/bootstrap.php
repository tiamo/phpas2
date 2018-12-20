<?php

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/functions.php";

spl_autoload_register(function ($class) {
    spl_autoload(strtolower(str_replace("\\", "/", $class)));
});

$storage = new \models\FileStorage();
$manager = new \AS2\Management();
$manager->setLogger(new \models\FileLogger('log.txt'));
