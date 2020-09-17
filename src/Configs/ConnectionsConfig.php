<?php


namespace Yetione\RabbitMQ\Configs;


use Illuminate\Support\Collection;
use Yetione\DTO\DTO;
use Yetione\RabbitMQ\DTO\Connection;
use Yetione\RabbitMQ\DTO\Credentials;
use Yetione\RabbitMQ\DTO\Node;

class ConnectionsConfig extends AbstractConfig
{
    public const TYPE_CREDENTIALS='credentials';
    public const TYPE_NODES='nodes';
    public const TYPE_CONNECTIONS='connections';
    public const CONNECTION_TYPES = 'connection_types';

    protected array $configTypes = [
        self::TYPE_CREDENTIALS => Credentials::class,
        self::TYPE_NODES => Node::class,
        self::TYPE_CONNECTIONS => Connection::class
    ];

    protected function parseConfig(): Collection
    {
        $result = collect([]);
        $config = $this->configProvider->read();
        foreach ($this->configTypes as $type => $itemClass) {
            if (!isset($config[$type])) {
                continue;
            }
            $items = collect([]);
            foreach ($config[$type] as $key=>$value) {
                switch ($type) {
                    case self::TYPE_NODES:
                        if (isset($config[self::TYPE_CREDENTIALS],
                            $config[self::TYPE_CREDENTIALS][$value['credentials']])) {
                            $value['credentials'] = $config[self::TYPE_CREDENTIALS][$value['credentials']];
                        }
                        break;
                }
                if (null !== ($object = DTO::fromArray($value, $itemClass))) {
                    $items->put($key, $object);
                }
            }
            $result->put($type, $items);
        }
        if (isset($config[self::CONNECTION_TYPES])) {
            $result->put(self::CONNECTION_TYPES, $config[self::CONNECTION_TYPES]);
        }
        return $result;
    }
}