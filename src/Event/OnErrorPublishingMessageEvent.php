<?php


namespace Yetione\RabbitMQ\Event;


class OnErrorPublishingMessageEvent extends ProducerEvent
{
    protected $name = ProducerEvent::ERROR_PUBLISHING_MESSAGE;
}