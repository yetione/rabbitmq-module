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
    private Exchange $destination;

    /**
     * QueueBind constructor.
     * @param Exchange $destination
     * @param Exchange $exchange
     */
    public function __construct(Exchange $destination, Exchange $exchange)
    {
        $this->destination = $destination;
        parent::__construct($exchange);
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