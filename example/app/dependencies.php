<?php

use App\Repositories\MessageRepository;
use App\Repositories\PartnerRepository;
use AS2\Management;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

return function ($container) {

    $container['MessageRepository'] = function ($c) {
        return new MessageRepository([
            'path' => $c['settings']['storage']['path'].'/messages',
        ]);
    };

    $container['PartnerRepository'] = function ($c) {
        return new PartnerRepository(
            require __DIR__.'/../config/partners.php'
        );
    };

    $container['logger'] = function ($c) {
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler('php://stdout'));

        if (! empty($c['settings']['logger'])) {
            $fileHandler = new StreamHandler(
                $c['settings']['logger']['path'],
                $c['settings']['logger']['level']
            );
            $logger->pushHandler($fileHandler);
        }

        return $logger;
    };

    $container['manager'] = function ($c) {
        $manager = new Management($c['settings']['management']);
        $manager->setLogger($c['logger']);

        return $manager;
    };

};
