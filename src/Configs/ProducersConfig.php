<?php


namespace Yetione\RabbitMQ\Configs;


use Illuminate\Support\Collection;
use Yetione\DTO\DTO;
use Yetione\RabbitMQ\Configs\Providers\ConfigProviderInterface;
use Yetione\RabbitMQ\DTO\Producer;

class ProducersConfig extends AbstractConfig
{
    protected DefaultConfig $defaultConfig;

    public function __construct(DefaultConfig $defaultConfig, ConfigProviderInterface $configProvider)
    {
        $this->defaultConfig = $defaultConfig;
        parent::__construct($configProvider);
    }

    protected function parseConfig(): Collection
    {
        $result = collect([]);
        $config = $this->configProvider->read();
        foreach ($config as $name => $parameters) {
            $parameters = array_merge(
                $this->defaultConfig->config()->get(DefaultConfig::CONNECTABLE, collect([]))->all(),
                $parameters
            );
            if (null !== ($object = DTO::fromArray($parameters, Producer::class))) {
                $result->put($name, $object);
            }
        }
        return $result;
    }
}