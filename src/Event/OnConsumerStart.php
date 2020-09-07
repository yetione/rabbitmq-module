<?php


namespace Yetione\RabbitMQ\Event;


class OnConsumerStart extends ConsumerEvent
{
    protected $name = ConsumerEvent::ON_START;
}