<?php


namespace Yetione\RabbitMQ\Event;


interface EventDispatcherInterface
{
    public function dispatch($event);

    public function listen($eventName, $listener, $priority=null);
}