<?php


namespace Yetione\RabbitMQ\Connection;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection as AMQPAbstractConnection;
use PhpAmqpLib\Connection\Heartbeat\PCNTLHeartbeatSender;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Yetione\RabbitMQ\DTO\Exchange;
use Yetione\RabbitMQ\DTO\ExchangeBinding;
use Yetione\RabbitMQ\DTO\QosOptions;
use Yetione\RabbitMQ\DTO\Queue;
use Yetione\RabbitMQ\DTO\QueueBinding;
use Yetione\RabbitMQ\Exception\ConnectionIsNotSetupException;


abstract class AbstractConnection
{
    protected AMQPAbstractConnection $connection;

    protected AMQPChannel $channel;

    protected PCNTLHeartbeatSender $heartbeatSender;

    protected array $declaredExchanges = [];

    protected array $declaredQueues = [];

    protected array $declaredQueuesBinds = [];

    protected array $declaredExchangeBindings = [];

    /**
     * @param AMQPAbstractConnection $connection
     */
    protected function setup(AMQPAbstractConnection $connection): void
    {
        if (!$this->isConnectionSetup()) {
            $this->setConnection($connection);
            try {
                $this->registerHeartbeat();
            } catch (ConnectionIsNotSetupException $e) {
                // TODO: Log
            }
            $this->cleanData();
        }
    }

    /**
     * @throws ConnectionIsNotSetupException
     */
    protected function destroy(): void
    {
        $this->closeChannel()
            ->unregisterHeartbeat()
            ->closeConnection()
            ->cleanData();
    }

    /**
     * @return AMQPChannel
     * @throws ConnectionIsNotSetupException
     */
    protected function getChannel(): AMQPChannel
    {
        if (!$this->isChannelSetup()) {
            $this->setChannel($this->createChannel());
        }
        return $this->channel;
    }

