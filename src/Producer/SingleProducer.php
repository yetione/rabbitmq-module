<?php


namespace Yetione\RabbitMQ\Producer;


use PhpAmqpLib\Exception\AMQPChannelClosedException;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Message\AMQPMessage;
use Yetione\RabbitMQ\Factory\MessageFactoryInterface;
use Yetione\RabbitMQ\Factory\ReusableMessageFactory;

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
        $aLoggerContext = [
            'message'=>[
                'body'=>$message->getBody(),
                'routing_key'=>$routingKey,
                'properties'=>$message->get_properties(),
            ]
        ];
        $this->beforePublish();
        try {
            $this->getConnectionWrapper()->getChannel()
                ->basic_publish($message, $oExchange->getName(), $routingKey, $mandatory, $immediate, $ticket);;
        } catch (AMQPChannelClosedException $e) {
            // TODO: Log
            if ($this->autoReconnect) {
                $this->getConnectionWrapper()->closeChannel()->getChannel();
            }
            return $this;
        } catch (AMQPConnectionClosedException $e) {
            // TODO: Log
            if ($this->autoReconnect) {
                $this->getConnectionWrapper()->reconnect();
            }
            return $this;
        }
        $this->afterPublish($message);
        return $this;
    }

    public function getMessageFactory(): MessageFactoryInterface
    {
        if (null === $this->messageFactory) {
            $this->messageFactory = new ReusableMessageFactory();
        }
        return parent::getMessageFactory();
    }
}