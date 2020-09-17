<?php


namespace Yetione\RabbitMQ\Configs;


use Illuminate\Support\Collection;


class ConnectableConfig extends AbstractConfig
{
    public const AUTO_RECONNECT = 'auto_reconnect';
    public const RECONNECT_RETRIES = 'reconnect_retries';
    public const RECONNECT_DELAY = 'reconnect_delay';

    public array $availableKeys = [

        self::AUTO_RECONNECT,
        self::RECONNECT_RETRIES,
        self::RECONNECT_DELAY
    ];

    protected function parseConfig(): Collection
    {
        $result = collect([]);
        $config = $this->configProvider->read();
        foreach ($this->availableKeys as $key) {
            if (isset($config[$key])) {
                $result->put($key, $config[$key]);
            }
        }
        return $result;
    }
}