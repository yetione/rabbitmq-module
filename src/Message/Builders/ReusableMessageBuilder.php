<?php


namespace Yetione\RabbitMQ\Message\Builders;


use PhpAmqpLib\Message\AMQPMessage;

class ReusableMessageBuilder extends MessageBuilder
{
    protected AMQPMessage $message;

    protected function getMessage(string $body = '', array $properties = []): AMQPMessage
    {
        if (!isset($this->message)) {
            $this->message = parent::getMessage();
        }
        $this->message->setBody($body);
        foreach ($properties as $key => $value) {
            $this->message->set($key, $value);
        }
        return $this->message;
    }
}