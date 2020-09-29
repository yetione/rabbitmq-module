<?php


namespace Yetione\RabbitMQ\Consumer;


use Exception;
use Yetione\RabbitMQ\Configs\ConsumersConfig;
use Yetione\RabbitMQ\Connection\ConnectionFactory;
use Yetione\RabbitMQ\Event\EventDispatcherInterface;
use Yetione\RabbitMQ\Exception\InvalidConsumerTypeException;
use Yetione\RabbitMQ\DTO\Consumer as ConsumerDTO;
use Yetione\RabbitMQ\Exception\MakeConnectionFailedException;
use Yetione\RabbitMQ\Exception\MakeConsumerFailedException;
use Yetione\RabbitMQ\Logger\LoggerProviderInterface;

class ConsumerFactory
{
    protected ConsumersConfig $consumersConfig;

    protected ConnectionFactory $connectionFactory;

    protected EventDispatcherInterface $eventDispatcher;

    protected LoggerProviderInterface $loggerProvider;

    protected array $consumerTypesMap;

    /** @var ConsumerInterface[] */
    protected array $consumers;

    public function __construct(
        ConsumersConfig $consumersConfig,
        ConnectionFactory $connectionFactory,
        EventDispatcherInterface $eventDispatcher,
        LoggerProviderInterface $loggerProvider
    )
    {
        $this->consumersConfig = $consumersConfig;
        $this->connectionFactory = $connectionFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->loggerProvider = $loggerProvider;
        $this->consumerTypesMap = [];
    }

    public function addConsumerType(string $type, string $consumerClass): void
    {
        if (!class_exists($consumerClass) || !is_subclass_of($consumerClass, ConsumerInterface::class)) {
            throw new InvalidConsumerTypeException(
                sprintf('Consumer type\'s [%s] class [%].', $type, $consumerClass)
            );
        }
        $this->consumerTypesMap[$type] = $consumerClass;
    }

    /**
     * @param string $name
     * @param string|null $alias
     * @return ConsumerInterface
     * @throws MakeConnectionFailedException
     * @throws MakeConsumerFailedException
     */
    public function make(string $name, ?string $alias=null): ConsumerInterface
    {
        $alias = $alias ?: $name;
        if (!isset($this->consumers[$alias])) {
            $this->consumers[$alias] = $this->createConsumer($this->makeConsumerFromConfig($name));
        }
        return $this->consumers[$alias];
    }

    /**
     * @param string $name
     * @param string|null $alias
     * @return ConsumerInterface|null
     */
    public function makeSafe(string $name, ?string $alias=null): ?ConsumerInterface
    {
        try {
            return $this->make($name, $alias);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param ConsumerDTO $consumerOptions
     * @return ConsumerInterface
     * @throws MakeConsumerFailedException
     * @throws MakeConnectionFailedException
     */
    public function createConsumer(ConsumerDTO $consumerOptions): ConsumerInterface
    {
        if (!isset($this->consumerTypesMap[$consumerOptions->getType()])) {
            throw new MakeConsumerFailedException(
                sprintf('Consumer\'s type [%s] is not registered', $consumerOptions->getType())
            );
        }
        $connectionWrapper = $this->connectionFactory->make(
            $consumerOptions->getConnection(),
            $consumerOptions->getConnectionAlias()
        );
        $consumerClass = $this->consumerTypesMap[$consumerOptions->getType()];
        /** @var AbstractConsumer $result */
        $result = new $consumerClass($consumerOptions, $connectionWrapper, $this->eventDispatcher);
        $result->setLoggerProvider($this->loggerProvider);
        return $result;
    }

    /**
     * @param string $name
     * @return ConsumerDTO
     * @throws MakeConsumerFailedException
     */
    protected function makeConsumerFromConfig(string $name): ConsumerDTO
    {
        if (null !== ($result = $this->consumersConfig->config()->get($name))) {
            return $result;
        }
        throw new MakeConsumerFailedException(sprintf('Consumer [%s] is missing', $name));
    }

    /**
     * @param array $parameters
     * @return ConsumerDTO
     * @throws MakeConsumerFailedException
     */
    protected function makeConsumerFromArray(array $parameters): ConsumerDTO
    {
        if (null !== ($result = $this->consumersConfig->make($parameters))) {
            return $result;
        }
        throw new MakeConsumerFailedException('Creating consumer from array failed');
    }
}