    protected function setChannel(AMQPChannel $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * @param int|null $channelId
     * @return AMQPChannel
     * @throws ConnectionIsNotSetupException
     */
    protected function createChannel(?int $channelId=null): AMQPChannel
    {
        $this->connectionRequired();
        return $this->connection->channel($channelId);
    }

    /**
     * @return bool
     * @throws ConnectionIsNotSetupException
     */
    protected function isChannelOpen(): bool
    {
        return $this->isChannelSetup() && $this->getChannel()->is_open();
    }

    protected function isChannelSetup(): bool
    {
        return isset($this->channel);
    }

    /**
     * @return $this
     * @throws ConnectionIsNotSetupException
     */
    protected function closeChannel(): self
    {
        if ($this->isChannelOpen()) {
            try {
                $this->getChannel()->close();
            } catch (Exception $e) {
                // TODO: Log
            }
            $this->cleanChannel();
        }
        return $this;
    }

    protected function cleanChannel(): void
    {
        unset($this->channel);
    }

    /**
     * @return AMQPAbstractConnection
     * @throws ConnectionIsNotSetupException
     */
    protected function getConnection(): AMQPAbstractConnection
    {
        $this->connectionRequired();
        return $this->connection;
    }

    protected function setConnection(AMQPAbstractConnection $connection): self
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * @return $this
     * @throws ConnectionIsNotSetupException
     */
    protected function closeConnection(): self
    {
        $this->connectionRequired();
        try {
            $this->getConnection()->close();
        } catch (Exception $e) {
            // TODO: Log
        }
        $this->cleanConnection();
        return $this;
    }

    protected function cleanConnection(): void
    {
        unset($this->connection);
    }

    protected function isConnectionSetup(): bool
    {
        return isset($this->connection);
    }

    /**
     * @return bool
     * @throws ConnectionIsNotSetupException
     */
    protected function isConnectionConnected(): bool
    {
        return $this->getConnection()->isConnected();
    }

    /**
     * @return bool
     * @throws ConnectionIsNotSetupException
     */
    protected function connectionRequired(): bool
    {
        if (!$this->isConnectionSetup()) {
            throw new ConnectionIsNotSetupException('AMQP connection is not setup yet');
        }
        return true;
    }

    /**
     * @return bool
     * @throws ConnectionIsNotSetupException
     */
    protected function registerHeartbeat(): bool
    {
        $this->connectionRequired();
        if (!$this->isHeartbeatRegistered() && 0 < $this->connection->getHeartbeat()) {
            try {
                $heartbeatSender = new PCNTLHeartbeatSender($this->connection);
                $heartbeatSender->register();
                $this->heartbeatSender = $heartbeatSender;
                return true;
            } catch (AMQPRuntimeException $e) {
                // TODO: Log
            }
        }
        return false;
    }

    /**
     * @return $this
     * @throws ConnectionIsNotSetupException
     */
    protected function unregisterHeartbeat(): self
    {
        $this->connectionRequired();
        if ($this->isHeartbeatRegistered()) {
            $this->heartbeatSender->unregister();
            $this->cleanHeartbeat();
        }
        return $this;
    }

    protected function cleanHeartbeat(): void
    {
        unset($this->heartbeatSender);
    }

    protected function isHeartbeatRegistered(): bool
    {
        return isset($this->heartbeatSender);
    }

    /**
     * @param Exchange $exchange
     * @param bool $forceDeclare
     * @return bool
     * @throws ConnectionIsNotSetupException
     */
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

    /**
     * @param Queue $queue
     * @param bool $forceDeclare
     * @return bool
     * @throws ConnectionIsNotSetupException
     */
    public function declareQueue(Queue $queue, bool $forceDeclare=false): bool
    {
        if (($queue->isDeclare() && !isset($this->declaredQueues[$queue->getName()])) || $forceDeclare) {
            try {
                list($queueName,,) = $this->getChannel()->queue_declare(
                    $queue->getName(),
                    $queue->isPassive(),
                    $queue->isDurable(),
                    $queue->isExclusive(),
                    $queue->isAutoDelete(),
                    $queue->isNowait(),
                    $queue->getArguments(),
                    $queue->getTicket()
                );
                if ($queueName !== $queue->getName()) {
                    $queue->setName($queueName);
                }
                $this->declaredQueues[$queue->getName()] = true;
                return true;
            } catch (AMQPTimeoutException $e) {
                // TODO: log
            }
        }
        return false;
    }

    /**
     * @param QueueBinding $binding
     * @param bool $forceDeclare
     * @return bool
     * @throws ConnectionIsNotSetupException
     */
    public function declareQueueBinding(QueueBinding $binding, bool $forceDeclare=false): bool
    {
        if (($binding->isDeclare() && !isset($this->declaredQueuesBinds[$binding->getKey()])) || $forceDeclare) {
            $routingKeys = !is_array($binding->getRoutingKey()) ?
                [$binding->getRoutingKey()] :
                $binding->getRoutingKey();
            foreach ($routingKeys as $routingKey) {
                try {
                    $this->getChannel()->queue_bind(
                        $binding->getQueue()->getName(),
                        $binding->getExchange()->getName(),
                        $routingKey,
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

    /**
     * @param ExchangeBinding $binding
     * @param bool $forceDeclare
     * @return bool
     * @throws ConnectionIsNotSetupException
     */
    public function declareExchangeBinding(ExchangeBinding $binding, bool $forceDeclare=false): bool
    {
        if (($binding->isDeclare() && !isset($this->declaredExchangeBindings[$binding->getKey()])) || $forceDeclare) {
            $routingKeys = !is_array($binding->getRoutingKey()) ?
                [$binding->getRoutingKey()] :
                $binding->getRoutingKey();
            foreach ($routingKeys as $routingKey) {
                try {
                    $this->getChannel()->exchange_bind(
                        $binding->getDestination()->getName(),
                        $binding->getExchange()->getName(),
                        $routingKey,
                        $binding->isNowait(),
                        $binding->getArguments(),
                        $binding->getTicket()
                    );
                } catch (AMQPTimeoutException $e) {
                    // TODO: Log
                    return false;
                }
            }
            $this->declaredExchangeBindings[$binding->getKey()] = true;
            return true;

        }
        return false;
    }

    /**
     * @param Queue $queue
     * @param bool $noWait
     * @return bool
     * @throws ConnectionIsNotSetupException
     */
    public function purgeQueue(Queue $queue, bool $noWait=true): bool
    {
        try {
            $this->getChannel()->queue_purge($queue->getName(), $noWait, $queue->getTicket());
            return true;
        } catch (AMQPTimeoutException $e) {
            // TODO: Log
        }
        return false;
    }

    /**
     * @param Queue $queue
     * @param bool $onlyIfUnused
     * @param bool $onlyIfEmpty
     * @param bool $noWait
     * @return bool
     * @throws ConnectionIsNotSetupException
     */
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
            if (isset($this->declaredQueues[$queue->getName()])) {
                unset($this->declaredQueues[$queue->getName()]);
            }
            return true;
        } catch (AMQPTimeoutException $e) {
            // TODO: Log
        }
        return false;
    }

    /**
     * @param Exchange $exchange
     * @param bool $onlyIfUnused
     * @param bool $noWait
     * @return bool
     * @throws ConnectionIsNotSetupException
     */
    public function deleteExchange(Exchange $exchange, bool $onlyIfUnused=false, bool $noWait=false): bool
    {
        try {
            $this->getChannel()->exchange_delete(
                $exchange->getName(),
                $onlyIfUnused,
                $noWait,
                $exchange->getTicket()
            );
            if (isset($this->declaredExchanges[$exchange->getName()])) {
                unset($this->declaredExchanges[$exchange->getName()]);
            }
            return true;
        } catch (AMQPTimeoutException $e) {
            // TODO: Log
        }
        return false;
    }

    /**
     * @param ExchangeBinding $binding
     * @param bool $noWait
     * @return bool
     * @throws ConnectionIsNotSetupException
     */
    public function unbindExchange(ExchangeBinding $binding, bool $noWait=false): bool
    {
        try {
            $routingKeys = !is_array($binding->getRoutingKey()) ?
                [$binding->getRoutingKey()] :
                $binding->getRoutingKey();
            foreach ($routingKeys as $routingKey) {
                $this->getChannel()->exchange_unbind(
                    $binding->getDestination()->getName(),
                    $binding->getExchange()->getName(),
                    $routingKey,
                    $noWait,
                    $binding->getArguments(),
                    $binding->getTicket()
                );
            }
            if (isset($this->declaredExchangeBindings[$binding->getKey()])) {
                unset($this->declaredExchangeBindings[$binding->getKey()]);
            }
            return true;
        } catch (AMQPTimeoutException $e) {
            // TODO: Log
        }
        return false;
    }

    /**
     * @param QueueBinding $binding
     * @return bool
     * @throws ConnectionIsNotSetupException
     */
    public function unbindQueue(QueueBinding $binding): bool
    {
        try {
            $routingKeys = !is_array($binding->getRoutingKey()) ?
                [$binding->getRoutingKey()] :
                $binding->getRoutingKey();
            foreach ($routingKeys as $routingKey) {
                $this->getChannel()->queue_unbind(
                    $binding->getQueue()->getName(),
                    $binding->getExchange()->getName(),
                    $routingKey,
                    $binding->getArguments(),
                    $binding->getTicket()
                );
            }
            if (isset($this->declaredQueuesBinds[$binding->getKey()])) {
                unset($this->declaredQueuesBinds[$binding->getKey()]);
            }
            return true;
        } catch (AMQPTimeoutException $e) {
            // TODO: Log
        }
        return false;
    }

    /**
     * @param QosOptions $qosOptions
     * @return bool
     * @throws ConnectionIsNotSetupException
     */
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
            // TODO: Log
        }
        return false;
    }

    protected function cleanData(): self
    {
        $this->declaredExchanges = [];
        $this->declaredQueues = [];
        $this->declaredQueuesBinds = [];
        $this->declaredExchangeBindings = [];
        return $this;
    }
}