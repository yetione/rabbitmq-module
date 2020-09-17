<?php


namespace Yetione\RabbitMQ\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Producer
 * @package Yetione\RabbitMQ\DTO
 *
 * @method Producer setExchange(string $exchange)
 * @method Producer setType(string $type)
 * @method Producer setPublishRetries(int $publishRetries)
 */
class Producer extends Connectable
{

    /**
     * @var string
     * @Assert\Type(type="string")
     * @SerializedName("type")
     */
    private string $type;

    /**
     * @var string
     * @Assert\Type(type="string")
     * @SerializedName("exchange")
     */
    private string $exchange;

    /**
     * Кол-во попыток повторной отправки сообщения.
     *
     * @var int
     * @Assert\Type(type="int")
     * @Assert\GreaterThanOrEqual(0)
     * @SerializedName("publish_retries")
     */
    private int $publishRetries = 0;

    public function __construct(string $type, string $exchange, string $connection)
    {
        parent::__construct($connection);
        $this->setType($type)->setExchange($exchange);
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