<?php


namespace Yetione\RabbitMQ\Connection;


use PhpAmqpLib\Channel\AMQPChannel;
use Yetione\RabbitMQ\DTO\Connectable;

/**
 * Trait InteractsWithConnection
 * @package Yetione\RabbitMQ\Connection
 *
 * @property Connectable $options
 */
trait InteractsWithConnection
{
    protected ConnectionInterface $connectionWrapper;

    protected function maybeReconnect(): self
    {
        if ($this->options->isAutoReconnect() && !$this->isConnected()) {
            for ($i=0;$i<$this->options->getReconnectRetries();++$i) {
                $this->getConnectionWrapper()->reconnect($this->options->getReconnectDelay());
                if (0 < $this->options->getReconnectInterval()) {
                    usleep($this->options->getReconnectInterval());
                }
                if ($this->isConnected()) {
                    break;
                }
            }
        }
        return $this;
    }

    public function connection(): ConnectionInterface
    {
        return $this->getConnectionWrapper();
    }

    protected function close(): self
    {
        $this->getConnectionWrapper()->close();
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
}