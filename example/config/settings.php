<?php

use Monolog\Logger;

return [
    /**
     * @see http://www.slimframework.com/docs/v3/objects/application.html#application-configuration
     */
    'displayErrorDetails' => true,

    'logger' => [
        'level' => Logger::DEBUG,
        'path' => __DIR__ . '/../storage/logs/app.log',
        // ...
    ],

    'management' => [
        /**
         * @see \AS2\Management::$options
         */
    ],

    'storage' => [
        'path' => __DIR__.'/../storage',
    ],
];
