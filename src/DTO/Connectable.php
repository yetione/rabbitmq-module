<?php


namespace Yetione\RabbitMQ\DTO;


use Yetione\DTO\DTOInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

abstract class Connectable implements DTOInterface
{
    /**
     * Восстанавливать или нет соединение с брокером сообщений автоматически, если текущее
     * соединение оборвалось.
     *
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("auto_reconnect")
     */
    private bool $autoReconnect = true;

    /**
     * Максимальное число попыток восстановления соединения.
     *
     * @var int
     * @Assert\Type(type="int")
     * @SerializedName("reconnect_retries")
     */
    private int $reconnectRetries = 5;

    /**
     * Пауза между попытками восстановить соединение.
     * Измеряется в микросекундах (1/1000000 секунды).
     *
     * @var int
     * @Assert\Type(type="int")
     * @SerializedName("reconnect_delay")
     */
    private int $reconnectDelay = 500000;

    /**
     * Имя соединения.
     *
     * @var string
     * @Assert\Type(type="string")
     * @SerializedName("connection")
     */
    private string $connection;

    public function __construct(string $connection)
    {
        $this->setConnection($connection);
    }

    /**
     * @return bool
     */
    public function isAutoReconnect(): bool
    {
        return $this->autoReconnect;
    }

    /**
     * @param bool $autoReconnect
     * @return Connectable
     */
    public function setAutoReconnect(bool $autoReconnect): Connectable
    {
        $this->autoReconnect = $autoReconnect;
        return $this;
    }

    /**
     * @return int
     */
    public function getReconnectRetries(): int
    {
        return $this->reconnectRetries;
    }

    /**
     * @param int $reconnectRetries
     * @return Connectable
     */
    public function setReconnectRetries(int $reconnectRetries): Connectable
    {
        $this->reconnectRetries = $reconnectRetries;
        return $this;
    }

    /**
     * @return int
     */
    public function getReconnectDelay(): int
    {
        return $this->reconnectDelay;
    }

    /**
     * @param int $reconnectDelay
     * @return Connectable
     */
    public function setReconnectDelay(int $reconnectDelay): Connectable
    {
        $this->reconnectDelay = $reconnectDelay;
        return $this;
    }

    /**
     * @return string
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * @param string $connection
     * @return Connectable
     */
    public function setConnection(string $connection): Connectable
    {
        $this->connection = $connection;
        return $this;
    }
}