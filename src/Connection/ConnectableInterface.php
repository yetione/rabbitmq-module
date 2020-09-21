<?php


namespace Yetione\RabbitMQ\Connection;


interface ConnectableInterface
{
    public function getConnectionWrapper(): ConnectionInterface;
}