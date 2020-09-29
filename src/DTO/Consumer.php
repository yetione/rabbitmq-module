<?php


namespace Yetione\RabbitMQ\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class Consumer extends Connectable
{

    /**
     * @var string
     * @Assert\Type(type="string")
     * @SerializedName("type")
     */
    protected string $type;

    /**
     * @var QosOptions|null
     * @Assert\Type({"\Yetione\RabbitMQ\DTO\QosOptions", null})
     * @SerializedName("qos")
     */
    protected ?QosOptions $qos = null;

    public function __construct(string $type, string $connection)
    {
        parent::__construct($connection);
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return QosOptions|null
     */
    public function getQos(): ?QosOptions
    {
        return $this->qos;
    }
}