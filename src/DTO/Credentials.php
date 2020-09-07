<?php


namespace Yetione\RabbitMQ\DTO;

use Yetione\DTO\DTOInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class Credentials implements DTOInterface
{
    /**
     * @var string
     * @Assert\Type(type="string")
     * @Assert\NotBlank()
     * @SerializedName("username")
     */
    private $username;

    /**
     * @var string
     * @Assert\Type(type="string")
     * @Assert\NotBlank()
     * @SerializedName("password")
     */
    private $password;

    /**
     * @var string
     * @Assert\Type(type="string")
     * @Assert\NotBlank()
     * @SerializedName("vhost")
     */
    private $vhost;

    /**
     * Credential constructor.
     * @param string $username
     * @param string $password
     * @param string $vhost
     */
    public function __construct(string $username, string $password, string $vhost = '/')
    {
        $this->username = $username;
        $this->password = $password;
        $this->vhost = $vhost;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getVhost(): string
    {
        return $this->vhost;
    }
}