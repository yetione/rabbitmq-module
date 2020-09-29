<?php


namespace Yetione\RabbitMQ\Logger;


use Psr\Log\LoggerInterface;

interface LoggerProviderInterface
{
    public function getLogger(): LoggerInterface;
}