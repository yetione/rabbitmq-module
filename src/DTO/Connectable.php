<?php


namespace Yetione\RabbitMQ\DTO;


use Yetione\DTO\DTOInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Yetione\DTO\Support\MagicSetter;

/**
 * Class Connectable
 * @package Yetione\RabbitMQ\DTO
 *
 * @property string $connection)
 * @property bool $autoReconnect
 * @property int $reconnectRetries
 * @property int $reconnectDelay
 * @property int $reconnectInterval
 * @property ?string $connectionAlias
 */
abstract class Connectable implements DTOInterface
{
    use MagicSetter;

    /**
     * Восстанавливать или нет соединение с брокером сообщений автоматически, если текущее
     * соединение оборвалось.
     *
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("auto_reconnect")
     */
    protected bool $autoReconnect = true;

    /**
     * Максимальное число попыток восстановления соединения.
     *
     * @var int
     * @Assert\Type(type="int")
     * @SerializedName("reconnect_retries")
     */
    protected int $reconnectRetries = 5;

    /**
     * Пауза между закрытием старого и открытием нового соединения
     * Измеряется в микросекундах (1/1000000 секунды).
     *
     * @var int
     * @Assert\Type(type="int")
     * @SerializedName("reconnect_delay")
     */
    protected int $reconnectDelay = 2000;

    /**
     * Пауза между попытками восстановить соединение.
     * Измеряется в микросекундах (1/1000000 секунды).
     *
     * @var int
     * @Assert\Type(type="int")
     * @SerializedName("reconnect_interval")
     */
    protected int $reconnectInterval = 500000;

    /**
     * Имя соединения.
     *
     * @var string
     * @Assert\Type(type="string")
     * @SerializedName("connection")
     */
    protected string $connection;

    /**
     * Алиас для соединения.
     *
     * @var string|null
     * @Assert\Type(type={"string", "null"})
     * @SerializedName("connection_alias")
     */
    protected ?string $connectionAlias = null;

    public function __construct(string $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return bool
     */
    public function isAutoReconnect(): bool
    {
        return $this->autoReconnect;
    }

    /**
     * @return int
     */
    public function getReconnectRetries(): int
    {
        return $this->reconnectRetries;
    }

    /**
     * @return int
     */
    public function getReconnectDelay(): int
    {
        return $this->reconnectDelay;
    }

    /**
     * @return int
     */
    public function getReconnectInterval(): int
    {
        return $this->reconnectInterval;
    }

    /**
     * @return string
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * @return string|null
     */
    public function getConnectionAlias(): ?string
    {
        return $this->connectionAlias;
    }
}