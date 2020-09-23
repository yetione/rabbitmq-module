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
            if (null !== ($object = $this->make($parameters))) {
                $result->put($name, $object);
            }
        }
        return $result;
    }

    public function make(array $parameters): ?Exchange
    {
        /** @var Exchange|null $result */
        $result = DTO::fromArray($parameters, Exchange::class);
        return $result;
    }
}