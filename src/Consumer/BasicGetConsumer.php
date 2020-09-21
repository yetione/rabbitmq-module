<?php


namespace Yetione\RabbitMQ\Consumer;


use PhpAmqpLib\Message\AMQPMessage;
use Yetione\RabbitMQ\Event\ConsumerEvent;
use Yetione\RabbitMQ\Event\OnConsumeEvent;

abstract class BasicGetConsumer extends AbstractConsumer
{
    protected function isMessageExists(): bool
    {
        return true;
    }

    protected function setupConsume()
    {
        parent::setupConsume();
        $oQueue=$this->getQueue();
        $this->eventDispatcher->listen(
            ConsumerEvent::ON_CONSUME,
            function (OnConsumeEvent $event) use ($oQueue) {
                $oMessage = $this->channel()->basic_get($oQueue->getName());
                if ($oMessage instanceof AMQPMessage) {
                    $this->processMessageFromQueue($oMessage);
                }
            },
            1000
        );
    }

    protected function setupQosOptions()
    {
        // There is not QoS options for basic_get consumer
    }

    protected function wait(array $waitTimeout)
    {
        if (0 < ($iWaitingTime = $waitTimeout['seconds'] ?? 0)) {
            sleep($iWaitingTime);
        }
    }
}