<?php


namespace Yetione\RabbitMQ\Logger;


use Psr\Log\LoggerInterface;

interface Loggable
{
    public function getLogger(): LoggerInterface;

    public function setLoggerProvider(LoggerProviderInterface $loggerProvider): void;
}