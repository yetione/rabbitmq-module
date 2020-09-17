<?php


namespace Yetione\RabbitMQ\DTO;


use Yetione\DTO\DTOInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class Exchange implements DTOInterface
{
    /**
     * @var string
     * @Assert\Type(type="string")
     * @Assert\NotBlank()
     * @SerializedName("name")
     */
    private $name;

    /**
     * @var string
     * @Assert\Type(type="string")
     * @Assert\Choice({
     *     \Yetione\RabbitMQ\Constant\Echange::TYPE_DIRECT,
     *     \Yetione\RabbitMQ\Constant\Echange::TYPE_TOPIC,
     *     \Yetione\RabbitMQ\Constant\Echange::TYPE_HEADERS,
     *     \Yetione\RabbitMQ\Constant\Echange::TYPE_FANOUT
     *     })
     * @SerializedName("type")
     */
    private $type;

    /**
     * Если true, то при попытке создать уже существующий exchange вернется успешный результат,
     * иначе - ошибка (мб исключение)
     *
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("passive")
     */
    private $passive;

    /**
     * exchange хранится на диске
     *
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("durable")
     */
    private $durable;

    /**
     * Удалить exchange после того, как все очереди завершат работу.
     *
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("auto_delete")
     */
    private $autoDelete;

    /**
     * Внутренний exchange. Если true, то не может использоваться для отправки сообщений.
     * Только для связи с другими exchange. Невидимы для клиентских приложений, используются для
     * создания сети.
     *
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("internal")
     */
    private $internal;

    /**
     * Если true - RabbitMQ не ответит на метод. В этом случае клиент не должен ожидать ответа от сервера.
     * Если метод не может быть выполнен, то будет выброшено исключение.
     *
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("nowait")
     */
    private $nowait;

    /**
     * Массив с доп. аргументами. Обычно используется для настройки альтернативных вариантов доставки сообщений.
     *
     * alternate-exchange -- exchange, в который будут направляться сообщения, необработанные в этом
     *
     * @var array|null
     * @Assert\Type(type={"array", "null"})
     * @SerializedName("arguments")
     */
    private $arguments;

    /**
     * @var int|null
     * @Assert\Type(type={"int", "null"})
     * @SerializedName("ticket")
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
     * Exchange constructor.
     * @param string $name
     * @param string $type
     * @param bool $passive
     * @param bool $durable
     * @param bool $autoDelete
     * @param bool $internal
     * @param bool $nowait
     * @param array|null $arguments
     * @param int|null $ticket
     * @param bool $declare
     * @param bool $temporary
     */
    public function __construct(
        string $name, string $type, bool $passive=false,
        bool $durable=true, bool $autoDelete=false, bool $internal=false,
        bool $nowait=false, ?array $arguments=[], ?int $ticket=null, bool $declare=true, bool $temporary=false
    )
    {
        $this->name = $name;
        $this->type = $type;
        $this->passive = $passive;
        $this->durable = $durable;
        $this->autoDelete = $autoDelete;
        $this->internal = $internal;
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
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
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
    public function isAutoDelete(): bool
    {
        return $this->autoDelete;
    }

    /**
     * @return bool
     */
    public function isInternal(): bool
    {
        return $this->internal;
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
     * @return Exchange
     */
    public function setName(string $name): Exchange
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $type
     * @return Exchange
     */
    public function setType(string $type): Exchange
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param bool $passive
     * @return Exchange
     */
    public function setPassive(bool $passive): Exchange
    {
        $this->passive = $passive;
        return $this;
    }

    /**
     * @param bool $durable
     * @return Exchange
     */
    public function setDurable(bool $durable): Exchange
    {
        $this->durable = $durable;
        return $this;
    }

    /**
     * @param bool $autoDelete
     * @return Exchange
     */
    public function setAutoDelete(bool $autoDelete): Exchange
    {
        $this->autoDelete = $autoDelete;
        return $this;
    }

    /**
     * @param bool $internal
     * @return Exchange
     */
    public function setInternal(bool $internal): Exchange
    {
        $this->internal = $internal;
        return $this;
    }

    /**
     * @param bool $nowait
     * @return Exchange
     */
    public function setNowait(bool $nowait): Exchange
    {
        $this->nowait = $nowait;
        return $this;
    }

    /**
     * @param array|null $arguments
     * @return Exchange
     */
    public function setArguments(?array $arguments): Exchange
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @param int|null $ticket
     * @return Exchange
     */
    public function setTicket(?int $ticket): Exchange
    {
        $this->ticket = $ticket;
        return $this;
    }

    /**
     * @param bool $declare
     * @return Exchange
     */
    public function setDeclare(bool $declare): Exchange
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
     * @return Exchange
     */
    public function setTemporary(bool $temporary): Exchange
    {
        $this->temporary = $temporary;
        return $this;
    }
}