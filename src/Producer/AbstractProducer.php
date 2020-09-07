<?php


namespace Yetione\RabbitMQ\Producer;


use InvalidArgumentException;
use PhpAmqpLib\Message\AMQPMessage;
use Yetione\RabbitMQ\Connection\ConnectionWrapper;
use Yetione\RabbitMQ\DTO\Exchange;
use Yetione\RabbitMQ\Event\OnAfterPublishingMessageEvent;
use Yetione\RabbitMQ\Event\OnBeforePublishingMessageEvent;
use Yetione\RabbitMQ\Exception\ConnectionException;
use Yetione\RabbitMQ\Factory\MessageFactoryInterface;
use Yetione\RabbitMQ\Service\RabbitMQService;
use Zend\Json\Json;


/**
 * TODO: EventManager
 * Class AbstractProducer
 * @package Yetione\RabbitMQ\Producer
 */
abstract class AbstractProducer implements ProducerInterface
{
    /**
     * @var MessageFactoryInterface
     */
    protected $messageFactory;

    /**
     * @var ConnectionWrapper
     */
    protected $connectionWrapper;

    /**
     * @var string
     */
    protected $connectionOptionsName = 'producer';

    /**
     * @var string
     */
    protected $connectionName = 'default_producer';

    /**
     * @var bool
     */
    protected $autoReconnect = true;

    /**
     * @var Exchange
     */
    protected $exchange;

    protected RabbitMQService $rabbitMQService;

    /**
     * AbstractProducer constructor.
     * @throws ConnectionException
     * @throws InvalidArgumentException
     */
    public function __construct(RabbitMQService $rabbitMQService)
    {
        $this->rabbitMQService = $rabbitMQService;
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
        $this->getEventManager()->trigger(
            (new OnBeforePublishingMessageEvent())
                ->setProducer($this)
        );
    }

    protected function afterPublish(AMQPMessage $message)
    {
        $this->getEventManager()->trigger(
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
     * @return ConnectionWrapper
     */
    public function getConnectionWrapper(): ConnectionWrapper
    {
        return $this->connectionWrapper;
    }

    /**
     * @param ConnectionWrapper $connectionWrapper
     * @return $this
     */
    public function setConnectionWrapper(ConnectionWrapper $connectionWrapper): AbstractProducer
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