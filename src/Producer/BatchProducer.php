<?php


namespace Yetione\RabbitMQ\Producer;


use PhpAmqpLib\Exception\AMQPChannelClosedException;
use PhpAmqpLib\Exception\AMQPConnectionBlockedException;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
use Yetione\RabbitMQ\Event\OnAfterFlushingMessageEvent;
use Yetione\RabbitMQ\Event\OnBeforeFlushingMessageEvent;
use Yetione\RabbitMQ\Event\OnErrorFlushingMessageEvent;
use Yetione\RabbitMQ\Message\Factory\MessageFactoryInterface;
use Yetione\RabbitMQ\Message\Factory\NewMessageFactory;

abstract class BatchProducer extends AbstractProducer
{
    protected int $batchSize = 50;

    protected int $currentBatchSize = 0;

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
        $this->channel()
            ->batch_basic_publish($message, $oExchange->getName(), $routingKey, $mandatory, $immediate, $ticket);
        $this->currentBatchSize++;
        $this->afterPublish($message);
        return $this;
    }

    protected function afterPublish(AMQPMessage $message, ?Throwable $e=null)
    {
        parent::afterPublish($message, $e);
        if (null !== $e) {
            $this->flushMessage(false);
        }
    }

    public function flushMessage(bool $force=true): self
    {
        if (($force || 0 === $this->currentBatchSize % $this->getBatchSize())) {
            $this->tryPublish();
            if (1 === $this->currentPublishTry()) {
                $this->eventDispatcher->dispatch((new OnBeforeFlushingMessageEvent())->setProducer($this));
            }
            try {
                $this->channel()->publish_batch();
                $this->eventDispatcher->dispatch((new OnAfterFlushingMessageEvent())->setProducer($this));
                $this->resetCurrentBatch();
            } catch (AMQPConnectionClosedException | AMQPChannelClosedException $e) {
                // TODO: Log
                $this->maybeReconnect();
                if ($this->isNeedRetry()) {
                    return $this->flushMessage($force);
                }
                $this->eventDispatcher->dispatch((new OnErrorFlushingMessageEvent())->setProducer($this)->setParams(['error'=>$e]));
            } catch (AMQPConnectionBlockedException $e) {
                // TODO: Log
                $this->eventDispatcher->dispatch((new OnErrorFlushingMessageEvent())->setProducer($this)->setParams(['error'=>$e]));
            }
            $this->resetPublishTries();
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
        if (!isset($this->messageFactory)) {
            $this->setMessageFactory(new NewMessageFactory());
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