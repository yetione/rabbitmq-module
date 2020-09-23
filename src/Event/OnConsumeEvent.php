<?php


namespace Yetione\RabbitMQ\Event;


class OnConsumeEvent extends ConsumerEvent
{
    protected $name = ConsumerEvent::ON_CONSUME;
}