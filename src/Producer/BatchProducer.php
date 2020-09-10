<?php


namespace Yetione\RabbitMQ\Producer;


use PhpAmqpLib\Message\AMQPMessage;
use Yetione\RabbitMQ\Event\EventDispatcherInterface;
use Yetione\RabbitMQ\Event\OnAfterFlushingMessageEvent;
use Yetione\RabbitMQ\Event\OnBeforeFlushingMessageEvent;
use Yetione\RabbitMQ\Factory\MessageFactoryInterface;
use Yetione\RabbitMQ\Factory\NewMessageFactory;

abstract class BatchProducer extends AbstractProducer
{
    protected int $batchSize = 50;

    protected int $currentBatchSize = 0;

    protected EventDispatcherInterface $eventDispatcher;

    public function resetCurrentBatch(): self
    {
        $this->currentBatchSize = 0;
        return $this;
    }

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
        $this->getConnectionWrapper()->getChannel()
            ->batch_basic_publish($message, $oExchange->getName(), $routingKey, $mandatory, $immediate, $ticket);
        $this->currentBatchSize++;
        $this->afterPublish($message);
        return $this;
    }

    protected function afterPublish(AMQPMessage $message)
    {
        parent::afterPublish($message);
        $this->flushMessage(false);
    }

    public function flushMessage(bool $force=true): self
    {
        if (($force || 0 === $this->currentBatchSize % $this->getBatchSize())) {
            $this->eventDispatcher->dispatch((new OnBeforeFlushingMessageEvent())->setProducer($this));
            $this->getConnectionWrapper()->getChannel()->publish_batch();
            $this->eventDispatcher->dispatch((new OnAfterFlushingMessageEvent())->setProducer($this));
            $this->resetCurrentBatch();
        }
        return $this;
    }

    protected function closeProducer()
    {
        $this->flushMessage();
        parent::closeProducer();
    }

    public function getMessageFactory(): MessageFactoryInterface
    {
        if (null === $this->messageFactory) {
            $this->messageFactory = new NewMessageFactory();
        }
        return parent::getMessageFactory();
    }

    /**
     * @return int
     */
    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    /**
     * @param int $batchSize
     * @return BatchProducer
     */
    public function setBatchSize(int $batchSize): BatchProducer
    {
        $this->batchSize = $batchSize;
        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentBatchSize(): int
    {
        return $this->currentBatchSize;
    }

    /**
     * @param int $currentBatchSize
     * @return BatchProducer
     */
    public function setCurrentBatchSize(int $currentBatchSize): BatchProducer
    {
        $this->currentBatchSize = $currentBatchSize;
        return $this;
    }


}