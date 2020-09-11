<?php


namespace Yetione\RabbitMQ\DTO;

use Yetione\DTO\DTOInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class ConnectionOptions implements DTOInterface
{

    /**
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("insist")
     */
    private $insist;

    /**
     * @var string
     * @Assert\Type(type="string")
     * @SerializedName("login_method")
     */
    private $loginMethod;

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
    private $locale;

    /**
     * @var float
     * @Assert\Type(type="float")
     * @Assert\NotBlank()
     * @SerializedName("connection_timeout")
     */
    private $connectionTimeout;

    /**
     * @var float
     * @Assert\Type(type="float")
     * @Assert\NotBlank()
     * @SerializedName("read_write_timeout")
     */
    private $readWriteTimeout;

    /**
     * @var array|null
     * @Assert\Type(type={"array", "null"})
     * @SerializedName("context_options")
     */
    private $contextOptions;

    /**
     * @var array|null
     * @Assert\Type(type={"array", "null"})
     * @SerializedName("context_params")
     */
    private $contextParams;

    /**
     * @var bool
     * @Assert\Type(type="bool")
     * @SerializedName("keepalive")
     */
    private $keepalive;

    /**
     * @var int
     * @Assert\Type(type="int")
     * @SerializedName("heartbeat")
     */
    private $heartbeat;

    /**
     * @var float
     * @Assert\Type(type="float")
     * @SerializedName("channel_rpc_timeout")
     */
    private $channelRpcTimeout;

    /**
     * @var string|null
     * @Assert\Type(type={"string", "null"})
     * @SerializedName("ssl_protocol")
     */
    private $sslProtocol;

    /**
     * @var string
     * @Assert\Type(type="string")
     * @Assert\Choice({\Yetione\RabbitMQ\Constant\Connection::TYPE_LAZY, \Yetione\RabbitMQ\Constant\Connection::TYPE_NORMAL})
     * @SerializedName("connection_type")
     */
    private $connectionType;

    /**
     * Connection constructor.
     * @param float $connectionTimeout
     * @param float $readWriteTimeout
     * @param bool $keepalive
     * @param int $heartbeat
     * @param bool $insist
     * @param string $loginMethod
     * @param string $locale
     * @param array|null $contextOptions
     * @param array|null $contextParams
     * @param float $channelRpcTimeout
     * @param string|null $sslProtocol
     * @param string $connectionType
     */
    public function __construct(
        float $connectionTimeout = 3.0, float $readWriteTimeout = 3.0, bool $keepalive = false, int $heartbeat = 0,
        bool $insist = false, string $loginMethod = 'AMQPLAIN', string $locale = 'en_US', ?array $contextOptions = null,
        ?array $contextParams = null, float $channelRpcTimeout = 0.0, ?string $sslProtocol = null, string $connectionType='normal'
    )
    {
        $this->insist = $insist;
        $this->loginMethod = $loginMethod;
        $this->locale = $locale;
        $this->connectionTimeout = $connectionTimeout;
        $this->readWriteTimeout = $readWriteTimeout;
        $this->contextOptions = $contextOptions;
        $this->contextParams = $contextParams;
        $this->keepalive = $keepalive;
        $this->heartbeat = $heartbeat;
        $this->channelRpcTimeout = $channelRpcTimeout;
        $this->sslProtocol = $sslProtocol;
        $this->loginResponse = null;
        $this->connectionType = $connectionType;
    }

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