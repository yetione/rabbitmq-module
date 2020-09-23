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
            $producer = $this->makeProducerFromConfig($name);
            $exchange = $this->makeExchangeFromConfig($producer->getExchange());
            $this->producers[$alias] = $this->createProducer($producer, $exchange);
        }
        return $this->producers[$alias];
    }

    /**
     * @param string $name
     * @param array $parameters
     * @return ProducerInterface
     * @throws MakeConnectionFailedException
     * @throws MakeProducerFailedException
     */
    public function makeFromArray(string $name, array $parameters): ProducerInterface
    {
        if (!isset($this->producers[$name])) {
            $producer = $this->makeProducerFromArray($parameters);
            $exchange = $this->makeExchangeFromConfig($producer->getExchange());
            $this->producers[$name] = $this->createProducer($producer, $exchange);
        }
        return $this->producers[$name];
    }

    /**
     * @param ProducerDTO $producerOptions
     * @param ExchangeDTO $exchange
     * @return ProducerInterface
     * @throws MakeProducerFailedException
     * @throws MakeConnectionFailedException
     */
    public function createProducer(ProducerDTO $producerOptions, ExchangeDTO $exchange): ProducerInterface
    {
        if (!isset($this->producerTypesMap[$producerOptions->getType()])) {
            throw new MakeProducerFailedException(
                sprintf('Producer\'s type [%s] is not registered', $producerOptions->getType())
            );
        }
        $connectionWrapper = $this->connectionFactory->make(
            $producerOptions->getConnection(),
            $producerOptions->getConnectionAlias()
        );
        $producerClass = $this->producerTypesMap[$producerOptions->getType()];
        return new $producerClass($producerOptions, $exchange, $connectionWrapper, $this->eventDispatcher);
    }

    /**
     * @param array $parameters
     * @return ProducerDTO
     * @throws MakeProducerFailedException
     */
    protected function makeProducerFromArray(array $parameters): ProducerDTO
    {
        if (null !== ($result = $this->producersConfig->make($parameters))) {
            return $result;
        }
        throw new MakeProducerFailedException('Creating producer from array failed');
    }

    /**
     * @param string $name
     * @return ProducerDTO
     * @throws MakeProducerFailedException
     */
    protected function makeProducerFromConfig(string $name): ProducerDTO
    {
        /** @var ProducerDTO $result */
        if (null !== ($result = $this->producersConfig->config()->get($name))) {
            return $result;
        }
        throw new MakeProducerFailedException(sprintf('Producer [%s] is missing', $name));
    }

    /**
     * @param array $parameters
     * @return ExchangeDTO
     * @throws MakeProducerFailedException
     */
    protected function makeExchangeFromArray(array $parameters): ExchangeDTO
    {
        if (null !== ($result = $this->exchangesConfig->make($parameters))) {
            return $result;
        }
        throw new MakeProducerFailedException('Creating exchange from array failed');
    }

    /**
     * @param string $name
     * @return ExchangeDTO
     * @throws MakeProducerFailedException
     */
    protected function makeExchangeFromConfig(string $name): ExchangeDTO
    {
        /** @var ExchangeDTO $exchange */
        if (null !== ($exchange = $this->exchangesConfig->config()->get($name))) {
              return $exchange;
        }
        throw new MakeProducerFailedException(
            sprintf('Exchange [%s] is missing', $name)
        );
    }

    public function makeSafe(string $name, ?string $alias=null): ProducerInterface
    {
        try {
            return $this->make($name, $alias);
        } catch (Exception $e) {
            return $this->nullProducer;
        }
    }

    public function makeFromArraySafe(string $name, array $parameters): ProducerInterface
    {
        try {
            return $this->makeFromArray($name, $parameters);
        } catch (Exception $e) {
            return $this->nullProducer;
        }
    }
}