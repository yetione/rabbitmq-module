<?php


namespace Yetione\RabbitMQ\Factory;


use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Yetione\Json\Json;

class NewMessageFactory extends AbstractMessageFactory
{
    public function createMessage(string $body = '', array $parameters = [], array $headers = null): AMQPMessage
    {
        $oMessage = new AMQPMessage($body, $this->getMessageParameters($parameters));
        if (!empty($headers)) {
            $oHeadersTable = new AMQPTable($headers);
            $oMessage->set('application_headers', $oHeadersTable);
        }
        return $this->addHeadersToMessage($oMessage, $headers);
    }

    public function fromArray(array $body, array $parameters = [], array $header = null): AMQPMessage
    {
        return $this->createMessage(Json::encode($this->extendArray($body)), $parameters, $header);
    }
}