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
            if (null !== ($object = $this->make($this->buildParameters(DefaultConfig::QUEUE, $parameters)))) {
                $result->put($name, $object);
            }
        }
        return $result;
    }

    public function make(array $parameters): ?Queue
    {
        /** @var Queue|null $result */
        $result = DTO::fromArray($parameters, Queue::class);
        return $result;
    }
}