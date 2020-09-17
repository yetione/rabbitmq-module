<?php


namespace Yetione\RabbitMQ\Connection;


use Closure;
use PhpAmqpLib\Connection\AbstractConnection as AMQPAbstractConnection;
use Yetione\RabbitMQ\Exception\ConnectionIsNotSetupException;

class ResolvableConnection extends AbstractConnection
{
    /**
     * @var Closure
     */
    protected Closure $connectionResolver;

    public function __construct(Closure $connectionResolver)
    {
        $this->connectionResolver = $connectionResolver;
        $this->connect();
    }

    /**
     * @throws ConnectionIsNotSetupException
     */
    public function close()
    {
        $this->destroy();
    }

    public function safeClose()
    {
        try {
            $this->close();
        } catch (ConnectionIsNotSetupException $e) {
        }
    }

    public function connect()
    {
        $this->setup($this->resolveConnection());
    }

    public function reconnect(int $delay=0)
    {
        $this->safeClose();
        if (0 < $delay) {
            usleep($delay);
        }
        $this->connect();
    }

    protected function resolveConnection(): AMQPAbstractConnection
    {
        return ($this->connectionResolver)();
    }


}