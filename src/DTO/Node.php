<?php


namespace Yetione\RabbitMQ\DTO;

use Yetione\DTO\DTOInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class Node implements DTOInterface
{
    /**
     * @var string
     * @Assert\Type(type="string")
     * @Assert\NotBlank()
     * @SerializedName("host")
     */
    private $host;

    /**
     * @var int
     * @Assert\Type(type="int")
     */
    private $port;

    /**
     * @var Credentials|null
     * @Assert\Type(type={"\Yetione\RabbitMQ\DTO\Credentials", "null"})
     * @SerializedName("credentials")
     */
    private $credential;

    /**
     * Node constructor.
     * @param string $host
     * @param int $port
     * @param Credentials|null $credential
     */
    public function __construct(string $host, int $port, ?Credentials $credential = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->credential = $credential;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return Credentials|null
     */
    public function getCredential(): ?Credentials
    {
        return $this->credential;
    }
}