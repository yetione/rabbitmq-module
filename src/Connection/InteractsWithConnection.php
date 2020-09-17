<?php


namespace Yetione\RabbitMQ\Connection;


use PhpAmqpLib\Channel\AMQPChannel;

trait InteractsWithConnection
{
    protected ConnectionInterface $connectionWrapper;

    protected bool $autoReconnect = true;

    protected int $reconnectRetries = 5;

    /**
     * Ждем пол секунды перед реконнектом
     * @var int
     */
    protected int $waitBeforeReconnect = 500000;

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
            $this->getConnectionWrapper()->unregisterHeartbeat();
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
}