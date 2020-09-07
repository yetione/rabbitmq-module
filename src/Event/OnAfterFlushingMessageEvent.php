<?php


namespace Yetione\RabbitMQ\Event;


class OnAfterFlushingMessageEvent extends ProducerEvent
{
    protected $name = ProducerEvent::AFTER_FLUSHING_MESSAGE;
}