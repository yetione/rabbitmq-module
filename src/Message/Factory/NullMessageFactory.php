<?php


namespace Yetione\RabbitMQ\Message\Factory;


use Yetione\RabbitMQ\Message\Builders\MessageBuilder;
use Yetione\RabbitMQ\Message\Builders\NullMessageBuilder;

class NullMessageFactory extends AbstractMessageFactory
{

    protected function createMessageBuilder(): MessageBuilder
    {
        return new NullMessageBuilder([], []);
    }
}