<?php


namespace Yetione\RabbitMQ\Configs\Providers;


class ArrayConfigProvider implements ConfigProviderInterface
{
    protected array $config;

    public function __construct(?array $config)
    {
        $e='fasd';
        $this->config = null === $config ? [] : $config;
    }

    public function read(): array
    {
        return $this->config;
    }
}