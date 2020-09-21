<?php


namespace Yetione\RabbitMQ\Producer;


use Exception;
use Yetione\RabbitMQ\Configs\ExchangesConfig;
use Yetione\RabbitMQ\Configs\ProducersConfig;
use Yetione\RabbitMQ\Connection\ConnectionFactory;
use Yetione\RabbitMQ\DTO\Exchange as ExchangeDTO;
use Yetione\RabbitMQ\DTO\Producer as ProducerDTO;
use Yetione\RabbitMQ\Event\EventDispatcherInterface;
use Yetione\RabbitMQ\Exception\InvalidProducerTypeException;
use Yetione\RabbitMQ\Exception\MakeConnectionFailedException;
use Yetione\RabbitMQ\Exception\MakeProducerFailedException;

class ProducerFactory
{
    /** @var ProducerInterface[]  */
    protected array $producers = [];

    protected ProducersConfig $producersConfig;

    protected ExchangesConfig $exchangesConfig;

    protected ConnectionFactory $connectionFactory;

    protected EventDispatcherInterface $eventDispatcher;

    protected array $producerTypesMap = [];

    protected NullProducer $nullProducer;

    public function __construct(
        ProducersConfig $producersConfig,
        ExchangesConfig $exchangesConfig,
        ConnectionFactory $connectionFactory,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->producersConfig = $producersConfig;
        $this->exchangesConfig = $exchangesConfig;
        $this->connectionFactory = $connectionFactory;
        $this->eventDispatcher = $eventDispatcher;

        $this->nullProducer = new NullProducer();
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

    /**
     * @param string $name
     * @param string|null $alias
     * @return ProducerInterface
     * @throws MakeConnectionFailedException
     * @throws MakeProducerFailedException
     */
    public function make(string $name, ?string $alias=null): ProducerInterface
    {
        $alias = $alias ?: $name;
        if (!isset($this->producers[$alias])) {
            $this->producers[$alias] = $this->createProducer($name);
        }
        return $this->producers[$alias];
    }

    /**
     * @param string $name
     * @return ProducerInterface
     * @throws MakeProducerFailedException
     * @throws MakeConnectionFailedException
     */
    protected function createProducer(string $name): ProducerInterface
    {
        /** @var ProducerDTO $producerOptions */
        if (null === ($producerOptions = $this->producersConfig->config()->get($name))) {
            throw new MakeProducerFailedException(sprintf('Producer [%s] is missing', $name));
        }
        if (!isset($this->producerTypesMap[$producerOptions->getType()])) {
            throw new MakeProducerFailedException(
                sprintf('Producer\'s type [%s] is not registered', $producerOptions->getType())
            );
        }
        /** @var ExchangeDTO $exchange */
        if (null === ($exchange = $this->exchangesConfig->config()->get($producerOptions->getExchange()))) {
            throw new MakeProducerFailedException(
                sprintf('Exchange [%s] is missing', $producerOptions->getExchange())
            );
        }

        $connectionWrapper = $this->connectionFactory->make(
            $producerOptions->getConnection(),
            $producerOptions->getConnectionAlias()
        );
        $producerClass = $this->producerTypesMap[$producerOptions->getType()];
        return new $producerClass($producerOptions, $exchange, $connectionWrapper, $this->eventDispatcher);
    }

    public function makeSafe(string $name, ?string $alias): ProducerInterface
    {
        try {
            return $this->make($name, $alias);
        } catch (Exception $e) {
            return $this->nullProducer;
        }
    }
}