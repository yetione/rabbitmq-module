<?php


namespace Yetione\RabbitMQ\Event;


class OnAfterPublishingMessageEvent extends ProducerEvent
{
    protected $name = ProducerEvent::AFTER_PUBLISHING_MESSAGE;
}