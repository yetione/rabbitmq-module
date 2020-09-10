<?php


namespace Yetione\RabbitMQ\Event;


class OnErrorFlushingMessageEvent extends ProducerEvent
{
    protected $name = ProducerEvent::ERROR_FLUSHING_MESSAGE;
}