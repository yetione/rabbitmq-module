<?php


namespace Yetione\RabbitMQ\Configs\Providers;


interface ConfigProviderInterface
{
    /**
     * Возвращает массив с конфигами
     *
     * @return array
     */
    public function read(): array;
}