<?php


namespace Yetione\RabbitMQ\Service;


interface ConfigProviderInterface
{
    /**
     * Возвращает массив с конфигами
     *
     * @return array
     */
    public function read(): array;
}