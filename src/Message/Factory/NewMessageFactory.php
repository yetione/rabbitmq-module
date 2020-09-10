<?php


namespace Yetione\RabbitMQ\Message\Factory;

use Yetione\RabbitMQ\Message\Builders\MessageBuilder;

class NewMessageFactory extends AbstractMessageFactory
{
    protected function createMessageBuilder(): MessageBuilder
    {
        return new MessageBuilder([], $this->getDefaultProperties());
    }
}