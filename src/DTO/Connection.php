<?php


namespace Yetione\RabbitMQ\DTO;

use Yetione\DTO\DTOInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Yetione\DTO\Support\MagicSetter;
use Yetione\RabbitMQ\Constant\Connection as ConnectionEnum;

class Connection implements DTOInterface
{
    use MagicSetter;

    /**
     * Хз что это. Вроде как deprecated, но подтверждения не нашел.
     *
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("insist")
     */
    private bool $insist = false;

    /**
     * Метод авторизации. Тоже доки толком не нашел.
     *
     * @var string
     * @Assert\Type(type="string")
     * @Assert\Choice({
     *     \Yetione\RabbitMQ\Constant\Connection::LOGIN_METHOD_PLAIN,
     *     \Yetione\RabbitMQ\Constant\Connection::LOGIN_METHOD_RABBIT_CR_DEMO,
     *     \Yetione\RabbitMQ\Constant\Connection::LOGIN_METHOD_AMQPPLAIN,
     *     \Yetione\RabbitMQ\Constant\Connection::LOGIN_METHOD_EXTERNAL
     * })
     * @SerializedName("login_method")
     */
    private string $loginMethod = ConnectionEnum::LOGIN_METHOD_AMQPPLAIN;

    /**
     * Deprecated штука
     *
     * @var null
     * @Assert\Type(type="null")
     * @SerializedName("login_response")
     */
    private $loginResponse = null;

    /**
     * Локализация ответов.
     *
     * @var string
     * @Assert\Type(type="string")
     * @Assert\NotBlank()
     * @SerializedName("locale")
     */
    private string $locale = 'en_US';

    /**
     * Timeout на операции чтения.
     * Только для SOCKET соединений.
     *
     * @var float
     * @Assert\Type(type="float")
     * @Assert\NotBlank()
     * @SerializedName("read_timeout")
     */
    private float $readTimeout = 3.0;

    /**
     * Timeout на операции записи.
     * Только для SOCKET соединений.
     *
     * @var float
     * @Assert\Type(type="float")
     * @Assert\NotBlank()
     * @SerializedName("write_timeout")
     */
    private float $writeTimeout = 3.0;

    /**
     * Timeout на установку соединения.
     * Только для STREAM соединений.
     *
     * @var float
     * @Assert\Type(type="float")
     * @Assert\NotBlank()
     * @SerializedName("connection_timeout")
     */
    private float $connectionTimeout = 3.0;

    /**
     * Только для STREAM соединений.
     *
     * @var array|null
     * @Assert\Type(type={"array", "null"})
     * @SerializedName("context_options")
     */
    private ?array $contextOptions = null;

    /**
     * Только для STREAM соединений.
     *
     * @var array|null
     * @Assert\Type(type={"array", "null"})
     * @SerializedName("context_params")
     */
    private ?array $contextParams = null;

    /**
     * Timeout на операции чтения/записи.
     * Только для STREAM соединений.
     *
     * @var float
     * @Assert\Type(type="float")
     * @Assert\NotBlank()
     * @SerializedName("read_write_timeout")
     */
    private float $readWriteTimeout = 130.0;

    /**
     * Только для STREAM соединений.
     *
     * @var string|null
     * @Assert\Type(type={"string", "null"})
     * @SerializedName("ssl_protocol")
     */
    private ?string $sslProtocol = null;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("keepalive")
     */
    private bool $keepalive = false;

    /**
     * @var int
     * @Assert\Type(type="int")
     * @SerializedName("heartbeat")
     */
    private int $heartbeat = 0;

    /**
     * @var float
     * @Assert\Type(type="float")
     * @SerializedName("channel_rpc_timeout")
     */
    private float $channelRpcTimeout = 0;

    /**
     * Опции для создания SSL контекста.
     * Только для STREAM соединений.
     *
     * @var array
     * @Assert\Type(type="array")
     * @SerializedName("ssl_options")
     */
    private array $sslOptions = [];

    /**
     * Тип соединения
     *
     * @var string
     * @Assert\Type(type="string")
     * @SerializedName("connection_type")
     */
    private string $connectionType = ConnectionEnum::TYPE_STREAM_NORMAL;

    /**
     * @return bool
     */
    public function isInsist(): bool
    {
        return $this->insist;
    }

    /**
     * @return string
     */
    public function getLoginMethod(): string
    {
        return $this->loginMethod;
    }

    /**
     * @return null
     */
    public function getLoginResponse()
    {
        return $this->loginResponse;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return resource|null
     */
    public function getContext()
    {
        if (null === $this->getContextOptions() && null === $this->getContextParams()) {
            return null;
        }
        return stream_context_create($this->getContextOptions(), $this->getContextParams());
    }

    /**
     * @return bool
     */
    public function isKeepalive(): bool
    {
        return $this->keepalive;
    }

    /**
     * @return int
     */
    public function getHeartbeat(): int
    {
        return $this->heartbeat;
    }

    /**
     * @return float
     */
    public function getChannelRpcTimeout(): float
    {
        return $this->channelRpcTimeout;
    }

    /**
     * @return string
     */
    public function getConnectionType(): string
    {
        return $this->connectionType;
    }

    /**
     * @return float
     */
    public function getReadTimeout(): float
    {
        return $this->readTimeout;
    }

    /**
     * @return float
     */
    public function getWriteTimeout(): float
    {
        return $this->writeTimeout;
    }

    /**
     * @return float
     */
    public function getConnectionTimeout(): float
    {
        return $this->connectionTimeout;
    }

    /**
     * @return array|null
     */
    public function getContextOptions(): ?array
    {
        return $this->contextOptions;
    }

    /**
     * @return array|null
     */
    public function getContextParams(): ?array
    {
        return $this->contextParams;
    }

    /**
     * @return float
     */
    public function getReadWriteTimeout(): float
    {
        return $this->readWriteTimeout;
    }

    /**
     * @return string|null
     */
    public function getSslProtocol(): ?string
    {
        return $this->sslProtocol;
    }

    /**
     * @return array
     */
    public function getSslOptions(): array
    {
        return $this->sslOptions;
    }
}