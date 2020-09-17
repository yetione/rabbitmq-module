<?php


namespace Yetione\RabbitMQ\Configs;


use Illuminate\Support\Collection;

class ConsumersConfig extends AbstractConfig
{
    use WithDefaultConfig;

    protected function parseConfig(): Collection
    {
        $result = collect([]);

        return $result;
    }
}