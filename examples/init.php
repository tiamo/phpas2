<?php

require_once __DIR__."/bootstrap.php";

// init storage
if (! is_dir($config['storage_path'])) {
    echo sprintf('Prepare storage').PHP_EOL;
    mkdir($config['storage_path'], 0777, true);
    foreach ([$storage::TYPE_MESSAGE, $storage::TYPE_PARTNER] as $dir) {
        $path = $config['storage_path'].DIRECTORY_SEPARATOR.$dir;
        if (! is_dir($path)) {
            mkdir($path, 0777, true);
            echo sprintf('`%s` - created', $path).PHP_EOL;
        } else {
            echo sprintf('`%s` - already exists', $path).PHP_EOL;
        }
    }
}

// init partners
echo sprintf('Prepare partners').PHP_EOL;
foreach ($config['partners'] as $partner) {
    if (empty($partner['id'])) {
        throw new \InvalidArgumentException('`id` required.');
    }

    $saved = $storage->savePartner(
        $storage->initPartner($partner)
    );

    if ($saved) {
        echo sprintf('Partner `%s` - OK', $partner['id']).PHP_EOL;
    } else {
        echo sprintf('Partner `%s` - FAILED', $partner['id']).PHP_EOL;
    }
}
