<?php


namespace Yetione\RabbitMQ\Event;


use PhpAmqpLib\Message\AMQPMessage;
use Yetione\RabbitMQ\Producer\AbstractProducer;

abstract class ProducerEvent extends AbstractEvent
{
    const BEFORE_PUBLISHING_MESSAGE = 'BEFORE_PROCESSING_MESSAGE';
    const AFTER_PUBLISHING_MESSAGE = 'AFTER_PROCESSING_MESSAGE';


    const BEFORE_FLUSHING_MESSAGE = 'BEFORE_FLUSHING_MESSAGE';
    const AFTER_FLUSHING_MESSAGE = 'AFTER_FLUSHING_MESSAGE';

    const ERROR_PUBLISHING_MESSAGE = 'ERROR_PUBLISHING_MESSAGE';
    const ERROR_FLUSHING_MESSAGE = 'ERROR_FLUSHING_MESSAGE';

    /**
     * @var AMQPMessage
     */
    protected $message;

    /**
     * @var AbstractProducer
     */
    protected $producer;

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

    /**
     * @return AbstractProducer
     */
    public function getProducer(): AbstractProducer
    {
        return $this->producer;
    }

    /**
     * @param AbstractProducer $producer
     * @return $this
     */
    public function setProducer(AbstractProducer $producer): self
    {
        $this->producer = $producer;
        return $this;
    }
}