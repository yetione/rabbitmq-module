<?php


namespace Yetione\RabbitMQ\Consumer;


use Exception;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

interface ConsumerInterface
{
    /**
     * Метод обрабатывает сообщение из очереди и отвечает за его подтверждение.
     * @param AMQPMessage $message
     * @throws Throwable
     */
    public function processMessageFromQueue(AMQPMessage $message);

    /**
     * Метод выполняет подготовку консьюмера.
     * @return bool
     */
    public function setup(): bool;

    /**
     * Метод запускает консьюмер
     * @return int
     * @throws Exception
     */
    public function start(): int;

    /**
     * Метод выполняется в конце работы консьюмера.
     */
    public function stop();
}