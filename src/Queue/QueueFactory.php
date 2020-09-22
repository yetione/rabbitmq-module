<?php


namespace Yetione\RabbitMQ\Queue;


use Yetione\RabbitMQ\Configs\QueuesConfig;

class QueueFactory
{
    protected QueuesConfig $queuesConfig;

    protected array $queues = [];

    public function __construct(QueuesConfig $queuesConfig)
    {
        $this->queuesConfig = $queuesConfig;
    }

    public function make(string $name, ?string $alias=null)
    {
        $alias = $alias ?: $name;
        if (!isset($this->queues[$alias])) {
            $this->queues[$alias] = $this->queuesConfig->config()->get($name);
        }
        return $this->queues[$alias];
    }
}