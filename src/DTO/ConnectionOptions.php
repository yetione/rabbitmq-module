<?php


namespace Yetione\RabbitMQ\DTO;

use Yetione\DTO\DTOInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Yetione\DTO\Support\MagicSetter;
use Yetione\RabbitMQ\Constant\Connection;

class ConnectionOptions implements DTOInterface
{
    use MagicSetter;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("insist")
     */
    private bool $insist = false;

    /**
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
    private string $loginMethod = Connection::LOGIN_METHOD_AMQPPLAIN;

    /**
     * @var null
     * @Assert\Type(type="null")
     * @SerializedName("login_response")
     */
    private $loginResponse;

    /**
     * @var string
     * @Assert\Type(type="string")
     * @Assert\NotBlank()
     * @SerializedName("locale")
     */
    private string $locale = 'en_US';

    /**
     * @var float
     * @Assert\Type(type="float")
     * @Assert\NotBlank()
     * @SerializedName("connection_timeout")
     */
    private float $connectionTimeout = 3;

    /**
     * @var float
     * @Assert\Type(type="float")
     * @Assert\NotBlank()
     * @SerializedName("read_write_timeout")
     */
    private float $readWriteTimeout = 3;

    /**
     * @var array|null
     * @Assert\Type(type={"array", "null"})
     * @SerializedName("context_options")
     */
    private ?array $contextOptions = null;

    /**
     * @var array|null
     * @Assert\Type(type={"array", "null"})
     * @SerializedName("context_params")
     */
    private ?array $contextParams = null;

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
     * @var string|null
     * @Assert\Type(type={"string", "null"})
     * @SerializedName("ssl_protocol")
     */
    private ?string $sslProtocol = null;

    /**
     * @var string
     * @Assert\Type(type="string")
     * @Assert\Choice({
     *     \Yetione\RabbitMQ\Constant\Connection::TYPE_LAZY,
     *     \Yetione\RabbitMQ\Constant\Connection::TYPE_NORMAL
     * })
     * @SerializedName("connection_type")
     */
    private string $connectionType = Connection::TYPE_NORMAL;

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
     * @return float
     */
    public function getConnectionTimeout(): float
    {
        return $this->connectionTimeout;
    }

    /**
     * @return float
     */
    public function getReadWriteTimeout(): float
    {
        return $this->readWriteTimeout;
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
     * @return string|null
     */
    public function getSslProtocol(): ?string
    {
        return $this->sslProtocol;
    }

    /**
     * @return string
     */
    public function getConnectionType(): string
    {
        return $this->connectionType;
    }
}