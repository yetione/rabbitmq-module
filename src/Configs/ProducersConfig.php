<?php


namespace Yetione\RabbitMQ\Configs;


use Illuminate\Support\Collection;
use Yetione\DTO\DTO;
use Yetione\RabbitMQ\Configs\Providers\ConfigProviderInterface;
use Yetione\RabbitMQ\DTO\Producer;

class ProducersConfig extends AbstractConfig
{
    protected ConnectableConfig $connectableConfig;

    public function __construct(ConnectableConfig $connectableConfig, ConfigProviderInterface $configProvider)
    {
        $this->connectableConfig = $connectableConfig;
        parent::__construct($configProvider);
    }

    protected function parseConfig(): Collection
    {
        $result = collect([]);
        $config = $this->configProvider->read();
        foreach ($config as $name => $parameters) {
            $parameters = array_merge($this->connectableConfig->config()->all(), $parameters);
            if (null !== ($object = DTO::fromArray($parameters, Producer::class))) {
                $result->put($name, $object);
            }
        }
        return $result;
    }
}