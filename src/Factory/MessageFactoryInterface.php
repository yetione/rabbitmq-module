<?php


namespace Yetione\RabbitMQ\Factory;


use PhpAmqpLib\Message\AMQPMessage;

interface MessageFactoryInterface
{
    /**
     * Create new AMQPMessage for publishing.
     * @param string $body
     * @param array $parameters
     * @param array|null $headers
     * @return AMQPMessage
     */
    public function createMessage(string $body='', array $parameters=[], array $headers=null): AMQPMessage;

    /**
     * Create new AMQPMessage form publishing from array using json_encode.
     * @param array $body
     * @param array $parameters
     * @param array|null $header
     * @return AMQPMessage
     */
    public function fromArray(array $body, array $parameters=[], array $header=null): AMQPMessage;
}