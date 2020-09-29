<?php


namespace Yetione\RabbitMQ\Logger;


use Psr\Log\LoggerInterface;

trait WithLogger
{
    protected LoggerInterface $logger;

    public function getLogger(): LoggerInterface
    {
        if (!isset($this->logger)) {
            $this->setLoggerProvider(new NullLoggerProvider());
        }
        return $this->logger;
    }

    public function setLoggerProvider(LoggerProviderInterface $loggerProvider): void
    {
        $this->logger = $loggerProvider->getLogger();
    }
}