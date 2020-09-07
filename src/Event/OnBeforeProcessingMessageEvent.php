<?php


namespace Yetione\RabbitMQ\Event;


class OnBeforeProcessingMessageEvent extends ConsumerEvent
{
    protected $name = ConsumerEvent::BEFORE_PROCESSING_MESSAGE;
}