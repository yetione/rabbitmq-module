<?php


namespace Yetione\RabbitMQ\Producer;


use PhpAmqpLib\Exception\AMQPChannelClosedException;
use PhpAmqpLib\Exception\AMQPConnectionBlockedException;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Message\AMQPMessage;
use Yetione\RabbitMQ\Message\Factory\MessageFactoryInterface;
use Yetione\RabbitMQ\Message\Factory\ReusableMessageFactory;

abstract class SingleProducer extends AbstractProducer
{

    /**
     * @param AMQPMessage $message
     * @param string $routingKey
     * @param bool $mandatory
     * @param bool $immediate
     * @param int|null $ticket
     * @return ProducerInterface
     */
    final public function publish(AMQPMessage $message, string $routingKey='', bool $mandatory = false, bool $immediate=false, ?int $ticket = null): ProducerInterface
    {
        $oExchange = $this->getExchange();
        $this->beforePublish();
        try {
            $this->channel()
                ->basic_publish($message, $oExchange->getName(), $routingKey, $mandatory, $immediate, $ticket);
        } catch (AMQPConnectionClosedException | AMQPChannelClosedException $e) {
            // TODO: Log
            $this->maybeReconnect();
            if ($this->isNeedRetry()) {
                return $this->publish($message, $routingKey, $mandatory, $immediate, $ticket);
            }
            $this->afterPublish($message, $e);
            return $this;
        } catch (AMQPConnectionBlockedException $e) {
            // TODO: Log
            $this->afterPublish($message, $e);
            return $this;
        }
        $this->afterPublish($message);
        return $this;
    }

    public function getMessageFactory(): MessageFactoryInterface
    {
        if (null === $this->messageFactory) {
            $this->setMessageFactory(new ReusableMessageFactory());
        }
        return parent::getMessageFactory();
    }
}