<?php


namespace Yetione\RabbitMQ\Exception;


use Throwable;

class ConnectionRestoredException extends ConnectionException
{
    /**
     * @var bool
     */
    protected $connectionState;

    public function __construct($message = "", bool $connectionState=false, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->connectionState = $connectionState;
    }

    /**
     * @return bool
     */
    public function getConnectionState(): bool
    {
        return $this->connectionState;
    }
}