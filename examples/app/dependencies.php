<?php

use App\Repositories\MessageRepository;
use App\Repositories\PartnerRepository;
use AS2\Management;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

return function ($container) {

    $container['MessageRepository'] = function ($c) {
        return new MessageRepository([
            'path' => $c['settings']['storage']['path'],
        ]);
    };

    $container['PartnerRepository'] = function ($c) {
        return new PartnerRepository(
            require __DIR__.'/partners.php'
        );
    };

    $container['logger'] = function ($c) {
        $logger = new Logger('app');
        $fileHandler = new StreamHandler(
            sprintf('%s/logs/app.log', $c['settings']['storage']['path'])
        );
        $logger->pushHandler($fileHandler);

        return $logger;
    };

    $container['manager'] = function ($c) {
        $manager = new Management($c['settings']['management']);
        $manager->setLogger($c['logger']);

        return $manager;
    };

};
