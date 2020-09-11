<?php


namespace Yetione\RabbitMQ\Configs;


use Illuminate\Support\Collection;
use Yetione\RabbitMQ\Configs\Providers\ConfigProviderInterface;

abstract class AbstractConfig
{
    protected Collection $config;

    protected ConfigProviderInterface $configProvider;

    protected bool $configLoaded;

    public function __construct(ConfigProviderInterface $configProvider)
    {
        $this->configProvider = $configProvider;
        $this->configLoaded = false;
    }

    public function config(): Collection
    {
        if (!isset($this->config)) {
            $this->loadConfig();
        }
        return $this->config;
    }

    /**
     * @param bool $reload
     * @return bool
     */
    abstract protected function loadConfig(bool $reload=false): bool;
}