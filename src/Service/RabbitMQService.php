<?php


namespace Yetione\RabbitMQ\Service;

use Throwable;
use Exception;
use Yetione\RabbitMQ\Consumer\AbstractConsumer;
use Yetione\RabbitMQ\Logger\Loggable;
use Yetione\RabbitMQ\Logger\WithLogger;

class RabbitMQService implements Loggable
{
    use WithLogger;

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
            $this->getLogger()->error($e->getMessage());
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