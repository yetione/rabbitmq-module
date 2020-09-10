<?php


namespace Yetione\RabbitMQ\Connection;


use PhpAmqpLib\Channel\AMQPChannel;
use Yetione\RabbitMQ\Exception\ConnectionException;
use Yetione\RabbitMQ\Service\RabbitMQService;

trait InteractsWithConnection
{
    protected ConnectionInterface $connectionWrapper;

    protected bool $autoReconnect = true;

    protected int $reconnectRetries = 5;

    protected string $connectionOptionsName;

    protected string $connectionName;

    protected RabbitMQService $rabbitMQService;

    /**
     * Ждем пол секунды перед реконнектом
     * @var int
     */
    protected int $waitBeforeReconnect = 500000;

    /**
     * @return ConnectionInterface
     * @throws ConnectionException
     */
    protected function createConnection(): ConnectionInterface
    {
        if (null === ($con = $this->rabbitMQService->getConnection($this->connectionName, $this->connectionOptionsName))) {
            throw new ConnectionException("Cannot create connection {$this->connectionName} with option {$this->connectionOptionsName}.");
        }
        return $con;
    }

    protected function maybeReconnect(): void
    {
        if ($this->isAutoReconnect()) {
            $tries = 0;
            while ($tries < $this->reconnectRetries && !$this->isConnected()) {
                $this->reconnect();
                if (0 < $this->getWaitBeforeReconnect()) {
                    usleep($this->getWaitBeforeReconnect());
                }
                $tries++;
            }
        }
        return;
    }

    public function connection(): ConnectionInterface
    {
        return $this->getConnectionWrapper();
    }

    protected function reconnect(): self
    {
        $this->connection()->reconnect();
        return $this;
    }

    protected function close(): self
    {
        return $this->closeChannel()->closeConnection();
    }

    protected function closeChannel(): self
    {
        if ($this->getConnectionWrapper()->isChannelOpen()) {
            $this->getConnectionWrapper()->closeChannel();
        }
        return $this;
    }

    protected function closeConnection(): self
    {
        if ($this->getConnectionWrapper()->isConnectionOpen()) {
            $this->getConnectionWrapper()->close();
        }
        return $this;
    }

    public function isConnected(): bool
    {
        $con = $this->getConnectionWrapper();
        return $con->isConnectionOpen() && $con->isChannelOpen();
    }

    public function channel(): AMQPChannel
    {
        return $this->getConnectionWrapper()->getChannel();
    }

    public function getConnectionWrapper(): ConnectionInterface
    {
        return $this->connectionWrapper;
    }

    public function setConnectionWrapper(ConnectionInterface $connectionWrapper): self
    {
        $this->connectionWrapper = $connectionWrapper;
        return $this;
    }

    public function isAutoReconnect(): bool
    {
        return $this->autoReconnect;
    }

    public function setAutoReconnect(bool $autoReconnect): self
    {
        $this->autoReconnect = $autoReconnect;
        return $this;
    }

    public function getWaitBeforeReconnect(): int
    {
        return $this->waitBeforeReconnect;
    }

    public function setWaitBeforeReconnect(int $waitBeforeReconnect): self
    {
        $this->waitBeforeReconnect = $waitBeforeReconnect;
        return $this;
    }

    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    public function setConnectionName(string $connectionName): self
    {
        $this->connectionName = $connectionName;
        return $this;
    }

    public function getConnectionOptionsName(): string
    {
        return $this->connectionOptionsName;
    }

    public function setConnectionOptionsName(string $connectionOptionsName): self
    {
        $this->connectionOptionsName = $connectionOptionsName;
        return $this;
    }
}