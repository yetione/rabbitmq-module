<?php


namespace Yetione\RabbitMQ\Configs\Providers;


class ArrayConfigProvider implements ConfigProviderInterface
{
    protected array $config;

    public function __construct(?array $config)
    {
        $this->config = null === $config ? [] : $config;
    }

    public function read(): array
    {
        return $this->config;
    }
}