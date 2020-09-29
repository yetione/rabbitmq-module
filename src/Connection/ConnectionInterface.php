<?php


namespace Yetione\RabbitMQ\Connection;


use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use Yetione\RabbitMQ\DTO\Exchange;
use Yetione\RabbitMQ\DTO\ExchangeBinding;
use Yetione\RabbitMQ\DTO\QosOptions;
use Yetione\RabbitMQ\DTO\Queue;
use Yetione\RabbitMQ\DTO\QueueBinding;

interface ConnectionInterface
{

    public function getChannel(bool $createNew=false): AMQPChannel;

    public function setChannel(?AMQPChannel $channel): ConnectionInterface;

    public function isChannelOpen(): bool;

    public function closeChannel(): ConnectionInterface;

    public function createChannel(int $channelId=null): AMQPChannel;

    public function getConnection(): AbstractConnection;

    public function setConnection(AbstractConnection $connection): ConnectionInterface;

    public function isConnectionOpen(): bool;

    public function closeConnection(): ConnectionInterface;

    public function close(): ConnectionInterface;

    public function open(): ConnectionInterface;

    public function reconnect(int $waitBeforeConnect=0): ConnectionInterface;

    public function declareExchange(Exchange $exchange, bool $forceDeclare=false): bool;

    public function declareQueue(Queue $queue, bool $forceDeclare=false): bool;

    public function declareQueueBinding(QueueBinding $binding, bool $forceDeclare=false): bool;

    public function declareExchangeBinding(ExchangeBinding $binding, bool $forceDeclare=false): bool;

    public function purgeQueue(Queue $queue, bool $noWait=true): bool;

    public function deleteQueue(Queue $queue, bool $onlyIfUnused=false, bool $onlyIfEmpty=false, bool $noWait=false): bool;

    public function deleteExchange(Exchange $exchange, bool $onlyIfUnused=false, bool $noWait=false): bool;

    public function unbindExchange(ExchangeBinding $binding, bool $noWait=false): bool;

    public function unbindQueue(QueueBinding $binding): bool;

    public function declareQosOptions(QosOptions $qosOptions): bool;

    public function registerHeartbeat(): bool;

    public function unregisterHeartbeat(): bool;

    public function isHeartbeatRegister(): bool;

}