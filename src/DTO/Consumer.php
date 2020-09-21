<?php


namespace Yetione\RabbitMQ\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class Consumer extends Connectable
{

    protected array $bindings;
}