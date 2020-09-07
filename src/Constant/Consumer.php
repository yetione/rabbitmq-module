<?php


namespace Yetione\RabbitMQ\Constant;


final class Consumer
{
    const RESULT_REJECT = 0;
    const RESULT_REJECT_REQUEUE = 1;
    const RESULT_SUCCESS = 2;

    const TIMEOUT_TYPE_IDLE = 1;
    const TIMEOUT_TYPE_GRACEFUL_MAX_EXECUTION = 2;

}