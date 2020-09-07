<?php


namespace Yetione\RabbitMQ\Event;


class OnBeforeFlushingMessageEvent extends ProducerEvent
{
    protected $name = ProducerEvent::BEFORE_FLUSHING_MESSAGE;
}