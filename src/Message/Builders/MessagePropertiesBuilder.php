<?php


namespace Yetione\RabbitMQ\Message\Builders;


use Illuminate\Support\Collection;
use PhpAmqpLib\Wire\AMQPTable;

class MessagePropertiesBuilder
{
    protected array $default = [];

    protected array $properties = [];

    protected ?MessageBuilder $messageBuilder;

    protected Collection $headers;

    public function __construct(array $default=[], ?MessageBuilder $messageBuilder=null)
    {
        $this->default = $default;
        $this->messageBuilder = $messageBuilder;
        $this->reset();
    }

    public function reset(): MessagePropertiesBuilder
    {
        $this->properties = $this->default;
        $this->headers = collect([]);
        return $this;
    }

    public function build(): array
    {
        $result = array_filter($this->properties);
        if (isset($result['application_headers'])) {
            $this->headers = $this->headers->merge(iterator_to_array($result['application_headers']));
        }
        if ($this->headers->isNotEmpty()) {
            $result['application_headers'] = new AMQPTable($this->headers->all());
        }
        return $result;
    }

    public function withContentType(?string $contentType=null): MessagePropertiesBuilder
    {
        $this->properties['content_type'] = $contentType;
        return $this;
    }

    public function withContentEncoding(?string $contentEncoding=null): MessagePropertiesBuilder
    {
        $this->properties['content_encoding'] = $contentEncoding;
        return $this;
    }

    public function withHeadersTable(?AMQPTable $headers=null): MessagePropertiesBuilder
    {
        $this->properties['application_headers'] = $headers;
        return $this;
    }

    public function withHeaders(array $headers=[]): MessagePropertiesBuilder
    {
        $this->headers = collect($headers);
        return $this;
    }

    public function withDeliveryMode(int $mode): MessagePropertiesBuilder
    {
        $this->properties['delivery_mode'] = $mode;
        return $this;
    }

    public function withPriority(int $priority): MessagePropertiesBuilder
    {
        $this->properties['priority'] = $priority;
        return $this;
    }

    public function withCorrelationId(string $correlationId): MessagePropertiesBuilder
    {
        $this->properties['correlation_id'] = $correlationId;
        return $this;
    }

    public function withReplyTo(string $replyTo): MessagePropertiesBuilder
    {
        $this->properties['reply_to'] = $replyTo;
        return $this;
    }

    public function withExpiration(string $exp): MessagePropertiesBuilder
    {
        $this->properties['expiration'] = $exp;
        return $this;
    }

    public function withMessageId(string $messageId): MessagePropertiesBuilder
    {
        $this->properties['message_id'] = $messageId;
        return $this;
    }

    public function withTimestamp(int $timestamp): MessagePropertiesBuilder
    {
        $this->properties['timestamp'] = $timestamp;
        return $this;
    }

    public function withType(string $type): MessagePropertiesBuilder
    {
        $this->properties['type'] = $type;
        return $this;
    }

    public function withUserId(string $userId): MessagePropertiesBuilder
    {
        $this->properties['user_id'] = $userId;
        return $this;
    }

    public function withAppId(string $appId): MessagePropertiesBuilder
    {
        $this->properties['app_id'] = $appId;
        return $this;
    }

    public function withClusterId(string $clusterId): MessagePropertiesBuilder
    {
        $this->properties['cluster_id'] = $clusterId;
        return $this;
    }

    public function message(): ?MessageBuilder
    {
        return $this->messageBuilder;
    }

    public function headers(): Collection
    {
        return $this->headers;
    }

    public function merge(array $properties): MessagePropertiesBuilder
    {
        $this->properties = array_merge($this->properties, $properties);
        return $this;
    }
}