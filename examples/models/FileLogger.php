<?php

/** @noinspection PhpComposerExtensionStubsInspection */

namespace models;

use Psr\Log\AbstractLogger;

class FileLogger extends AbstractLogger
{
    protected $path;

    public function __construct($filePath)
    {
        $this->path = $filePath;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param  mixed  $level
     * @param  string  $message
     * @param  array  $context
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $message = trim(sprintf('[%s][%s] %s %s',
                date('Y-m-d H:i:s'),
                strtoupper($level),
                $message,
                !empty($context) ? json_encode($context) : null
            )).PHP_EOL;

        $flags = 0;

        if ($this->path !== 'php://stdout') {
            $flags = FILE_APPEND;
        }

        @file_put_contents($this->path, $message, $flags);
    }
}
