<?php


namespace Yetione\RabbitMQ\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class Producer extends Connectable
{

    /**
     * @var string
     * @Assert\Type(type="string")
     * @SerializedName("type")
     */
    private string $type;

    /**
     * @var string
     * @Assert\Type(type="string")
     * @SerializedName("exchange")
     */
    private string $exchange;

    public function __construct(string $type, string $exchange, string $connection)
    {
        parent::__construct($connection);
        $this->setType($type)->setExchange($exchange);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Producer
     */
    public function setType(string $type): Producer
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getExchange(): string
    {
        return $this->exchange;
    }

    /**
     * @param string $exchange
     * @return Producer
     */
    public function setExchange(string $exchange): Producer
    {
        $this->exchange = $exchange;
        return $this;
    }
}