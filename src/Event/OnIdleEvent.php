<?php


namespace Yetione\RabbitMQ\Event;


class OnIdleEvent extends ConsumerEvent
{
    protected $name = ConsumerEvent::ON_IDLE;

    protected $forceStop = true;

    /**
     * @return bool
     */
    public function isForceStop(): bool
    {
        return $this->forceStop;
    }

    /**
     * @param bool $forceStop
     * @return OnIdleEvent
     */
    public function setForceStop(bool $forceStop): OnIdleEvent
    {
        $this->forceStop = $forceStop;
        return $this;
    }
}