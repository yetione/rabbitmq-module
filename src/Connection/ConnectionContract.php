<?php


namespace Yetione\RabbitMQ\Connection;


interface ConnectionContract
{
    public function connect(): void;

    public function close(): void;

    public function reconnect(int $delay=0): void;

    public function channel(): void;

    public function isConnected(): bool;




}