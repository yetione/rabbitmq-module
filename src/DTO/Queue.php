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
    private string $name = '';

    /**
     * Если true, то при попытке создать уже существующую queue вернется успешный результат,
     * иначе - ошибка.
     *
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("passive")
     */
    private bool $passive = false;

    /**
     * queue хранится на диске
     *
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("durable")
     */
    private bool $durable = true;

    /**
     * Exclusive queue доступны только в пределах текущего соединения и будут удалены при закрытие соединения.
     * passive нельзя использовать с exclusive queue
     *
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("exclusive")
     */
    private bool $exclusive = false;

    /**
     * Queue удаляется, когда завершает работу её последний consumer. Если у queue небыло consumers, то она не удалится.
     * Такие queue можно удалять с помощью delete запроса.
     *
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("auto_delete")
     */
    private bool $autoDelete = false;

    /**
     * Если true - RabbitMQ не ответит на метод. В этом случае клиент не должен ожидать ответа от сервера.
     * Если метод не может быть выполнен, то будет выброшено исключение.
     *
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("nowait")
     */
    private bool $nowait = false;

    /**
     * Массив доп. аргументов queue:
     * x-message-ttl -- время жизни сообщения в мс.
     * x-expires -- время жизни очереди в мс.
     * x-dead-letter-exchange -- exchange в который будут отправляться "мёртвые" сообщения:
     *                              - basic_reject, basic_nack with requeue=false;
     *                              - вышел TTL (x-message-ttl, message expiration)
     *                              - сообщение не обработано из-за лимитов длины queue
     * x-dead-letter-routing-key -- routing key для "мёртвого сообщения"
     * x-max-length -- максимальное кол-во сообщений в queue
     * x-max-length-bytes -- максимальный размер сообщений в queue
     * x-overflow -- политика при переполнение:
     *                      - drop-head - отбрасываются сообщения с начала очереди (старые)
     *                      - reject-publish - отбрасываются сообщения с конца очереди (новые)
     * x-max-priority -- приоритет queue. 0-255, сообщения с самым высоким приоритетом будут обрабатываться в queue с
     *                      с самым высоким приоритетом
     * x-queue-mode -- режим работы queue (default, lazy)
     * x-queue-type -- тип queue (quorum, classic)
     *
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