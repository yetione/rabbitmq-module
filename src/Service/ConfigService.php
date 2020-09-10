<?php

namespace Yetione\RabbitMQ\Service;

use Yetione\DTO\Exception\SerializerException;
use Yetione\DTO\Serializer;;
use OutOfBoundsException;
use Yetione\RabbitMQ\DTO\ConnectionOptions;
use Yetione\RabbitMQ\DTO\Credentials;
use Yetione\RabbitMQ\DTO\Node;

/**
 * TODO: Config provider
 * Class ConfigService
 * @package Yetione\RabbitMQ\Service
 */
class ConfigService
{
    /**
     * @var Credentials[]
     */
    private array $credentials = [];

    /**
     * @var Node[]
     */
    private array $nodes = [];

    /**
     * @var ConnectionOptions[]
     */
    private array $connectionsOptions = [];

    /**
     * @var bool
     */
    private bool $configLoaded = false;

    /**
     * @var Serializer
     */
    private Serializer $serializer;

    protected ConfigProviderInterface $configProvider;

    public function __construct(Serializer $serializer, ConfigProviderInterface $configProvider)
    {
        $this->serializer = $serializer;
        $this->configProvider = $configProvider;
    }

    protected function loadConfig()
    {
        $aRabbitData = $this->configProvider->read();

        if (isset($aRabbitData['credentials'])) {
            foreach ($aRabbitData['credentials'] as $sName => $aData) {
                try {
                    $oCredentials = $this->serializer->fromArray($aData, Credentials::class);
                } catch (SerializerException $e) {
                    continue;
                }
                $this->credentials[$sName] = $oCredentials;
            }
        }
        if (isset($aRabbitData['connection_options'])) {
            foreach ($aRabbitData['connection_options'] as $sName => $aData) {
                try {
                    $oConnectionOptions = $this->serializer->fromArray($aData, ConnectionOptions::class);
                } catch (SerializerException $e) {
                    continue;
                }
                $this->connectionsOptions[$sName] = $oConnectionOptions;
            }
        }

        if (isset($aRabbitData['nodes'])) {
            foreach ($aRabbitData['nodes'] as $aData) {
                if (isset($aData['credentials'])) {
                    if (!isset($aRabbitData['credentials'][$aData['credentials']])) {
                        continue;
                    }
                    $aData['credentials'] = $aRabbitData['credentials'][$aData['credentials']];
                }
                try {
                    $oNode = $this->serializer->fromArray($aData, Node::class);
                } catch (SerializerException $e) {
                    continue;
                }
                $this->nodes[] = $oNode;
            }
        }
        $this->configLoaded = true;
    }

    /**
     * @param string|null $sName
     * @return Credentials|Credentials[]
     * @throws OutOfBoundsException
     */
    public function getCredentials(?string $sName = null)
    {
        if (!$this->configLoaded) {
            $this->loadConfig();
        }
        if (null === $sName) {
            return $this->credentials;
        }
        if (!isset($this->credentials[$sName])) {
            throw new OutOfBoundsException('Credentials '.$sName.' is not exist.');
        }
        return $this->credentials[$sName];
    }

    /**
     * @param string|null $sName
     * @return ConnectionOptions|ConnectionOptions[]
     * @throws OutOfBoundsException
     */
    public function getConnectionsOptions(?string $sName = null)
    {
        if (!$this->configLoaded) {
            $this->loadConfig();
        }
        if (null === $sName) {
            return $this->connectionsOptions;
        }
        if (!isset($this->connectionsOptions[$sName])) {
            throw new OutOfBoundsException('Connection '.$sName.' is not exist.');
        }
        return $this->connectionsOptions[$sName];
    }

    /**
     * @return Node[]
     */
    public function getNodes(): array
    {
        if (!$this->configLoaded) {
            $this->loadConfig();
        }
        return $this->nodes;
    }
}