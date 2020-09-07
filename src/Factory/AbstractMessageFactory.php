<?php


namespace Yetione\RabbitMQ\Factory;


use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

abstract class AbstractMessageFactory implements MessageFactoryInterface
{
    /**
     * @var string
     */
    protected $contentType = 'text/plain';

    /**
     * @var int
     */
    protected $deliveryMode = AMQPMessage::DELIVERY_MODE_PERSISTENT;

    /**
     * @var array
     */
    protected $defaultParameters;

    protected $extendArrayMessage = true;

    protected $extensionKey = '__extension';

    /**
     * @return array
     */
    public function getDefaultParameters(): array
    {
        if (null === $this->defaultParameters) {
            $this->setDefaultParameters([
                'content_type'=>$this->contentType,
                'delivery_mode'=>$this->deliveryMode
            ]);
        }
        return $this->defaultParameters;
    }

    /**
     * @param array $defaultParameters
     * @return $this
     */
    public function setDefaultParameters(array $defaultParameters): self
    {
        $this->defaultParameters = $defaultParameters;
        return $this;
    }

    /**
     * @param array $parameters
     * @return array
     */
    protected function getMessageParameters(array $parameters=[]): array
    {
        return array_merge($this->getDefaultParameters(), $parameters);
    }

    /**
     * @param string $contentType
     * @return $this
     */
    public function setContentType(string $contentType): AbstractMessageFactory
    {
        $this->contentType = $contentType;
        return $this;
    }

    /**
     * @param int $deliveryMode
     * @return $this
     */
    public function setDeliveryMode(int $deliveryMode): AbstractMessageFactory
    {
        $this->deliveryMode = $deliveryMode;
        return $this;
    }

    protected function addHeadersToMessage(AMQPMessage $message, array $headers=null): AMQPMessage
    {
        if (!empty($headers)) {
            $oHeadersTable = new AMQPTable($headers);
            $message->set('application_headers', $oHeadersTable);
        }
        return $message;
    }

    protected function extendArray(array $body): array
    {
        if ($this->extendArrayMessage && !isset($body[$this->extensionKey])) {
            $aExtension = [];
            if (!empty($aExtension)) {
                $body[$this->extensionKey] = $aExtension;
            }
        }
        return $body;
    }
}