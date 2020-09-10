<?php


namespace Yetione\RabbitMQ\Event;


use PhpAmqpLib\Message\AMQPMessage;
use Yetione\RabbitMQ\Consumer\AbstractConsumer;

abstract class ConsumerEvent extends AbstractEvent
{
    const ON_IDLE = 'ON_IDLE';
    const ON_CONSUME = 'ON_CONSUME';
    const BEFORE_PROCESSING_MESSAGE = 'BEFORE_PROCESSING_MESSAGE';
    const AFTER_PROCESSING_MESSAGE = 'AFTER_PROCESSING_MESSAGE';
    const ON_START = 'ON_CONSUMER_START';
    const ON_FINISH = 'ON_CONSUMER_FINISH';

    protected AMQPMessage $message;

    protected AbstractConsumer $consumer;

    /**
     * @return AbstractConsumer
     */
    public function getConsumer(): AbstractConsumer
    {
        return $this->consumer;
    }

    /**
     * @param AbstractConsumer $consumer
     * @return $this
     */
    public function setConsumer(AbstractConsumer $consumer): self
    {
        $this->consumer = $consumer;
        return $this;
    }

    /**
     * @return AMQPMessage
     */
    public function getMessage(): AMQPMessage
    {
        return $this->message;
    }

    /**
     * @param AMQPMessage $message
     * @return $this
     */
    public function setMessage(AMQPMessage $message): self
    {
        $this->message = $message;
        return $this;
    }
}