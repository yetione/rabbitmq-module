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
    private Exchange $exchange;

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
    private bool $nowait = false;

    /**
     * @var array|null
     * @Assert\Type(type={"array", "null"})
     * @SerializedName("arguments")
     */
    private ?array $arguments = [];

    /**
     * @var int|null
     * @Assert\Type(type={"int", "null"})
     */
    private ?int $ticket = null;

    /**
     * Если true - то binding считается уже объявленным ранее, иначе будет сделана попытка
     * объявления.
     *
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("declare")
     */
    private bool $declare = true;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("temporary")
     */
    private bool $temporary = false;

    /**
     * Binding constructor.
     * @param Exchange $exchange
     */
    public function __construct(Exchange $exchange)
    {
        $this->exchange = $exchange;
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