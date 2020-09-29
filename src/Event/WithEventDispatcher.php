<?php


namespace Yetione\RabbitMQ\Event;


interface WithEventDispatcher
{
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): WithEventDispatcher;

    public function getEventDispatcher(): EventDispatcherInterface;

}