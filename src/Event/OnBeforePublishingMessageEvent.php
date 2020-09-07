<?php


namespace Yetione\RabbitMQ\Event;


class OnBeforePublishingMessageEvent extends ProducerEvent
{
    protected $name = ProducerEvent::BEFORE_PUBLISHING_MESSAGE;
}