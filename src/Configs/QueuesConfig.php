<?php


namespace Yetione\RabbitMQ\Configs;


use Illuminate\Support\Collection;
use Yetione\DTO\DTO;
use Yetione\RabbitMQ\DTO\Queue;

class QueuesConfig extends AbstractConfig
{
    use WithDefaultConfig;

    protected function parseConfig(): Collection
    {
        $result = collect([]);
        $config = $this->configProvider->read();
        foreach ($config as $name => $parameters) {
            $parameters = array_merge(
                $this->defaultConfig->config()->get(DefaultConfig::QUEUE, collect([]))->all(),
                $parameters
            );
            if (null !== ($object = DTO::fromArray($parameters, Queue::class))) {
                $result->put($name, $object);
            }
        }
        return $result;
    }
}