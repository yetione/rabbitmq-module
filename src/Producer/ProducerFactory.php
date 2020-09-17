<?php


namespace Yetione\RabbitMQ\Producer;


use Yetione\RabbitMQ\Configs\ProducersConfig;
use Yetione\RabbitMQ\Exception\InvalidProducerTypeException;

class ProducerFactory
{
    /** @var ProducerInterface[]  */
    protected array $producers = [];

    protected ProducersConfig $config;

    protected array $producerTypesMap = [];

    public function __construct(ProducersConfig $config)
    {
        $this->config;
    }

    public function addProducerType(string $type, string $producerClass): void
    {
        if (!class_exists($producerClass) || !is_subclass_of($producerClass, ProducerInterface::class)) {
            throw new InvalidProducerTypeException(
                sprintf('Producers type\'s [%s] class [%].', $type, $producerClass)
            );
        }
        $this->producerTypesMap[$type] = $producerClass;
    }
}