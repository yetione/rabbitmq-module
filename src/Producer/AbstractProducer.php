<?php


namespace Yetione\RabbitMQ\Producer;


use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
use Yetione\RabbitMQ\Connection\ConnectionInterface;
use Yetione\RabbitMQ\Connection\InteractsWithConnection;
use Yetione\RabbitMQ\DTO\Exchange;
use Yetione\RabbitMQ\Event\EventDispatcherInterface;
use Yetione\RabbitMQ\Event\OnAfterPublishingMessageEvent;
use Yetione\RabbitMQ\Event\OnBeforePublishingMessageEvent;
use Yetione\RabbitMQ\Event\OnErrorPublishingMessageEvent;
use Yetione\RabbitMQ\Message\Factory\MessageFactoryInterface;


/**
 * TODO: EventManager
 * Class AbstractProducer
 * @package Yetione\RabbitMQ\Producer
 */
abstract class AbstractProducer implements ProducerInterface
{
    use InteractsWithConnection;

    protected MessageFactoryInterface $messageFactory;

    protected Exchange $exchange;

    protected EventDispatcherInterface $eventDispatcher;

    /**
     * Кол-во повторных попыток
     * @var int
     */
    protected int $retries = 5;

    protected int $currentTry = 0;

    /**
     * AbstractProducer constructor.
     * @param ConnectionInterface $connection
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(ConnectionInterface $connection, EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->setConnectionWrapper($connection);
    }

    protected function beforePublish()
    {
        $this->maybeReconnect();
        $this->connection()->declareExchange($this->getExchange());
        $this->newTry();
        if (1 < $this->currentTry()) {
            $this->eventDispatcher->dispatch(
                (new OnBeforePublishingMessageEvent())
                    ->setProducer($this)
            );
        }
    }

    protected function afterPublish(AMQPMessage $message)
    {
        $this->eventDispatcher->dispatch(
            (new OnAfterPublishingMessageEvent())
                ->setProducer($this)
                ->setMessage($message)
        );
        $this->resetTries();
    }

    protected function onPublishError(AMQPMessage $message, Throwable $e)
    {
        $this->eventDispatcher->dispatch(
            (new OnErrorPublishingMessageEvent())
            ->setProducer($this)->setMessage($message)
            ->setParams(['error'=>$e])
        );
    }

    protected function isNeedRetry(): bool
    {
        if ($this->currentTry <= $this->getRetries()) {
            return true;
        }
        // Попытки кончились, а значит обнуляем счетчик
        $this->resetTries();
        return false;
    }

    protected function newTry()
    {
        $this->currentTry++;
    }

    protected function resetTries()
    {
        $this->currentTry = 0;
    }

    protected function currentTry(): int
    {
        return $this->currentTry;
    }

    protected function closeProducer()
    {
        $this->close();
    }

    public function __destruct()
    {
        $this->closeProducer();
    }

    public function getMessageFactory(): MessageFactoryInterface
    {
        return $this->messageFactory;
    }

    public function setMessageFactory(MessageFactoryInterface $messageFactory): AbstractProducer
    {
        $this->messageFactory = $messageFactory;
        return $this;
    }

    public function setExchange(Exchange $exchange): self
    {
        $this->exchange = $exchange;
        return $this;
    }

    public function getExchange(): Exchange
    {
        if (null === $this->exchange) {
            $this->setExchange($this->createExchange());
        }
        return $this->exchange;
    }

    abstract protected function createExchange(): Exchange;

    public function getRetries(): int
    {
        return $this->retries;
    }

    public function setRetries(int $retries): self
    {
        $this->retries = $retries;
        return $this;
    }
}