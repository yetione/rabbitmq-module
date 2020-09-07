<?php


namespace Yetione\RabbitMQ\DTO;

use Yetione\DTO\DTOInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class Queue implements DTOInterface
{
    /**
     * @var string
     * @Assert\Type(type="string")
     * @Assert\NotBlank()
     * @SerializedName("name")
     */
    private $name;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("passive")
     */
    private $passive;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("durable")
     */
    private $durable;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("exclusive")
     */
    private $exclusive;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("auto_delete")
     */
    private $autoDelete;

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
     * Queue constructor.
     * @param string $name
     * @param bool $passive
     * @param bool $durable
     * @param bool $exclusive
     * @param bool $autoDelete
     * @param bool $nowait
     * @param array|null $arguments
     * @param int|null $ticket
     * @param bool $declare
     * @param bool $temporary
     */
    public function __construct(
        string $name='', bool $passive=false, bool $durable=true, bool $exclusive=false,
        bool $autoDelete=false, bool $nowait=false, ?array $arguments=[], ?int $ticket=null, bool $declare=true,
        bool $temporary=false
    )
    {
        $this->name = $name;
        $this->passive = $passive;
        $this->durable = $durable;
        $this->exclusive = $exclusive;
        $this->autoDelete = $autoDelete;
        $this->nowait = $nowait;
        $this->arguments = $arguments;
        $this->ticket = $ticket;
        $this->declare = $declare;
        $this->temporary = $temporary;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isPassive(): bool
    {
        return $this->passive;
    }

    /**
     * @return bool
     */
    public function isDurable(): bool
    {
        return $this->durable;
    }

    /**
     * @return bool
     */
    public function isExclusive(): bool
    {
        return $this->exclusive;
    }

    /**
     * @return bool
     */
    public function isAutoDelete(): bool
    {
        return $this->autoDelete;
    }

    /**
     * @return bool
     */
    public function isNowait(): bool
    {
        return $this->nowait;
    }

    /**
     * @return array|null
     */
    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    /**
     * @return int|null
     */
    public function getTicket(): ?int
    {
        return $this->ticket;
    }

    /**
     * @return bool
     */
    public function isDeclare(): bool
    {
        return $this->declare;
    }

    /**
     * @param string $name
     * @return Queue
     */
    public function setName(string $name): Queue
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param bool $passive
     * @return Queue
     */
    public function setPassive(bool $passive): Queue
    {
        $this->passive = $passive;
        return $this;
    }

    /**
     * @param bool $durable
     * @return Queue
     */
    public function setDurable(bool $durable): Queue
    {
        $this->durable = $durable;
        return $this;
    }

    /**
     * @param bool $exclusive
     * @return Queue
     */
    public function setExclusive(bool $exclusive): Queue
    {
        $this->exclusive = $exclusive;
        return $this;
    }

    /**
     * @param bool $autoDelete
     * @return Queue
     */
    public function setAutoDelete(bool $autoDelete): Queue
    {
        $this->autoDelete = $autoDelete;
        return $this;
    }

    /**
     * @param bool $nowait
     * @return Queue
     */
    public function setNowait(bool $nowait): Queue
    {
        $this->nowait = $nowait;
        return $this;
    }

    /**
     * @param array|null $arguments
     * @return Queue
     */
    public function setArguments(?array $arguments): Queue
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @param int|null $ticket
     * @return Queue
     */
    public function setTicket(?int $ticket): Queue
    {
        $this->ticket = $ticket;
        return $this;
    }

    /**
     * @param bool $declare
     * @return Queue
     */
    public function setDeclare(bool $declare): Queue
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
     * @return Queue
     */
    public function setTemporary(bool $temporary): Queue
    {
        $this->temporary = $temporary;
        return $this;
    }
}