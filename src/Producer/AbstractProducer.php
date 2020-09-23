<?php


namespace Yetione\RabbitMQ\Producer;


use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
use Yetione\RabbitMQ\Connection\ConnectionInterface;
use Yetione\RabbitMQ\Connection\InteractsWithConnection;
use Yetione\RabbitMQ\DTO\Exchange as ExchangeDTO;
use Yetione\RabbitMQ\DTO\Producer as ProducerDTO;
use Yetione\RabbitMQ\Event\EventDispatcherInterface;
use Yetione\RabbitMQ\Event\OnAfterPublishingMessageEvent;
use Yetione\RabbitMQ\Event\OnBeforePublishingMessageEvent;
use Yetione\RabbitMQ\Event\OnErrorPublishingMessageEvent;
use Yetione\RabbitMQ\Logger\Loggable;
use Yetione\RabbitMQ\Logger\WithLogger;
use Yetione\RabbitMQ\Message\Factory\MessageFactoryInterface;
use Yetione\RabbitMQ\Support\WithEventDispatcher;


/**
 * TODO: EventManager
 * Class AbstractProducer
 * @package Yetione\RabbitMQ\Producer
 */
abstract class AbstractProducer implements ProducerInterface, Loggable
{
    use InteractsWithConnection, WithEventDispatcher, WithLogger;

    protected MessageFactoryInterface $messageFactory;

    protected ProducerDTO $options;

    protected ExchangeDTO $exchange;

    protected int $currentPublishTry = 0;

    /**
     * AbstractProducer constructor.
     * @param ProducerDTO $options
     * @param ExchangeDTO $exchange
     * @param ConnectionInterface $connection
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ProducerDTO $options,
        ExchangeDTO $exchange,
        ConnectionInterface $connection,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->options = $options;
        $this->exchange = $exchange;
        $this->setEventDispatcher($eventDispatcher);
        $this->setConnectionWrapper($connection);
    }

    protected function setup()
    {

    }

    protected function beforePublish()
    {
        $this->connection()->declareExchange($this->getExchange());
        $this->tryPublish();
        if (1 === $this->currentPublishTry()) {
            $this->eventDispatcher->dispatch(
                (new OnBeforePublishingMessageEvent())
                    ->setProducer($this)
            );
        }
    }

    protected function afterPublish(AMQPMessage $message, ?Throwable $e=null)
    {
        $event = null === $e ?
            new OnAfterPublishingMessageEvent() :
            (new OnErrorPublishingMessageEvent())->setParams(['error'=>$e]);
        $event->setProducer($this)->setMessage($message);
        $this->eventDispatcher->dispatch($event);
        $this->resetPublishTries();
    }

    protected function isNeedRetry(): bool
    {
        if ($this->currentPublishTry <= $this->options->getPublishRetries()) {
            return true;
        }
        // Попытки кончились, а значит обнуляем счетчик
        $this->resetPublishTries();
        return false;
    }

    protected function tryPublish()
    {
        $this->currentPublishTry++;
    }

    protected function resetPublishTries()
    {
        $this->currentPublishTry = 0;
    }

    protected function currentPublishTry(): int
    {
        return $this->currentPublishTry;
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

    public function setExchange(ExchangeDTO $exchange): self
    {
        $this->exchange = $exchange;
        return $this;
    }

    public function getExchange(): ExchangeDTO
    {
        return $this->exchange;
    }
}