<?php


namespace Yetione\RabbitMQ\Producer;


use PhpAmqpLib\Message\AMQPMessage;
use Yetione\Json\Json;
use Yetione\RabbitMQ\Connection\ConnectionInterface;
use Yetione\RabbitMQ\DTO\Exchange;
use Yetione\RabbitMQ\Event\EventDispatcherInterface;
use Yetione\RabbitMQ\Event\OnAfterPublishingMessageEvent;
use Yetione\RabbitMQ\Event\OnBeforePublishingMessageEvent;
use Yetione\RabbitMQ\Exception\ConnectionException;
use Yetione\RabbitMQ\Message\Factory\MessageFactoryInterface;
use Yetione\RabbitMQ\Service\RabbitMQService;


/**
 * TODO: EventManager
 * Class AbstractProducer
 * @package Yetione\RabbitMQ\Producer
 */
abstract class AbstractProducer implements ProducerInterface
{
    protected MessageFactoryInterface $messageFactory;

    protected ConnectionInterface $connectionWrapper;

    protected string $connectionOptionsName = 'producer';

    protected string $connectionName = 'default_producer';

    protected bool $autoReconnect = true;

    protected Exchange $exchange;

    protected RabbitMQService $rabbitMQService;

    protected EventDispatcherInterface $eventDispatcher;

    /**
     * AbstractProducer constructor.
     * @param RabbitMQService $rabbitMQService
     * @param EventDispatcherInterface $eventDispatcher
     * @throws ConnectionException
     */
    public function __construct(RabbitMQService $rabbitMQService, EventDispatcherInterface $eventDispatcher)
    {
        $this->rabbitMQService = $rabbitMQService;
        $this->eventDispatcher = $eventDispatcher;
        $oConnection = $this->rabbitMQService->getConnection($this->connectionName, $this->connectionOptionsName);
        if (null === $oConnection) {
            throw new ConnectionException("Cannot create connection {$this->connectionName} with option {$this->connectionOptionsName}.");
        }
        $this->setConnectionWrapper($oConnection);
    }

    protected function beforePublish()
    {
        $this->checkConnection();
        $this->getConnectionWrapper()->declareExchange($this->getExchange());
        $this->eventDispatcher->dispatch(
            (new OnBeforePublishingMessageEvent())
                ->setProducer($this)
        );
    }

    protected function afterPublish(AMQPMessage $message)
    {
        $this->eventDispatcher->dispatch(
            (new OnAfterPublishingMessageEvent())
                ->setProducer($this)
                ->setMessage($message)
        );
    }

    /**
     * @return $this
     */
    protected function checkConnection(): self
    {
        if (!$this->getConnectionWrapper()->isConnectionOpen() && $this->autoReconnect) {
            $this->getConnectionWrapper()->reconnect();
        }
        if (!$this->getConnectionWrapper()->isChannelOpen()) {
            $this->getConnectionWrapper()->getChannel(true);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function closeConnectionWrapper(): self
    {
        if (null !== $this->connectionWrapper && $this->getConnectionWrapper()->isConnectionOpen()) {
            $this->getConnectionWrapper()->close();
        }
        return $this;
    }

    protected function closeProducer()
    {
        $this->closeConnectionWrapper();
    }

    public function __destruct()
    {
        $this->closeProducer();
    }

    /**
     * @param Exchange $exchange
     * @return $this
     */
    public function setExchange(Exchange $exchange): self
    {
        $this->exchange = $exchange;
        return $this;
    }

    /**
     * @return MessageFactoryInterface
     */
    public function getMessageFactory(): MessageFactoryInterface
    {
        return $this->messageFactory;
    }

    /**
     * @param MessageFactoryInterface $messageFactory
     * @return $this
     */
    public function setMessageFactory(MessageFactoryInterface $messageFactory): AbstractProducer
    {
        $this->messageFactory = $messageFactory;
        return $this;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnectionWrapper(): ConnectionInterface
    {
        return $this->connectionWrapper;
    }

    /**
     * @param ConnectionInterface $connectionWrapper
     * @return $this
     */
    public function setConnectionWrapper(ConnectionInterface $connectionWrapper): AbstractProducer
    {
        $this->connectionWrapper = $connectionWrapper;
        return $this;
    }

    /**
     * @return string
     */
    public function getConnectionOptionsName(): string
    {
        return $this->connectionOptionsName;
    }

    /**
     * @param string $connectionOptionsName
     * @return $this
     */
    public function setConnectionOptionsName(string $connectionOptionsName): AbstractProducer
    {
        $this->connectionOptionsName = $connectionOptionsName;
        return $this;
    }

    /**
     * @return string
     */
    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    /**
     * @param string $connectionName
     * @return $this
     */
    public function setConnectionName(string $connectionName): AbstractProducer
    {
        $this->connectionName = $connectionName;
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

    public function getLoggerContext($message=null): array
    {
        $aResult = [
            'producer'=>[
                'exchange_name'=>$this->getExchange()->getName(),
                'exchange_type'=>$this->getExchange()->getType(),
                'connection_class'=>get_class($this->getConnectionWrapper()->getConnection()),
                'connection_name'=>$this->getConnectionName(),
                'connection_options'=>$this->getConnectionOptionsName(),
                'message_factory'=>get_class($this->getMessageFactory())
            ]
        ];
        if ($message instanceof AMQPMessage) {
            $aResult['message'] = [
                'body'=>$message->getBody(),
                'body_size'=>$message->getBodySize(),
                'properties'=>$message->get_properties(),
                'encoding'=>$message->getContentEncoding()
            ];
        } elseif (is_array($message) || is_string($message)) {
            $sBody = is_array($message) ?  Json::encode($message) : $message;
            $aResult['message'] = [
                'body'=>$sBody,
                'body_size'=>strlen($sBody)
            ];
        }
        return $aResult;
    }

}