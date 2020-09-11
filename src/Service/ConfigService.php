<?php

namespace Yetione\RabbitMQ\Service;

use Illuminate\Support\Collection;
use Yetione\DTO\DTO;
use OutOfBoundsException;
use Yetione\RabbitMQ\DTO\ConnectionOptions;
use Yetione\RabbitMQ\DTO\Credentials;
use Yetione\RabbitMQ\DTO\Node;

/**
 * Class ConfigService
 * @package Yetione\RabbitMQ\Service
 */
class ConfigService
{
    public const TYPE_CREDENTIALS='credentials';
    public const TYPE_NODES='nodes';
    public const TYPE_CONNECTION_OPTIONS='connection_options';

    /** @var Collection[]  */
    protected array $configs;


    /**
     * @var Credentials[]
     */
    private array $credentials;

    /**
     * @var Node[]
     */
    private array $nodes;

    /**
     * @var ConnectionOptions[]
     */
    private array $connectionsOptions;

    /**
     * @var bool
     */
    private bool $configLoaded = false;

    protected array $configTypes = [
        self::TYPE_CREDENTIALS => Credentials::class,
        self::TYPE_NODES => Node::class,
        self::TYPE_CONNECTION_OPTIONS => ConnectionOptions::class
    ];

    protected ConfigProviderInterface $configProvider;

    public function __construct(ConfigProviderInterface $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * Жуть
     * TODO: Refactor
     */
    protected function loadConfig()
    {
        if (!isset($this->configs)) {
            $this->configs = [];
            $configData = $this->configProvider->read();
            foreach ($this->configTypes as $type=>$itemClass) {
                if (isset($configData[$type])) {
                    $this->configs[$type] = collect([]);
                    foreach ($configData[$type] as $key=>$value) {
                        if (self::TYPE_NODES === $type) { // TODO: Refactor
                            if (null === $this->configs[self::TYPE_CREDENTIALS]->get($value['credentials'])) {
                                continue;
                            }
                            $value['credentials'] = $this->configs[self::TYPE_CREDENTIALS][$value['credentials']];
                        }
                        if (null !== ($object = DTO::fromArray($value, $itemClass))) {
                            $this->configs[$type]->put($key, $object);
                        }
                    }
                }
            }
        }
    }

    public function credentials(): Collection
    {
        return $this->configs[self::TYPE_CREDENTIALS] ?? collect([]);
    }

    public function connectionOptions(): Collection
    {
        return $this->configs[self::TYPE_CONNECTION_OPTIONS] ?? collect([]);
    }

    public function nodes(): Collection
    {
        return $this->configs[self::TYPE_NODES] ?? collect([]);
    }

    /**
     * @param string|null $sName
     * @return Credentials|Credentials[]
     */
    public function getCredentials(?string $sName=null)
    {
        $this->loadConfig();
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