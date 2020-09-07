<?php


namespace Yetione\RabbitMQ\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class ExchangeBinding extends Binding
{
    /**
     * @var Exchange
     * @Assert\Type(type="\Yetione\RabbitMQ\DTO\Exchange")
     * @Assert\NotBlank()
     * @SerializedName("exchange")
     */
    private $destination;

    /**
     * QueueBind constructor.
     * @param Exchange $destination
     * @param Exchange $exchange
     * @param array|string $routingKey
     * @param bool $nowait
     * @param array|null $arguments
     * @param int|null $ticket
     * @param bool $declare
     * @param bool $temporary
     */
    public function __construct(
        Exchange $destination, Exchange $exchange, $routingKey='',
        bool $nowait=false, ?array $arguments=[], ?int $ticket=null, bool $declare=true, bool $temporary=false
    )
    {
        $this->destination = $destination;
        parent::__construct($exchange, $routingKey, $nowait, $arguments, $ticket, $declare, $temporary);
    }

    /**
     * @return Exchange
     */
    public function getDestination(): Exchange
    {
        return $this->destination;
    }

    /**
     * @param Exchange $destination
     * @return ExchangeBinding
     */
    public function setDestination(Exchange $destination): ExchangeBinding
    {
        $this->destination = $destination;
        return $this;
    }

    public function getKey(): string
    {
        return $this->getDestination()->getName() . ':' . $this->getExchange()->getName();
    }
}