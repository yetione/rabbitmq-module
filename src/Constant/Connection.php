<?php


namespace Yetione\RabbitMQ\Constant;


final class Connection
{
    const TYPE_STREAM_LAZY = 'stream_lazy';
    const TYPE_STREAM_NORMAL = 'stream_normal';
    const TYPE_STREAM_SSL = 'stream_ssl';
    const TYPE_SOCKET_LAZY = 'socket_lazy';
    const TYPE_SOCKET_NORMAL = 'socket_normal';


    const LOGIN_METHOD_PLAIN = 'PLAIN';
    const LOGIN_METHOD_RABBIT_CR_DEMO = 'RABBIT-CR-DEMO';
    const LOGIN_METHOD_AMQPPLAIN = 'AMQPLAIN';
    const LOGIN_METHOD_EXTERNAL = 'RABBIT-CR-DEMO';
}