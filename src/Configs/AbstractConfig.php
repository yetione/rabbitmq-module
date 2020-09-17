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

    public function config(bool $reload=false): Collection
    {
        if (!isset($this->config) || !$this->configLoaded || $reload) {
            $this->config = $this->parseConfig();
            $this->configLoaded = true;
        }
        return $this->config;
    }

    /**
     * @return Collection
     */
    abstract protected function parseConfig(): Collection;
}