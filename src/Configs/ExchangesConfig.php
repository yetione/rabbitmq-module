<?php


namespace Yetione\RabbitMQ\Configs;


use Illuminate\Support\Collection;
use Yetione\DTO\DTO;
use Yetione\RabbitMQ\DTO\Exchange;

class ExchangesConfig extends AbstractConfig
{
    use WithDefaultConfig;

    protected function parseConfig(): Collection
    {
        $result = collect([]);
        $config = $this->configProvider->read();
        foreach ($config as $name => $parameters) {
            if (null !== ($object = DTO::fromArray($parameters, Exchange::class))) {
                $result->put($name, $object);
            }
        }
        return $result;
    }
}