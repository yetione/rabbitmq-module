<?php


namespace Yetione\RabbitMQ\Message\Factory;


use PhpAmqpLib\Message\AMQPMessage;
use Yetione\Json\Json;
use Yetione\RabbitMQ\Message\Builders\MessageBuilder;

abstract class AbstractMessageFactory implements MessageFactoryInterface
{
    protected string $contentType = 'text/plain';

    protected int $deliveryMode = AMQPMessage::DELIVERY_MODE_PERSISTENT;

    protected array $defaultOptions = [];

    protected array $defaultProperties = [
        'content_type'=>'text/plain',
        'delivery_mode'=>AMQPMessage::DELIVERY_MODE_PERSISTENT
    ];

    protected MessageBuilder $messageBuilder;

    public function __construct(array $defaultOptions=[], array $defaultProperties=[])
    {
        $this->setDefaultOptions(array_merge($this->defaultOptions, $defaultOptions));
        $this->setDefaultProperties(array_merge($this->defaultProperties, $defaultProperties));
    }

    public function getMessageBuilder(): MessageBuilder
    {
        if (!isset($this->messageBuilder)) {
            $this->messageBuilder = $this->createMessageBuilder();
        }
        return $this->messageBuilder;
    }

    abstract protected function createMessageBuilder(): MessageBuilder;

    public function setContentType(string $contentType): MessageFactoryInterface
    {
        $this->contentType = $contentType;
        return $this;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function setDeliveryMode(int $deliveryMode): MessageFactoryInterface
    {
        $this->deliveryMode = $deliveryMode;
        return $this;
    }

    public function getDeliveryMode(): int
    {
        return  $this->deliveryMode;
    }

    public function getDefaultProperties(): array
    {
        if (!isset($this->defaultProperties) || empty($this->defaultProperties)) {
            $this->setDefaultProperties([
                'content_type'=>$this->getContentType(),
                'delivery_mode'=>$this->getDeliveryMode()
            ]);
        }
        return $this->defaultProperties;
    }

    public function setDefaultProperties(array $defaultProperties): MessageFactoryInterface
    {
        $this->defaultProperties = $defaultProperties;
        return $this;
    }

    public function getDefaultOptions(): array
    {
        return $this->defaultOptions;
    }

    public function setDefaultOptions(array $defaultOptions): MessageFactoryInterface
    {
        $this->defaultOptions = $defaultOptions;
        return $this;
    }

    public function createMessage(string $body = '', array $parameters = [], array $headers = []): AMQPMessage
    {
        $this->getMessageBuilder()->reset()->withBody($body);
        $properties = $this->messageBuilder->properties();
        $properties->merge($parameters)->withHeaders($properties->headers()->merge($headers)->all());
        return $this->messageBuilder->build();
    }

    public function fromArray(array $body, array $parameters = [], array $headers = []): AMQPMessage
    {
        return $this->createMessage(Json::encode($body), $parameters, $headers);
    }
}