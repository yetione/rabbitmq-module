<?php


namespace Yetione\RabbitMQ\Support;


use Yetione\RabbitMQ\Event\EventDispatcherInterface;

/**
 * Trait WithEventDispatcher
 * @package Yetione\RabbitMQ\Support
 */
trait WithEventDispatcher
{
    protected EventDispatcherInterface $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @return $this|\Yetione\RabbitMQ\Event\WithEventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): \Yetione\RabbitMQ\Event\WithEventDispatcher
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }
}