<?php

namespace Yetione\RabbitMQ\Service;

use Illuminate\Support\Collection;
use Yetione\DTO\DTO;
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
                            if (!isset($configData[self::TYPE_CREDENTIALS]) ||
                                !isset($configData[self::TYPE_CREDENTIALS][$value['credentials']])) {
                                continue;
                            }
                            $value['credentials'] = $configData[self::TYPE_CREDENTIALS][$value['credentials']];
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
        $this->loadConfig();
        return $this->configs[self::TYPE_CREDENTIALS] ?? collect([]);
    }

    public function connectionOptions(): Collection
    {
        $this->loadConfig();
        return $this->configs[self::TYPE_CONNECTION_OPTIONS] ?? collect([]);
    }

    public function nodes(): Collection
    {
        $this->loadConfig();
        return $this->configs[self::TYPE_NODES] ?? collect([]);
    }
}