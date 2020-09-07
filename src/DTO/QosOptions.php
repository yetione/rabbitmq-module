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
    private $prefetchSize;

    /**
     * @var int|null
     * @Assert\Type({"int", "null"})
     * @SerializedName("prefetch_count")
     */
    private $prefetchCount;

    /**
     * @var bool|null
     * @Assert\Type({"bool", "null"})
     * @SerializedName("global")
     */
    private $global;

    /**
     * QosOptions constructor.
     * @param int|null $prefetchSize
     * @param int|null $prefetchCount
     * @param bool|null $global
     */
    public function __construct(?int $prefetchSize, ?int $prefetchCount, ?bool $global)
    {
        $this->prefetchSize = $prefetchSize;
        $this->prefetchCount = $prefetchCount;
        $this->global = $global;
    }

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