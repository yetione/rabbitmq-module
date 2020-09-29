<?php


namespace Yetione\RabbitMQ\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Producer
 * @package Yetione\RabbitMQ\DTO
 *
 * @property  string $exchange
 * @property string $type
 * @property int $publishRetries
 */
class Producer extends Connectable
{

    /**
     * @var string
     * @Assert\Type(type="string")
     * @SerializedName("type")
     */
    protected string $type;

    /**
     * @var string
     * @Assert\Type(type="string")
     * @SerializedName("exchange")
     */
    protected string $exchange;

    /**
     * Кол-во попыток повторной отправки сообщения.
     *
     * @var int
     * @Assert\Type(type="int")
     * @Assert\GreaterThanOrEqual(0)
     * @SerializedName("publish_retries")
     */
    protected int $publishRetries = 0;

    public function __construct(string $type, string $exchange, string $connection)
    {
        parent::__construct($connection);
        $this->type = $type;
        $this->exchange = $exchange;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getExchange(): string
    {
        return $this->exchange;
    }

    /**
     * @return int
     */
    public function getPublishRetries(): int
    {
        return $this->publishRetries;
    }
}