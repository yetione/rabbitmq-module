<?php


namespace Yetione\RabbitMQ\DTO;

use Yetione\DTO\DTOInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class QosOptions implements DTOInterface
{

    /**
     * @var int|null
     * @Assert\Type({"int", "null"})
     * @SerializedName("prefetch_size")
     */
    private ?int $prefetchSize = null;

    /**
     * @var int|null
     * @Assert\Type({"int", "null"})
     * @SerializedName("prefetch_count")
     */
    private ?int $prefetchCount = null;

    /**
     * @var bool|null
     * @Assert\Type({"bool", "null"})
     * @SerializedName("global")
     */
    private ?bool $global = null;

    /**
     * @return int|null
     */
    public function getPrefetchSize(): ?int
    {
        return $this->prefetchSize;
    }

    /**
     * @param int|null $prefetchSize
     * @return QosOptions
     */
    public function setPrefetchSize(?int $prefetchSize): QosOptions
    {
        $this->prefetchSize = $prefetchSize;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPrefetchCount(): ?int
    {
        return $this->prefetchCount;
    }

    /**
     * @param int|null $prefetchCount
     * @return QosOptions
     */
    public function setPrefetchCount(?int $prefetchCount): QosOptions
    {
        $this->prefetchCount = $prefetchCount;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getGlobal(): ?bool
    {
        return $this->global;
    }

    /**
     * @param bool|null $global
     * @return QosOptions
     */
    public function setGlobal(?bool $global): QosOptions
    {
        $this->global = $global;
        return $this;
    }
}