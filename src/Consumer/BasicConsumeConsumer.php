<?php


namespace Yetione\RabbitMQ\Consumer;


use ErrorException;
use PhpAmqpLib\Exception\AMQPOutOfBoundsException;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exception\AMQPTimeoutException;

abstract class BasicConsumeConsumer extends AbstractConsumer
{
    protected function isMessageExists(): bool
    {
        return $this->channel()->is_consuming();
    }

    public function stop()
    {
        try {
            $this->channel()->basic_cancel($this->getConsumerTag(), false, true);
        } catch (AMQPTimeoutException $e) {
            // TODO: Log
        }
        parent::stop();
    }

    /**
     * @throws AMQPTimeoutException
     */
    protected function setupConsume()
    {
        parent::setupConsume();
        $oQueue=$this->getQueue();
        $this->channel()->basic_consume(
            $oQueue->getName(),
            $this->getConsumerTag(),
            false, // No local
            false, // No ACK
            false, // Exclusive
            false, // No wait
            [$this, 'processMessageFromQueue'] // Callback
        );
    }

    /**
     * @param array $waitTimeout
     * @throws AMQPTimeoutException
     * @throws ErrorException
     * @throws AMQPOutOfBoundsException
     * @throws AMQPRuntimeException
     */
    protected function wait(array $waitTimeout)
    {
        $this->channel()->wait(null, false, $waitTimeout['seconds']);
    }
}