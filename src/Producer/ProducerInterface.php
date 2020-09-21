<?php


namespace Yetione\RabbitMQ\Producer;


use PhpAmqpLib\Message\AMQPMessage;
use Yetione\RabbitMQ\DTO\Exchange;
use Yetione\RabbitMQ\Message\Factory\MessageFactoryInterface;

interface ProducerInterface
{
    /**
     * @param AMQPMessage $message
     * @param string $routingKey
     * @param bool $mandatory
     * @param bool $immediate
     * @param int|null $ticket
     * @return ProducerInterface
     */
    public function publish(AMQPMessage $message, string $routingKey='', bool $mandatory = false, bool $immediate=false, ?int $ticket = null): ProducerInterface;

    /**
     * @return Exchange
     */
    public function getExchange(): Exchange;

    /**
     * @return MessageFactoryInterface
     */
    public function getMessageFactory(): MessageFactoryInterface;
}