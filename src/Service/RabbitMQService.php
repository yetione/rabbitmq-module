<?php


namespace Yetione\RabbitMQ\Service;


use Yetione\DTO\Exception\SerializerException;
use Yetione\DTO\Serializer;
use Exception;
use OutOfBoundsException;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Yetione\RabbitMQ\Connection\ConnectionWrapper;
use Yetione\RabbitMQ\Consumer\AbstractConsumer;
use Yetione\RabbitMQ\Exception\ConnectionRestoredException;

class RabbitMQService
{
    /**
     * @var ConnectionWrapper[]
     */
    protected array $connections = [];

    /**
     * @var int
     */
    protected int $consumerErrorStatusCode = 3;

    protected ConfigService $configService;
    protected Serializer $serializer;


    public function __construct(ConfigService $configService, Serializer $serializer)
    {
        $this->configService = $configService;
        $this->serializer = $serializer;
    }

    /**
     * @param string $name
     * @param string $connectionOptions
     * @return ConnectionWrapper|null
     */
    public function getConnection(string $name, string $connectionOptions = 'default'): ?ConnectionWrapper
    {
        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($connectionOptions);
        }
        return $this->connections[$name];
    }

    /**
     * @param string|null $connectionConfigName
     * @return ConnectionWrapper|null
     */
    public function createConnection(string $connectionConfigName = 'default'): ?ConnectionWrapper
    {
        try {
            $oConnectionOptions = $this->configService->getConnectionsOptions($connectionConfigName);
        } catch (OutOfBoundsException $e) {
            // TODO: Log
            return null;
        }
        $aNodes = $this->configService->getNodes();
        $aNodesConnections = [];
        foreach ($aNodes as $oNode) {
            try {
                $aTemp = $this->serializer->toArray($oNode);
            } catch (SerializerException $e) {
                // TODO: Log
                return null;
            }
            $aTemp['credentials']['user'] = $aTemp['credentials']['username'];
            $aTemp = array_merge($aTemp, $aTemp['credentials']);
            unset($aTemp['credentials']);
            $aNodesConnections[] = $aTemp;
        }
        try {
            $aConnectionOptions = $this->serializer->toArray($oConnectionOptions);
        } catch (SerializerException $e) {
            // TODO: Log
            return null;
        }
        try {
            $oRabbitConnection = AMQPStreamConnection::create_connection($aNodesConnections, $aConnectionOptions);
        } catch (Exception $e) {
            // TODO: Log
            return null;
        }
        return new ConnectionWrapper($oRabbitConnection);
    }

    /**
     * @param ConnectionWrapper $connection
     * @param bool $reconnect
     * @return bool
     * @throws ConnectionRestoredException
     */
    public function checkConnection(ConnectionWrapper $connection, bool $reconnect=true): bool
    {
        $bReconnected = false;
        if (!$connection->isConnectionOpen() && $reconnect) {
            $connection->reconnect();
            $bReconnected = true;
        }
        if (!$connection->isChannelOpen()) {
            $connection->getChannel(true);
            $bReconnected = true;
        }
        $bResult = $connection->isConnectionOpen() && $connection->isChannelOpen();
        if ($bReconnected) {
            throw new ConnectionRestoredException('Connection was restored.', $bResult);
        }
        return $bResult;
    }

    /**
     * @param AbstractConsumer $consumer
     * @param string|null $consumerTag
     * @return int
     */
    public function runConsumer(AbstractConsumer $consumer, string $consumerTag=null): int
    {
        $consumerTag = null === $consumerTag ? uniqid(get_class($consumer), true) : $consumerTag;
        try {
            if (empty($consumer->getConsumerTag())) {
                $consumer->setConsumerTag($consumerTag);
            }
        } catch (\Exception | \Throwable $e) {
            $consumer->setConsumerTag($consumerTag);
        }
        try {
            $iExitCode = $consumer->start();
        } catch (\Exception | \Throwable $e) {
            // TODO: Log
            $iExitCode = $this->getConsumerErrorStatusCode();
        }
        return $iExitCode;
    }

    /**
     * @return int
     */
    public function getConsumerErrorStatusCode(): int
    {
        return $this->consumerErrorStatusCode;
    }

    /**
     * @param int $consumerErrorStatusCode
     * @return RabbitMQService
     */
    public function setConsumerErrorStatusCode(int $consumerErrorStatusCode): self
    {
        $this->consumerErrorStatusCode = $consumerErrorStatusCode;
        return $this;
    }
}