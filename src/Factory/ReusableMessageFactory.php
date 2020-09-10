<?php


namespace Yetione\RabbitMQ\Factory;


use PhpAmqpLib\Message\AMQPMessage;
use Yetione\Json\Json;

class ReusableMessageFactory extends AbstractMessageFactory
{
    /**
     * @var AMQPMessage
     */
    protected $reusableMessage;

    public function createMessage(string $body = '', array $parameters = [], array $headers = null): AMQPMessage
    {
        if (null !== $this->reusableMessage) {
            $this->reusableMessage->setBody($body);
        } else {
            $this->reusableMessage = new AMQPMessage($body, $this->getMessageParameters($parameters));
        }
        return $this->addHeadersToMessage($this->reusableMessage, $headers);
    }

    public function fromArray(array $body, array $parameters = [], array $header = null): AMQPMessage
    {
        return $this->createMessage(Json::encode($this->extendArray($body)), $parameters, $header);
    }
}