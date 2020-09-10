<?php


namespace Yetione\RabbitMQ\Message\Factory;


use Yetione\RabbitMQ\Message\Builders\MessageBuilder;
use Yetione\RabbitMQ\Message\Builders\ReusableMessageBuilder;

class ReusableMessageFactory extends AbstractMessageFactory
{
    protected function createMessageBuilder(): MessageBuilder
    {
        return new ReusableMessageBuilder([], $this->getDefaultProperties());
    }
}