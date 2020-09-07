<?php


namespace Yetione\RabbitMQ\Event;


class OnConsumerFinish extends ConsumerEvent
{
    protected $name = ConsumerEvent::ON_FINISH;
}