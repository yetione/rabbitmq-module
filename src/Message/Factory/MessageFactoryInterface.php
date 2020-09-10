<?php


namespace Yetione\RabbitMQ\Message\Factory;


use PhpAmqpLib\Message\AMQPMessage;

interface MessageFactoryInterface
{
    /**
     * Create new AMQPMessage for publishing.
     *
     * @param string $body
     * @param array $parameters
     * @param array $headers
     * @return AMQPMessage
     */
    public function createMessage(string $body='', array $parameters=[], array $headers=[]): AMQPMessage;

    /**
     * Create new AMQPMessage form publishing from array using json_encode.
     *
     * @param array $body
     * @param array $parameters
     * @param array $headers
     * @return AMQPMessage
     */
    public function fromArray(array $body, array $parameters=[], array $headers=[]): AMQPMessage;
}