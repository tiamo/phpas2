<?php

use App\Repositories\MessageRepository;
use App\Repositories\PartnerRepository;
use AS2\Management;
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
        if (! empty($c['settings']['logHandlers'])) {
            foreach ($c['settings']['logHandlers'] as $handler) {
                $logger->pushHandler($handler);
            }
        }

        return $logger;
    };

    $container['manager'] = function ($c) {
        $manager = new Management($c['settings']['management']);
        $manager->setLogger($c['logger']);

        return $manager;
    };

};
