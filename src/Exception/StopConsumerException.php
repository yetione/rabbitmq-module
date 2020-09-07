<?php


namespace Yetione\RabbitMQ\Exception;


use Yetione\RabbitMQ\Constant\Consumer;

/**
 * Если выбрасывается это исключение, то консьюмер завершает работу, сообщение не подтверждается
 * и возвращается обратно в очередь.
 * Class StopConsumerException
 * @package RabbitMQ\Exception
 */
class StopConsumerException extends ConsumerException
{
    public function getResultCode(): int
    {
        return Consumer::RESULT_REJECT_REQUEUE;
    }
}