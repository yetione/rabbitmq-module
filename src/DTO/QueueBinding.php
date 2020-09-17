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
    private Queue $queue;

    /**
     * QueueBind constructor.
     * @param Queue $queue
     * @param Exchange $exchange
     */
    public function __construct(Queue $queue, Exchange $exchange)
    {
        $this->queue = $queue;
        parent::__construct($exchange);
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