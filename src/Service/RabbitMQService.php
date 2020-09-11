<?php


namespace Yetione\RabbitMQ\Service;

use Throwable;
use Exception;
use Yetione\RabbitMQ\Consumer\AbstractConsumer;

class RabbitMQService
{
    /**
     * @var int
     */
    protected int $consumerErrorStatusCode = 3;

    /**
     * @param AbstractConsumer $consumer
     * @param string|null $consumerTag
     * @return int
     */
    public function runConsumer(AbstractConsumer $consumer, string $consumerTag=null): int
    {
        $consumerTag = null === $consumerTag ? uniqid(get_class($consumer), true) : $consumerTag;
        try {
            if (empty($consumer->getConsumerTag())) {
                $consumer->setConsumerTag($consumerTag);
            }
        } catch (Exception | Throwable $e) {
            $consumer->setConsumerTag($consumerTag);
        }
        try {
            $iExitCode = $consumer->start();
        } catch (Exception | Throwable $e) {
            // TODO: Log
            $iExitCode = $this->getConsumerErrorStatusCode();
        }
        return $iExitCode;
    }

    /**
     * @return int
     */
    public function getConsumerErrorStatusCode(): int
    {
        return $this->consumerErrorStatusCode;
    }

    /**
     * @param int $consumerErrorStatusCode
     * @return RabbitMQService
     */
    public function setConsumerErrorStatusCode(int $consumerErrorStatusCode): self
    {
        $this->consumerErrorStatusCode = $consumerErrorStatusCode;
        return $this;
    }
}