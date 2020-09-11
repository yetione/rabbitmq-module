<?php


namespace Yetione\RabbitMQ\Connection;


use Exception;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\Heartbeat\PCNTLHeartbeatSender;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use Yetione\DTO\DTO;
use Yetione\RabbitMQ\Configs\ConnectionsConfig;
use Yetione\RabbitMQ\Constant\Connection;
use Yetione\RabbitMQ\DTO\ConnectionOptions;
use Yetione\RabbitMQ\DTO\Node;
use Yetione\RabbitMQ\Exception\MakeConnectionFailedException;

class ConnectionFactory
{
    /** @var ConnectionInterface[]  */
    protected array $connections = [];

    protected array $nodes;

    protected ConnectionsConfig $config;

    protected array $connectionTypesMap = [
        Connection::TYPE_NORMAL => AMQPStreamConnection::class,
        Connection::TYPE_LAZY => AMQPLazyConnection::class
    ];

    public function __construct(ConnectionsConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $options
     * @param string|null $name
     * @return ConnectionInterface
     * @throws MakeConnectionFailedException
     */
    public function make(string $options, ?string $name=null): ConnectionInterface
    {
        $name = $name ?: $options;
        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($options);
        }
        return $this->connections[$name];
    }

    /**
     * @param string $options
     * @return ConnectionInterface
     * @throws MakeConnectionFailedException
     */
    protected function createConnection(string $options): ConnectionInterface
    {
        /** @var ConnectionOptions $connectionOptions */
        if (null === ($connectionOptions =
                $this->config->config()->get(ConnectionsConfig::TYPE_CONNECTION_OPTIONS, collect())->get($options))) {
            throw new MakeConnectionFailedException(sprintf('Connection [%s] is missing', $options));
        }
        if (null === ($connectionOptions = DTO::toArray($connectionOptions))) {
            throw new MakeConnectionFailedException(sprintf('Connection [%s] is broken', $options));
        }
        if (!isset($this->connectionTypesMap[$connectionOptions['connection_type']])
            || !class_exists($this->connectionTypesMap[$connectionOptions['connection_type']])) {
            throw new MakeConnectionFailedException(
                sprintf('Connection type [%s] is not registered', $connectionOptions['connection_type'])
            );
        }
        /** @var AbstractConnection|string $connectionClass */
        $connectionClass = $this->connectionTypesMap[$connectionOptions['connection_type']];
        try {
            $AMQPConnection = $connectionClass::create_connection($this->getNodes(), $connectionOptions);
        } catch (Exception $e) {
            throw new MakeConnectionFailedException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
        if (isset($connectionOptions['heartbeat']) && 0 < $connectionOptions['heartbeat']) {
            try {
                $heartbeatSender = new PCNTLHeartbeatSender($AMQPConnection);
                $heartbeatSender->register();
            } catch (AMQPRuntimeException $e) {}
        }
        return new ConnectionWrapper($AMQPConnection);
    }

    protected function getNodes(): array
    {
        if (!isset($this->nodes)) {
            $this->nodes = [];
            /** @var Node $node */
            foreach ($this->config->config()->get(ConnectionsConfig::TYPE_NODES, collect())->all() as $node) {
                if (null !== ($node = DTO::toArray($node))) {
                    $node['credentials']['user'] = $node['credentials']['username'];
                    $node = array_merge($node, $node['credentials']);
                    unset($node['credentials'], $node['username']);
                    $this->nodes[] = $node;
                }
            }
        }
        return $this->nodes;
    }

}