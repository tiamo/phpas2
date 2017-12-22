<?php

namespace AS2\Tests\Mock;

use Psr\Log\AbstractLogger;

class ConsoleLogger extends AbstractLogger
{
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
        echo implode(' - ', [
                $level,
                $message,
                json_encode($context)
            ]) . PHP_EOL;
    }
}