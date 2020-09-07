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
    private $credentials = [];

    /**
     * @var Node[]
     */
    private $nodes = [];

    /**
     * @var ConnectionOptions[]
     */
    private $connectionsOptions = [];

    /**
     * @var bool
     */
    private $configLoaded = false;

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    protected function readConfig(): array
    {
        // TODO: Refactor
        try {
            $oConfig = $this->getConfigService();
        } catch (ServiceNotFoundException $e) {
            return;
        }
        return $oConfig->get('rabbitmq', []);
    }


    protected function loadConfig()
    {
        $aRabbitData = $this->readConfig();

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