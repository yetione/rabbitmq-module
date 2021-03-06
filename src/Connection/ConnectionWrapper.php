<?php


namespace Yetione\RabbitMQ\Connection;


use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\Heartbeat\PCNTLHeartbeatSender;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Wire\AMQPTable;
use Throwable;
use Yetione\RabbitMQ\DTO\ExchangeBinding;
use Yetione\RabbitMQ\DTO\Exchange;
use Yetione\RabbitMQ\DTO\QosOptions;
use Yetione\RabbitMQ\DTO\Queue;
use Yetione\RabbitMQ\DTO\QueueBinding;
use Yetione\RabbitMQ\Logger\Loggable;
use Yetione\RabbitMQ\Logger\WithLogger;

class ConnectionWrapper implements ConnectionInterface, Loggable
{
    use WithLogger;

    protected AbstractConnection $connection;

    protected ?AMQPChannel $channel;

    protected array $declaredExchanges = [];

    protected array $declaredQueues = [];

    protected array $declaredQueuesBinds = [];

    protected array $declaredExchangeBindings = [];

    protected ?PCNTLHeartbeatSender $heartbeatSender = null;

    public function __construct(AbstractConnection $connection)
    {
        $this->setupConnection($connection);
    }

    protected function setupConnection(AbstractConnection $connection)
    {
        $this->setConnection($connection);
        $this->registerHeartbeat();
    }

    public function getChannel(bool $createNew=false): AMQPChannel
    {
        if (!isset($this->channel) || $createNew) {
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
     * @return ConnectionWrapper
     */
    public function closeChannel(): self
    {
        if ($this->isChannelOpen()) {
            try {
                $this->getChannel()->close();
            } catch (Exception | Throwable $e) {
                $this->getLogger()->error($e->getMessage());
            }
            unset($this->channel);
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
     * @return bool
     */
    public function isChannelOpen(): bool
    {
        return isset($this->channel) && $this->getChannel()->is_open();
    }

    public function registerHeartbeat(): bool
    {
        if (!$this->isHeartbeatRegister() && 0 < $this->getConnection()->getHeartbeat()) {
            $this->setHeartbeatSender(new PCNTLHeartbeatSender($this->getConnection()));
            $this->getHeartbeatSender()->register();
            return true;
        }
        return false;
    }

    public function unregisterHeartbeat(): bool
    {
        if ($this->isHeartbeatRegister()) {
            $this->getHeartbeatSender()->unregister();
            $this->setHeartbeatSender(null);
            return true;
        }
        return false;
    }

    public function isHeartbeatRegister(): bool
    {
        return isset($this->heartbeatSender) && null !== $this->heartbeatSender;
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
                $this->getLogger()->error($e->getMessage());
            }
        }
        return $this;
    }

    public function close(): self
    {
        $this->closeChannel();
        $this->unregisterHeartbeat();
        $this->closeConnection();
        $this->resetDeclaredData();
        return $this;
    }

    public function open(): self
    {
        if (!$this->isConnectionOpen()) {
            $this->getConnection()->reconnect();
            $this->registerHeartbeat();
            if (!$this->isChannelOpen()) {
                $this->setChannel($this->createChannel());
            }
        }
        return $this;
    }

    public function reconnect(int $waitBeforeConnect=0): self
    {
        $this->close();
        if (0 < $waitBeforeConnect) {
            usleep($waitBeforeConnect);
        }
        $this->open();
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
                $this->getLogger()->error($e->getMessage());
            }
        }
        return false;
    }

    public function declareQueue(Queue $queue, bool $forceDeclare=false): bool
    {
        if (($queue->isDeclare() && !isset($this->declaredQueues[$queue->getName()])) || $forceDeclare) {
            try {
                list($sQueueName,,) = $this->getChannel()->queue_declare(
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
                $this->getLogger()->error($e->getMessage());
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
            $arguments = !empty($binding->getArguments()) ? new AMQPTable($binding->getArguments()) : [];
            foreach ($aRoutingKey as $sRoutingKey) {
                try {
                    $this->getChannel()->queue_bind(
                        $binding->getQueue()->getName(),
                        $binding->getExchange()->getName(),
                        $sRoutingKey,
                        $binding->isNowait(),
                        $arguments,
                        $binding->getTicket()
                    );
                } catch (AMQPTimeoutException $e) {
                    $this->getLogger()->error($e->getMessage());
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
            $arguments = !empty($binding->getArguments()) ? new AMQPTable($binding->getArguments()) : [];
            foreach ($aRoutingKey as $sRoutingKey) {
                try {
                    $this->getChannel()->exchange_bind(
                        $binding->getDestination()->getName(),
                        $binding->getExchange()->getName(),
                        $sRoutingKey,
                        $binding->isNowait(),
                        $arguments,
                        $binding->getTicket()
                    );
                } catch (AMQPTimeoutException $e) {
                    $this->getLogger()->error($e->getMessage());
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    public function purgeQueue(Queue $queue, bool $noWait=true): bool
    {
        try {
            $this->getChannel()->queue_purge($queue->getName(), $noWait, $queue->getTicket());
            return true;
        } catch (AMQPTimeoutException $e) {
            $this->getLogger()->error($e->getMessage());
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
            $this->getLogger()->error($e->getMessage());
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
            $this->getLogger()->error($e->getMessage());
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
            $this->getLogger()->error($e->getMessage());
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
            $this->getLogger()->error($e->getMessage());
            return false;
        }
    }

    public function declareQosOptions(QosOptions $qosOptions): bool
    {
        try {
            $this->getChannel()->basic_qos(
                $qosOptions->getPrefetchSize(),
                $qosOptions->getPrefetchCount(),
                $qosOptions->getGlobal()
            );
            return true;
        } catch (AMQPTimeoutException $e) {
            $this->getLogger()->error($e->getMessage());
        }
        return false;
    }

    protected function resetDeclaredData()
    {
        $this->declaredExchanges = [];
        $this->declaredQueues = [];
        $this->declaredQueuesBinds = [];
        $this->declaredExchangeBindings = [];
    }

    /**
     * @return PCNTLHeartbeatSender|null
     */
    public function getHeartbeatSender(): ?PCNTLHeartbeatSender
    {
        return $this->heartbeatSender;
    }

    /**
     * @param PCNTLHeartbeatSender|null $heartbeatSender
     * @return ConnectionWrapper
     */
    public function setHeartbeatSender(?PCNTLHeartbeatSender $heartbeatSender): ConnectionWrapper
    {
        $this->heartbeatSender = $heartbeatSender;
        return $this;
    }
}