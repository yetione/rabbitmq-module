<?php


namespace Yetione\RabbitMQ\Constant;


final class Connection
{
    const TYPE_LAZY = 'lazy';
    const TYPE_NORMAL = 'normal';

    const LOGIN_METHOD_PLAIN = 'PLAIN';
    const LOGIN_METHOD_RABBIT_CR_DEMO = 'RABBIT-CR-DEMO';
    const LOGIN_METHOD_AMQPPLAIN = 'AMQPLAIN';
    const LOGIN_METHOD_EXTERNAL = 'RABBIT-CR-DEMO';
}