<?php


namespace Yetione\RabbitMQ\Message\Builders;


use Illuminate\Support\Collection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

class MessageBuilder
{
    public const BODY = 'body';
    public const BODY_SIZE = 'body_size';
    public const CONSUMER_TAG = 'consumer_tag';
    public const DELIVERY_TAG = 'delivery_tag';
    public const REDELIVERED = 'redelivered';
    public const EXCHANGE = 'exchange';
    public const ROUTING_KEY = 'routing_key';
    public const TRUNCATED = 'truncated';
    public const MESSAGE_COUNT = 'message_count';
    public const CHANNEL = 'channel';

    protected array $defaultOptions = [];

    protected Collection $messageOptions;

    protected MessagePropertiesBuilder $propertiesBuilder;

    public function __construct(array $defaultOptions=[], array $defaultProperties=[])
    {
        $this->defaultOptions = $defaultOptions;
        $this->propertiesBuilder = new MessagePropertiesBuilder($defaultProperties, $this);
        $this->reset();
    }

    public function reset()
    {
        $this->messageOptions = collect($this->defaultOptions);
        $this->propertiesBuilder->reset();
        return $this;
    }

    protected function getMessage(string $body='', array $properties=[]): AMQPMessage
    {
        return new AMQPMessage($body, $properties);
    }

    public function build(): AMQPMessage
    {
        $result = $this->getMessage(
            $this->messageOptions->pull(self::BODY, ''),
            $this->properties()->build()
        );
        $deliveryInfo = [];
        $options = $this->messageOptions->all();
        foreach ($options as $option => $value) {
            switch ($option) {
                case self::BODY_SIZE:
                    $result->setBodySize($value);
                    break;
                case !is_null($value) && self::CONSUMER_TAG:
                    $result->setConsumerTag($value);
                    break;
                case !is_null($value) && self::DELIVERY_TAG:
                case !is_null($value) && self::REDELIVERED:
                case !is_null($value) && self::EXCHANGE:
                case !is_null($value) && self::ROUTING_KEY:
                    $deliveryInfo[$option] = $value;
                    break;
                case self::TRUNCATED:
                    $result->setIsTruncated($value);
                    break;
                case !is_null($value) && self::MESSAGE_COUNT:
                    $result->setMessageCount($value);
                    break;
                case !is_null($value) && self::CHANNEL:
                    $result->setChannel($value);
                    break;
                default:
                    throw new RuntimeException('Unsupported field type');
            }
        }
        if (4 === count($deliveryInfo)) {
            $result->setDeliveryInfo(
                $deliveryInfo[self::DELIVERY_TAG],
                $deliveryInfo[self::REDELIVERED],
                $deliveryInfo[self::EXCHANGE],
                $deliveryInfo[self::ROUTING_KEY]
            );
        } elseif (isset($deliveryInfo[self::DELIVERY_TAG])) {
            $result->setDeliveryTag($deliveryInfo[self::DELIVERY_TAG]);
        }
        return $result;
    }

    public function withBody(string $body='')
    {
        $this->messageOptions[self::BODY] = $body;
        return $this;
    }

    public function withBodySize(int $bodySize=0)
    {
        $this->messageOptions[self::BODY_SIZE] = $bodySize;
        return $this;
    }

    public function withConsumerTag(?string $consumerTag=null)
    {
        $this->messageOptions[self::CONSUMER_TAG] = $consumerTag;
        return $this;
    }

    public function withDeliveryTag($deliveryTag=null)
    {
        $this->messageOptions[self::DELIVERY_TAG] = $deliveryTag;
        return $this;
    }

    public function withTruncated(bool $truncated=false)
    {
        $this->messageOptions[self::TRUNCATED] = $truncated;
        return $this;
    }

    public function withRedelivered(?bool $redelivered=null)
    {
        $this->messageOptions[self::REDELIVERED] = $redelivered;
        return $this;
    }

    public function withExchange(?string $exchange=null)
    {
        $this->messageOptions[self::EXCHANGE] = $exchange;
        return $this;
    }

    public function withRoutingKey(?string $routingKey=null)
    {
        $this->messageOptions[self::ROUTING_KEY] = $routingKey;
        return $this;
    }

    public function withMessageCount(?int $messageCount=null)
    {
        $this->messageOptions[self::MESSAGE_COUNT] = $messageCount;
        return $this;
    }

    public function withChannel(?AMQPChannel $channel=null)
    {
        $this->messageOptions[self::CHANNEL] = $channel;
        return $this;
    }

    public function properties(): MessagePropertiesBuilder
    {
        return $this->propertiesBuilder;
    }
}