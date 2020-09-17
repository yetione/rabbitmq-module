<?php


namespace Yetione\RabbitMQ\Connection;


use Exception;
use InvalidArgumentException;
use PhpAmqpLib\Connection\AbstractConnection;
use Yetione\DTO\DTO;
use Yetione\RabbitMQ\Configs\ConnectionsConfig;
use Yetione\RabbitMQ\DTO\Connection;
use Yetione\RabbitMQ\DTO\Node;
use Yetione\RabbitMQ\Exception\InvalidConnectionTypeException;
use Yetione\RabbitMQ\Exception\MakeConnectionFailedException;
use Yetione\RabbitMQ\Constant\Connection as ConnectionEnum;
use PhpAmqpLib\Connection\AbstractConnection as AMQPAbstractConnection;

class ConnectionFactory
{
    /** @var ConnectionInterface[]  */
    protected array $connections = [];

    protected array $nodes;

    protected ConnectionsConfig $config;

    protected array $connectionTypesMap = [];

    public function __construct(ConnectionsConfig $config)
    {
        $this->config = $config;
        $this->registerConnectionTypes();
    }

    protected function registerConnectionTypes(): void
    {
        $connectionTypes = $this->config->config()->get(ConnectionsConfig::CONNECTION_TYPES, []);
        foreach ($connectionTypes as $type => $connectionClass) {
            $this->addConnectionType($type, $connectionClass);
        }
    }

    public function addConnectionType(string $type, string $connectionClass): void
    {
        if (!class_exists($connectionClass) || !is_subclass_of($connectionClass, AMQPAbstractConnection::class)) {
            throw new InvalidConnectionTypeException(
                sprintf('Connection type\'s [%s] class [%s] must exists and extend [%s]',
                    $type, $connectionClass, AMQPAbstractConnection::class)
            );
        }
        $this->connectionTypesMap[$type] = $connectionClass;
    }

    /**
     * @param string $name
     * @param string|null $alias
     * @return ConnectionInterface
     * @throws MakeConnectionFailedException
     */
    public function make(string $name, ?string $alias=null): ConnectionInterface
    {
        $alias = $alias ?: $name;
        if (!isset($this->connections[$alias])) {
            $this->connections[$alias] = $this->createConnection($name);
        }
        return $this->connections[$alias];
    }

    /**
     * @param string $name
     * @return ConnectionInterface
     * @throws MakeConnectionFailedException
     */
    protected function createConnection(string $name): ConnectionInterface
    {
        /** @var Connection $connectionOptions */
        if (null === ($connectionOptions =
                $this->config->config()->get(ConnectionsConfig::TYPE_CONNECTIONS, collect())->get($name))) {
            throw new MakeConnectionFailedException(sprintf('Connection [%s] is missing', $name));
        }
//        if (null === ($connectionOptions = DTO::toArray($connectionOptions))) {
//            throw new MakeConnectionFailedException(sprintf('Connection [%s] is broken', $name));
//        }
        if (!isset($this->connectionTypesMap[$connectionOptions->getConnectionType()])) {
            throw new MakeConnectionFailedException(
                sprintf('Connection type [%s] is not registered', $connectionOptions->getConnectionType())
            );
        }
        try {
            $AMQPConnection = $this->createAMQPConnection($connectionOptions);
        } catch (Exception $e) {
            throw new MakeConnectionFailedException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
        return new ConnectionWrapper($AMQPConnection);
    }

    /**
     * @param Connection $connectionOptions
     * @return AbstractConnection
     * @throws Exception
     */
    protected function createAMQPConnection(Connection $connectionOptions): AbstractConnection
    {
        $nodes = $this->getNodes();
        if (1 < count($nodes)) {
            throw new InvalidArgumentException('An array of hosts are required when attempting to create a connection.');
        }
        $connectionType = $connectionOptions->getConnectionType();
        $connectionClass = $this->connectionTypesMap[$connectionType];
        foreach ($nodes as $node) {
            switch ($connectionType) {
                case ConnectionEnum::TYPE_STREAM_LAZY:
                case ConnectionEnum::TYPE_STREAM_NORMAL:
                    try {
                        return new $connectionClass(
                            $node['host'], $node['port'], $node['user'], $node['password'], $node['vhost'],
                            $connectionOptions->isInsist(),
                            $connectionOptions->getLoginMethod(),
                            $connectionOptions->getLoginResponse(),
                            $connectionOptions->getLocale(),
                            $connectionOptions->getConnectionTimeout(),
                            $connectionOptions->getReadWriteTimeout(),
                            $connectionOptions->getContext(),
                            $connectionOptions->isKeepalive(),
                            $connectionOptions->getHeartbeat(),
                            $connectionOptions->getChannelRpcTimeout(),
                            $connectionOptions->getSslProtocol(),
                        );
                    } catch (Exception $e) {
                        $lastException = $e;
                    }
                    break;
                case ConnectionEnum::TYPE_SOCKET_NORMAL:
                case ConnectionEnum::TYPE_SOCKET_LAZY:
                    try {
                        return new $connectionClass(
                            $node['host'], $node['port'], $node['user'], $node['password'], $node['vhost'],
                            $connectionOptions->isInsist(),
                            $connectionOptions->getLoginMethod(),
                            $connectionOptions->getLoginResponse(),
                            $connectionOptions->getLocale(),
                            $connectionOptions->getReadTimeout(),
                            $connectionOptions->isKeepalive(),
                            $connectionOptions->getWriteTimeout(),
                            $connectionOptions->getHeartbeat(),
                            $connectionOptions->getChannelRpcTimeout()
                        );
                    } catch (Exception $e) {
                        $lastException = $e;
                    }
                    break;
                case ConnectionEnum::TYPE_STREAM_SSL:
                    try {
                        return new $connectionClass(
                            $node['host'], $node['port'], $node['user'], $node['password'], $node['vhost'],
                            $connectionOptions->getSslOptions(),
                            [
                                'insist'=>$connectionOptions->isInsist(),
                                'login_method'=>$connectionOptions->getLoginMethod(),
                                'login_response'=>$connectionOptions->getLoginResponse(),
                                'locale'=>$connectionOptions->getLocale(),
                                'connection_timeout'=>$connectionOptions->getConnectionTimeout(),
                                'read_write_timeout'=>$connectionOptions->getReadWriteTimeout(),
                                'keepalive'=>$connectionOptions->isKeepalive(),
                                'heartbeat'=>$connectionOptions->getHeartbeat(),
                                'channel_rpc_timeout'=>$connectionOptions->getChannelRpcTimeout()
                            ],
                            $connectionOptions->getSslProtocol(),
                        );
                    } catch (Exception $e) {
                        $lastException = $e;
                    }
                    break;
                default:
                    $lastException = new Exception(
                        sprintf('Connection type [%s] is not registered', $connectionOptions->getConnectionType())
                    );
                    break;
            }
        }
        throw $lastException ?? new Exception('Connection creation failed with unknown error.');
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