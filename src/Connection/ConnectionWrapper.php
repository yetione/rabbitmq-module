<?php


namespace Yetione\RabbitMQ\Connection;


use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Throwable;
use Yetione\RabbitMQ\DTO\ExchangeBinding;
use Yetione\RabbitMQ\DTO\Exchange;
use Yetione\RabbitMQ\DTO\Queue;
use Yetione\RabbitMQ\DTO\QueueBinding;

class ConnectionWrapper implements ConnectionInterface
{
    protected AbstractConnection $connection;

    protected ?AMQPChannel $channel;

    protected array $declaredExchanges = [];

    protected array $declaredQueues = [];

    protected array $declaredQueuesBinds = [];

    protected array $declaredExchangeBindings = [];

    public function __construct(AbstractConnection $connection)
    {
        $this->setConnection($connection);
    }

    public function getChannel(bool $createNew=false): AMQPChannel
    {
        if (null === $this->channel || $createNew) {
            $this->setChannel($this->closeChannel()->createChannel());
        }
        return $this->channel;
    }

    public function setChannel(?AMQPChannel $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * @return bool
     */
    public function isChannelOpen(): bool
    {
        return null !== $this->channel && $this->getChannel()->is_open();
    }

    /**
     * @return ConnectionWrapper
     */
    public function closeChannel(): self
    {
        if (null !== $this->channel) {
            try {
                $this->channel->close();
            } catch (Exception | Throwable $e) {
                // TODO: Log
            }
            $this->channel = null;
        }
        return $this;
    }

    /**
     * @param int|null $channelId
     * @return AMQPChannel
     */
    public function createChannel(int $channelId=null): AMQPChannel
    {
        return $this->getConnection()->channel($channelId);
    }

    /**
     * @return AbstractConnection
     */
    public function getConnection(): AbstractConnection
    {
        return $this->connection;
    }

    /**
     * @param AbstractConnection $connection
     * @return ConnectionWrapper
     */
    public function setConnection(AbstractConnection $connection): self
    {
        $this->connection = $connection;
        return $this;
    }

    public function isConnectionOpen(): bool
    {
        return $this->getConnection()->isConnected();
    }

    public function closeConnection(): self
    {
        if ($this->isConnectionOpen()) {
            try {
                $this->getConnection()->close();
            } catch (Exception $e) {
                // TODO: Log
            }
        }
        return $this;
    }

    public function close(): self
    {
        if ($this->isChannelOpen()) {
            $this->closeChannel();
        }
        $this->closeConnection();
        $this->resetDeclaredData();
        return $this;
    }

    public function open(): self
    {
        if (!$this->isConnectionOpen()) {
            $this->getConnection()->reconnect();
            if (!$this->isChannelOpen()) {
                $this->setChannel($this->createChannel());
            }
        }
        return $this;
    }

    public function reconnect(): self
    {
        $this->closeChannel();
        $this->resetDeclaredData();
        $this->getConnection()->reconnect();
        $this->setChannel($this->createChannel());
        return $this;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function declareExchange(Exchange $exchange, bool $forceDeclare=false): bool
    {
        if (($exchange->isDeclare() && !isset($this->declaredExchanges[$exchange->getName()])) || $forceDeclare) {
            try {
                $this->getChannel()->exchange_declare(
                    $exchange->getName(),
                    $exchange->getType(),
                    $exchange->isPassive(),
                    $exchange->isDurable(),
                    $exchange->isAutoDelete(),
                    $exchange->isInternal(),
                    $exchange->isNowait(),
                    $exchange->getArguments(),
                    $exchange->getTicket()
                );
                $this->declaredExchanges[$exchange->getName()] = true;
                return true;
            } catch (AMQPTimeoutException $e) {
                //TODO: Log
            }
        }
        return false;
    }

    public function declareQueue(Queue $queue, bool $forceDeclare=false): bool
    {
        if (($queue->isDeclare() && !isset($this->declaredQueues[$queue->getName()])) || $forceDeclare) {
            try {
                list($sQueueName, $iMessageCount, $iConsumersCount) = $this->getChannel()->queue_declare(
                    $queue->getName(),
                    $queue->isPassive(),
                    $queue->isDurable(),
                    $queue->isExclusive(),
                    $queue->isAutoDelete(),
                    $queue->isNowait(),
                    $queue->getArguments(),
                    $queue->getTicket()
                );
                if ($sQueueName !== $queue->getName()) {
                    $queue->setName($sQueueName);
                }
                $this->declaredQueues[$queue->getName()] = true;
                return true;
            } catch (AMQPTimeoutException $e) {
                // TODO: log
            }
        }
        return false;
    }

    public function declareQueueBinding(QueueBinding $binding, bool $forceDeclare=false): bool
    {
        if (($binding->isDeclare() && !isset($this->declaredQueuesBinds[$binding->getKey()])) || $forceDeclare) {
            $aRoutingKey = !is_array($binding->getRoutingKey()) ?
                [$binding->getRoutingKey()] :
                $binding->getRoutingKey();
            foreach ($aRoutingKey as $sRoutingKey) {
                try {
                    $this->getChannel()->queue_bind(
                        $binding->getQueue()->getName(),
                        $binding->getExchange()->getName(),
                        $sRoutingKey,
                        $binding->isNowait(),
                        $binding->getArguments(),
                        $binding->getTicket()
                    );
                } catch (AMQPTimeoutException $e) {
                    // TODO: Log
                    return false;
                }
            }
            $this->declaredQueuesBinds[$binding->getKey()] = true;
            return true;
        }
        return false;
    }

    public function declareExchangeBinding(ExchangeBinding $binding, bool $forceDeclare=false): bool
    {
        if (($binding->isDeclare() && !isset($this->declaredExchangeBindings[$binding->getKey()])) || $forceDeclare) {
            $aRoutingKey = !is_array($binding->getRoutingKey()) ?
                [$binding->getRoutingKey()] :
                $binding->getRoutingKey();
            foreach ($aRoutingKey as $sRoutingKey) {
                try {
                    $this->getChannel()->exchange_bind(
                        $binding->getDestination()->getName(),
                        $binding->getExchange()->getName(),
                        $sRoutingKey,
                        $binding->isNowait(),
                        $binding->getArguments(),
                        $binding->getTicket()
                    );
                } catch (AMQPTimeoutException $e) {
                    // TODO: Log
                    return false;
                }
            }

        }
        return false;
    }

    public function purgeQueue(Queue $queue, bool $noWait=true): bool
    {
        try {
            $this->getChannel()->queue_purge($queue->getName(), $noWait, $queue->getTicket());
            return true;
        } catch (AMQPTimeoutException $e) {
            // TODO: Log
            return false;
        }
    }

    public function deleteQueue(Queue $queue, bool $onlyIfUnused=false, bool $onlyIfEmpty=false, bool $noWait=false): bool
    {
        try {
            $this->getChannel()->queue_delete(
                $queue->getName(),
                $onlyIfUnused,
                $onlyIfEmpty,
                $noWait,
                $queue->getTicket()
            );
            return true;
        } catch (AMQPTimeoutException $e) {
            // TODO: Log
            return false;
        }
    }

    public function deleteExchange(Exchange $exchange, bool $onlyIfUnused=false, bool $noWait=false): bool
    {
        try {
            $this->getChannel()->exchange_delete(
                $exchange->getName(),
                $onlyIfUnused,
                $noWait,
                $exchange->getTicket()
            );
            return true;
        } catch (AMQPTimeoutException $e) {
            // TODO: Log
            return false;
        }
    }

    public function unbindExchange(ExchangeBinding $binding, bool $noWait=false): bool
    {
        try {
            $aRoutingKey = !is_array($binding->getRoutingKey()) ?
                [$binding->getRoutingKey()] :
                $binding->getRoutingKey();
            foreach ($aRoutingKey as $sKey) {
                $this->getChannel()->exchange_unbind(
                    $binding->getDestination()->getName(),
                    $binding->getExchange()->getName(),
                    $sKey,
                    $noWait,
                    $binding->getArguments(),
                    $binding->getTicket()
                );
            }
            return true;
        } catch (AMQPTimeoutException $e) {
            // TODO: Log
            return false;
        }
    }

    public function unbindQueue(QueueBinding $binding): bool
    {
        try {
            $aRoutingKey = !is_array($binding->getRoutingKey()) ?
                [$binding->getRoutingKey()] :
                $binding->getRoutingKey();
            foreach ($aRoutingKey as $sKey) {
                $this->getChannel()->queue_unbind(
                    $binding->getQueue()->getName(),
                    $binding->getExchange()->getName(),
                    $sKey,
                    $binding->getArguments(),
                    $binding->getTicket()
                );
            }
            return true;
        } catch (AMQPTimeoutException $e) {
            // TODO: Log
            return false;
        }
    }

    protected function resetDeclaredData()
    {
        $this->declaredExchanges = [];
        $this->declaredQueues = [];
        $this->declaredQueuesBinds = [];
        $this->declaredExchangeBindings = [];
    }
}