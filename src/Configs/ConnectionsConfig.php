<?php


namespace Yetione\RabbitMQ\Configs;


use Yetione\DTO\DTO;
use Yetione\RabbitMQ\DTO\ConnectionOptions;
use Yetione\RabbitMQ\DTO\Credentials;
use Yetione\RabbitMQ\DTO\Node;

class ConnectionsConfig extends AbstractConfig
{
    public const TYPE_CREDENTIALS='credentials';
    public const TYPE_NODES='nodes';
    public const TYPE_CONNECTION_OPTIONS='connection_options';

    protected array $configTypes = [
        self::TYPE_CREDENTIALS => Credentials::class,
        self::TYPE_NODES => Node::class,
        self::TYPE_CONNECTION_OPTIONS => ConnectionOptions::class
    ];

    protected function loadConfig(bool $reload = false): bool
    {
        if (!$this->configLoaded || $reload) {
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
            $this->config = $result;
            $this->configLoaded = true;
            return true;
        }
        return false;
    }
}