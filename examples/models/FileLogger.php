<?php

namespace models;

use Psr\Log\AbstractLogger;

class FileLogger extends AbstractLogger
{
    protected $fileName;

    public function __construct($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $message = implode(' - ', [
                $level,
                $message,
                json_encode($context)
            ]) . PHP_EOL;

        file_put_contents($this->fileName, $message, FILE_APPEND);
    }
}