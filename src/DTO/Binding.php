<?php


namespace Yetione\RabbitMQ\DTO;

use Yetione\DTO\DTOInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

abstract class Binding implements DTOInterface
{
    /**
     * @var Exchange
     * @Assert\Type(type="\Yetione\RabbitMQ\DTO\Exchange")
     * @SerializedName("exchnage")
     */
    private $exchange;

    /**
     * @var string|array
     * @Assert\Type(type={"string", "array"})
     * @SerializedName("routing_key")
     */
    private $routingKey;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("nowait")
     */
    private $nowait;

    /**
     * @var array|null
     * @Assert\Type(type={"array", "null"})
     * @SerializedName("arguments")
     */
    private $arguments;

    /**
     * @var int|null
     * @Assert\Type(type={"int", "null"})
     */
    private $ticket;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("declare")
     */
    private $declare;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("temporary")
     */
    private $temporary;

    /**
     * Binding constructor.
     * @param Exchange $exchange
     * @param array|string $routingKey
     * @param bool $nowait
     * @param array|null $arguments
     * @param int|null $ticket
     * @param bool $declare
     * @param bool $temporary
     */
    public function __construct(
        Exchange $exchange, $routingKey='',
        bool $nowait=false, ?array $arguments=[], ?int $ticket=null, bool $declare=true, bool $temporary=false
    )
    {
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
        $this->nowait = $nowait;
        $this->arguments = $arguments;
        $this->ticket = $ticket;
        $this->declare = $declare;
        $this->temporary = $temporary;
    }

    /**
     * @return Exchange
     */
    public function getExchange(): Exchange
    {
        return $this->exchange;
    }

    /**
     * @param Exchange $exchange
     * @return Binding
     */
    public function setExchange(Exchange $exchange): Binding
    {
        $this->exchange = $exchange;
        return $this;
    }

    /**
     * @return array|string
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    /**
     * @param array|string $routingKey
     * @return Binding
     */
    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNowait(): bool
    {
        return $this->nowait;
    }

    /**
     * @param bool $nowait
     * @return Binding
     */
    public function setNowait(bool $nowait): Binding
    {
        $this->nowait = $nowait;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    /**
     * @param array|null $arguments
     * @return Binding
     */
    public function setArguments(?array $arguments): Binding
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getTicket(): ?int
    {
        return $this->ticket;
    }

    /**
     * @param int|null $ticket
     * @return Binding
     */
    public function setTicket(?int $ticket): Binding
    {
        $this->ticket = $ticket;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDeclare(): bool
    {
        return $this->declare;
    }

    /**
     * @param bool $declare
     * @return Binding
     */
    public function setDeclare(bool $declare): Binding
    {
        $this->declare = $declare;
        return $this;
    }

    /**
     * @return bool
     */
    public function isTemporary(): bool
    {
        return $this->temporary;
    }

    /**
     * @param bool $temporary
     * @return Binding
     */
    public function setTemporary(bool $temporary): Binding
    {
        $this->temporary = $temporary;
        return $this;
    }
}