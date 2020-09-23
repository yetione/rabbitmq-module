<?php


namespace Yetione\RabbitMQ\Configs;


use Illuminate\Support\Collection;
use Yetione\DTO\DTO;
use Yetione\RabbitMQ\DTO\Producer;

class ProducersConfig extends AbstractConfig
{
    use WithDefaultConfig;

    protected function parseConfig(): Collection
    {
        $result = collect([]);
        $config = $this->configProvider->read();
        foreach ($config as $name => $parameters) {
            $parameters = array_merge(
                $this->defaultConfig->config()->get(DefaultConfig::CONNECTABLE, collect([]))->all(),
                $parameters
            );
            if (null !== ($object = $this->make($parameters))) {
                $result->put($name, $object);
            }
        }
        return $result;
    }

    public function make(array $parameters): ?Producer
    {
        /** @var Producer|null $result */
        $result = DTO::fromArray($parameters, Producer::class);
        return $result;
    }
}