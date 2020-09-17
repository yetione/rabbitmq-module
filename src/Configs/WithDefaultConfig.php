<?php


namespace Yetione\RabbitMQ\Configs;


use Yetione\RabbitMQ\Configs\Providers\ConfigProviderInterface;

trait WithDefaultConfig
{
    protected DefaultConfig $defaultConfig;

    public function __construct(DefaultConfig $defaultConfig, ConfigProviderInterface $configProvider)
    {
        $this->defaultConfig = $defaultConfig;
        parent::__construct($configProvider);
    }
}