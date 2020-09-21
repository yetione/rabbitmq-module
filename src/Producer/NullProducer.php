<?php


namespace Yetione\RabbitMQ\Producer;


use PhpAmqpLib\Message\AMQPMessage;
use Yetione\RabbitMQ\DTO\Exchange;
use Yetione\RabbitMQ\Message\Factory\MessageFactoryInterface;
use Yetione\RabbitMQ\Message\Factory\NullMessageFactory;

class NullProducer extends AbstractProducer
{

    protected MessageFactoryInterface $messageFactory;

    public function __construct()
    {

    }

    public function publish(AMQPMessage $message, string $routingKey = '', bool $mandatory = false, bool $immediate = false, ?int $ticket = null): ProducerInterface
    {
        return $this;
    }

    public function getMessageFactory(): MessageFactoryInterface
    {
        if (!isset($this->messageFactory)) {
            $this->messageFactory = new NullMessageFactory();
        }
        return $this->messageFactory;
    }

    public function getExchange(): Exchange
    {
        return new Exchange('null', 'null');
    }
}