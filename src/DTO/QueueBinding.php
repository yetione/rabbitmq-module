<?php


namespace Yetione\RabbitMQ\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class QueueBinding extends Binding
{
    /**
     * @var Queue
     * @Assert\Type(type="\Yetione\RabbitMQ\DTO\Queue")
     * @Assert\NotBlank()
     * @SerializedName("queue")
     */
    private $queue;

    /**
     * QueueBind constructor.
     * @param Queue $queue
     * @param Exchange $exchange
     * @param array|string $routingKey
     * @param bool $nowait
     * @param array|null $arguments
     * @param int|null $ticket
     * @param bool $declare
     * @param bool $temporary
     */
    public function __construct(
        Queue $queue, Exchange $exchange, $routingKey='',
        bool $nowait=false, ?array $arguments=[], ?int $ticket=null, bool $declare=true, bool $temporary=false
    )
    {
        $this->queue = $queue;
        parent::__construct($exchange, $routingKey, $nowait, $arguments, $ticket, $declare, $temporary);
    }

    /**
     * @return Queue
     */
    public function getQueue(): Queue
    {
        return $this->queue;
    }

    /**
     * @param Queue $queue
     * @return QueueBinding
     */
    public function setQueue(Queue $queue): QueueBinding
    {
        $this->queue = $queue;
        return $this;
    }

    public function getKey(): string
    {
        return $this->getQueue()->getName() . ':' . $this->getExchange()->getName();
    }
}