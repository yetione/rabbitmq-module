<?php


namespace Yetione\RabbitMQ\Configs;


use Illuminate\Support\Collection;

class DefaultConfig extends AbstractConfig
{
    public const CONNECTABLE = 'connectable';
    public const CONNECTION = 'connection';
    public const PRODUCER = 'producer';
    public const CONSUMER = 'consumer';
    public const EXCHANGE = 'exchange';
    public const QUEUE = 'queue';

    public const CONNECTABLE_AUTO_RECONNECT = 'auto_reconnect';
    public const CONNECTABLE_RECONNECT_RETRIES = 'reconnect_retries';
    public const CONNECTABLE_RECONNECT_DELAY = 'reconnect_delay';
    public const CONNECTABLE_RECONNECT_INTERVAL = 'reconnect_interval';

    public array $availableKeys = [
        self::CONNECTABLE => [
            self::CONNECTABLE_AUTO_RECONNECT,
            self::CONNECTABLE_RECONNECT_RETRIES,
            self::CONNECTABLE_RECONNECT_DELAY,
            self::CONNECTABLE_RECONNECT_INTERVAL
        ],
        self::CONNECTION => [],
        self::PRODUCER => [],
        self::CONSUMER => [],
        self::EXCHANGE => [],
        self::QUEUE => []
    ];

    protected function parseConfig(): Collection
    {
        $result = collect([]);
        $config = $this->configProvider->read();
        foreach ($this->availableKeys as $key => $availableKeys) {
            if (isset($config[$key])) {
                $element = $config[$key];
                if (!empty($availableKeys)) {
                    foreach ($availableKeys as $availableKey) { // TODO: Refactor to array_* functions
                        if (!isset($element[$availableKey])) {
                            unset($element[$availableKey]);
                        }
                    }
                }
                $result->put($key, collect($element));
            }
        }
        return $result;
    }
}