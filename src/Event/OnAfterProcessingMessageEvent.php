<?php


namespace Yetione\RabbitMQ\Event;


use Exception;
use Throwable;

class OnAfterProcessingMessageEvent extends ConsumerEvent
{
    protected $name = ConsumerEvent::AFTER_PROCESSING_MESSAGE;

    /**
     * @var Exception|Throwable|null
     */
    protected $error;

    /**
     * @param Exception|Throwable|null $error
     * @return $this
     */
    public function setError($error): self
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return Exception|Throwable|null
     */
    public function getError()
    {
        return $this->error;
    }

    public function isSuccess(): bool
    {
        return null === $this->getError();
    }
}