<?php


namespace Yetione\RabbitMQ\Logger;


use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class NullLoggerProvider implements LoggerProviderInterface
{
    protected LoggerInterface $logger;

    public function getLogger(): LoggerInterface
    {
        if (!isset($this->logger)) {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }
}