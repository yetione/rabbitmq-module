<?php


namespace Yetione\RabbitMQ\Message\Builders;


use PhpAmqpLib\Message\AMQPMessage;

class NullMessageBuilder extends MessageBuilder
{

    public function build(): AMQPMessage
    {
        return new AMQPMessage();
    }
}