<?php

use Monolog\Handler\StreamHandler;
// use Monolog\Logger;

$storagePath = __DIR__.'/../storage';

return [
    /**
     * @see http://www.slimframework.com/docs/v3/objects/application.html#application-configuration
     */
    'displayErrorDetails' => true,

    'logHandlers' => [
        // TODO: some hosting providers doesn't support 'php://stdout'
        new StreamHandler('php://stdout'),
        // new StreamHandler($storagePath.'/logs/app.log', Logger::DEBUG),
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
