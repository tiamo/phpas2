<?php

use Monolog\Logger;

$storagePath = __DIR__.'/../storage';

return [
    /**
     * @see http://www.slimframework.com/docs/v3/objects/application.html#application-configuration
     */
    'displayErrorDetails' => true,

    'logger' => [
        'level' => Logger::DEBUG,
        'path' => $storagePath.'/logs/app.log',
        // ...
    ],

    'management' => [
        /**
         * @see \AS2\Management::$options
         */
    ],

    'storage' => [
        'path' => $storagePath,
    ],
];
