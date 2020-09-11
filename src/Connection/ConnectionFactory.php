<?php


namespace Yetione\RabbitMQ\Connection;


use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\Heartbeat\PCNTLHeartbeatSender;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use Yetione\DTO\DTO;
use Yetione\RabbitMQ\DTO\ConnectionOptions;
use Yetione\RabbitMQ\DTO\Node;
use Yetione\RabbitMQ\Exception\MakeConnectionFailedException;
use Yetione\RabbitMQ\Service\ConfigService;

class ConnectionFactory
{
    /** @var ConnectionInterface[]  */
    protected array $connections = [];

    protected array $nodes;

    protected ConfigService $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
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
        if (null === ($connectionOptions = $this->configService->connectionOptions()->get($options))) {
            throw new MakeConnectionFailedException(sprintf('Connection [%s] is missing', $options));
        }
        if (null === ($connectionOptions = DTO::toArray($connectionOptions))) {
            throw new MakeConnectionFailedException(sprintf('Connection [%s] is broken', $options));
        }
        try {
            $AMQPConnection = AMQPStreamConnection::create_connection($this->getNodes(), $connectionOptions);
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
            foreach ($this->configService->nodes()->all() as $node) {
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