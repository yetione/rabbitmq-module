<?php


namespace Yetione\RabbitMQ\Consumer;


use DateTime;
use InvalidArgumentException;
use PhpAmqpLib\Exception\AMQPEmptyDeliveryTagException;
use ErrorException;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPOutOfBoundsException;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use Yetione\DTO\DTO;
use Yetione\RabbitMQ\Connection\ConnectionInterface;
use Yetione\RabbitMQ\Connection\InteractsWithConnection;
use Yetione\RabbitMQ\Constant\Consumer;
use Yetione\RabbitMQ\DTO\Binding;
use Yetione\RabbitMQ\DTO\Exchange;
use Yetione\RabbitMQ\DTO\ExchangeBinding;
use Yetione\RabbitMQ\DTO\QosOptions;
use Yetione\RabbitMQ\DTO\Queue;
use Yetione\RabbitMQ\DTO\QueueBinding;
use Yetione\RabbitMQ\Event\EventDispatcherInterface;
use Yetione\RabbitMQ\Event\OnAfterProcessingMessageEvent;
use Yetione\RabbitMQ\Event\OnBeforeProcessingMessageEvent;
use Yetione\RabbitMQ\Event\OnConsumeEvent;
use Yetione\RabbitMQ\Event\OnConsumerFinish;
use Yetione\RabbitMQ\Event\OnConsumerStart;
use Yetione\RabbitMQ\Event\OnIdleEvent;
use Yetione\RabbitMQ\Exception\StopConsumerException;
use Throwable;
use Yetione\RabbitMQ\Logger\Loggable;
use Yetione\RabbitMQ\Logger\WithLogger;
use Yetione\RabbitMQ\Support\WithEventDispatcher;
use Yetione\RabbitMQ\DTO\Consumer as ConsumerDTO;

/**
 * TODO: Проверка памяти
 * TODO: Проверка времени выполнения
 * TODO: Разобраться с таймаутами
 * TODO: EventManager
 * Class AbstractConsumer
 * @package RabbitMQ\Consumer
 */
abstract class AbstractConsumer implements ConsumerInterface, Loggable
{
    use InteractsWithConnection, WithEventDispatcher, WithLogger;

    protected string $memoryLimit = '6144M';

    protected int $maxExecutionTime = 0;

    protected int $maxMessages = 0;

    protected int $consumedMessages = 0;

    protected int $unexpectedErrorExitCode = 3;

    protected int $idleTimeout = 0;

    protected int $idleTimeoutExitCode = 0;

    protected ?DateTime $gracefulMaxExecutionDateTime = null;

    protected int $gracefulTimeoutExitCode = 0;

    protected string $consumerTag;

    protected bool $forceStop = false;

    protected Queue $queue;

    protected ?Exchange $exchange;

    protected ?Binding $binding;

    protected ?QosOptions $qosOptions;

    protected array $metrics = [];

    protected ConsumerDTO $options;

    /**
     * AbstractConsumer constructor.
     * @param ConsumerDTO $options
     * @param ConnectionInterface $connection
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(ConsumerDTO $options, ConnectionInterface $connection, EventDispatcherInterface $eventDispatcher)
    {
        $this->options = $options;
        $this->setEventDispatcher($eventDispatcher);
        $this->setConnectionWrapper($connection);
    }

    /**
     * Метод обрабатывает сообщение из очереди и отвечает за его подтверждение.
     * @param AMQPMessage $message
     * @throws Throwable
     */
    public function processMessageFromQueue(AMQPMessage $message)
    {
        $oEvent = (new OnBeforeProcessingMessageEvent())->setConsumer($this)->setMessage($message);
        $this->eventDispatcher->dispatch($oEvent);

        try {
            $iResult = $this->processMessage($message);
            $this->handleProcessResult($message, $iResult);

            $oEvent = (new OnAfterProcessingMessageEvent())->setConsumer($this)->setMessage($message);
            $this->eventDispatcher->dispatch($oEvent);
        } catch (StopConsumerException $e) {
            $this->getLogger()->error($e->getMessage());
            $this->handleProcessResult($message, $e->getResultCode());
            $oEvent = (new OnAfterProcessingMessageEvent())
                ->setConsumer($this)
                ->setMessage($message)
                ->setError($e);
            $this->eventDispatcher->dispatch($oEvent);
            $this->stop();
        } catch (Exception | Throwable $e) {
            $this->getLogger()->error($e->getMessage());
            $oEvent = (new OnAfterProcessingMessageEvent())
                ->setConsumer($this)
                ->setMessage($message)
                ->setError($e);
            $this->eventDispatcher->dispatch($oEvent);
            throw $e;
        }
    }

    /**
     * Метод обработки сообщения.
     * @param AMQPMessage $message
     * @return int
     * @throws StopConsumerException
     */
    abstract protected function processMessage(AMQPMessage $message): int;

    /**
     * Метод проверяет очередь на существование сообщений.
     * @return bool
     */
    abstract protected function isMessageExists(): bool;

    /**
     * Метод выполняет проверку возможности начала следующей итерации цикла консьюмера.
     * @return bool
     */
    protected function nextIteration(): bool
    {
        gc_collect_cycles();
        $this->maybeReconnect();
        return $this->isMessageExists() && $this->isConnected();
    }

    /**
     * Метод запускает консьюмер
     * @return int
     * @throws Exception
     */
    public function start(): int
    {
        try {
            $this->getConsumerTag();
        } catch (Exception | Throwable $e) {
            $this->getLogger()->error($e->getMessage());
            return -1;
        }
        $this->metrics['start_time'] = microtime(true);
        $this->metrics['start_date'] = new DateTime();
        $this->metrics['connection_class'] = get_class($this->getConnectionWrapper()->getConnection());

        $this->getLogger()->debug('Start consumer', ['options'=>DTO::toArray($this->options)]);
        gc_enable();
        try {
            if (!$this->setup()) {
                $this->getLogger()->error('Consumer setup failed', ['options'=>DTO::toArray($this->options)]);
                return -1;
            }
            $oEvent = (new OnConsumerStart())->setConsumer($this);
            $this->eventDispatcher->dispatch($oEvent);
//            $oStartResult = $this->eventDispatcher->dispatch($oEvent);
//            if ($oStartResult->stopped()) {
//                $this->stop();
//                // TODO: Log
//                return -1;
//            }
            $iResult = $this->consume();
            $oEvent = (new OnConsumerFinish())->setConsumer($this);
            $this->eventDispatcher->dispatch($oEvent);
            $this->stop();
            $this->metrics['end_time'] = microtime(true);
            $this->metrics['execution_time'] = $this->metrics['end_time'] - $this->metrics['start_time'];
            $this->metrics['end_date'] = new DateTime();
            $this->getLogger()->debug('Consumer finished', ['options'=>DTO::toArray($this->options)]);
        } catch (Exception | Throwable $e) {
            $this->metrics['end_time'] = microtime(true); $this->metrics['end_date'] = new DateTime();
            $this->getLogger()->error($e->getMessage());
            $iResult = (int) $e->getCode();
        }
        return $iResult;
    }

    /**
     * Метод выполняет основную работу по получению и обработке сообщений.
     * @return int
     * @throws AMQPOutOfBoundsException
     * @throws AMQPRuntimeException
     * @throws AMQPTimeoutException
     * @throws ErrorException
     */
    protected function consume()
    {
        $this->getLogger()->debug('Begin consuming messages', ['options'=>DTO::toArray($this->options)]);
        while ($this->nextIteration()) {
            $oEvent = (new OnConsumeEvent())->setConsumer($this);
            $this->eventDispatcher->dispatch($oEvent);
            $this->maybeStopConsumer();
            try {
                $aWaitTimeout = $this->chooseWaitTimeout();
            } catch (Exception $e) {
                $this->getLogger()->error($e->getMessage());
                return $this->getUnexpectedErrorExitCode();
            }

            /*
             * Be careful not to trigger ::wait() with 0 or less seconds, when
             * graceful max execution timeout is being used.
             */
            if ($aWaitTimeout['timeoutType'] === Consumer::TIMEOUT_TYPE_GRACEFUL_MAX_EXECUTION && $aWaitTimeout['seconds'] < 1) {
                // Выходим в случае, если таймаут 0
                $this->getLogger()->debug('End consumer graceful', ['options'=>DTO::toArray($this->options)]);
                return $this->getGracefulTimeoutExitCode();
            }

            if (!$this->isForceStop()) {
                try {
                    $this->wait($aWaitTimeout);
                } catch (AMQPTimeoutException | ErrorException | AMQPOutOfBoundsException | AMQPRuntimeException $e) {
                    if (Consumer::TIMEOUT_TYPE_GRACEFUL_MAX_EXECUTION === $aWaitTimeout['timeoutType']) {
                        $this->getLogger()->error($e->getMessage());
                        return $this->getGracefulTimeoutExitCode();
                    }
                    /** @var OnIdleEvent $oEvent */
                    try {
                        $oEvent = (new OnIdleEvent())
                            ->setConsumer($this)
                            ->setParams(['timeout' => $aWaitTimeout['seconds'], 'forceStop' => $this->isForceStop()]);
                    } catch (InvalidArgumentException $e) {
                        $this->getLogger()->error($e->getMessage());
                        return $this->getIdleTimeoutExitCode();
                    }
                    $this->eventDispatcher->dispatch($oEvent);
                    if ($oEvent->isForceStop()) {
                        if (null !== $this->getIdleTimeoutExitCode()) {
                            $this->getLogger()->debug('End consumer IDLE', ['options'=>DTO::toArray($this->options)]);
                            return $this->getIdleTimeoutExitCode();
                        } else {
                            throw $e;
                        }
                    }
                    $this->maybeReconnect();
                }
            }
        }
        return 0;
    }

    /**
     * @param array $waitTimeout
     * @throws AMQPTimeoutException
     * @throws ErrorException
     * @throws AMQPOutOfBoundsException
     * @throws AMQPRuntimeException
     */
    abstract protected function wait(array $waitTimeout);

    /**
     * Метод проверяет надобность остановки консьюмера.
     */
    protected function maybeStopConsumer()
    {
        if ($this->isForceStop() || ($this->consumedMessages === $this->maxMessages && $this->maxMessages > 0)) {
            $this->stop();
        }
    }

    /**
     * Метод выполняет подготовку консьюмера.
     */
    public function setup(): bool
    {
        try {
            $this->setupPhp();
            $this->setupExchange();
            $this->setupQueue();
            $this->setupBinding();
            $this->setupOptions();
            $this->setupConsume();
            $this->setupEvents();
            return true;
        } catch (Exception | Throwable $e) {
            $this->getLogger()->error($e->getMessage());
        }
        return false;
    }

    /**
     * Метод подготавливает выполняет настройку PHP
     */
    protected function setupPhp()
    {
        set_time_limit(0);
        ini_set('memory_limit', $this->getMemoryLimit());
    }

    /**
     * Метод выполняет настройку и определение точки обмена.
     */
    protected function setupExchange()
    {
        if (isset($this->exchange) && null !== ($oExchange=$this->getExchange())) {
            $this->getConnectionWrapper()->declareExchange($oExchange);
        }
    }

    /**
     * Метод выполняет настройку и определение очереди.
     */
    protected function setupQueue()
    {
        $this->getConnectionWrapper()->declareQueue($this->getQueue());
    }

    /**
     * Метод выполняет настройку и определение связей между очередями и точками обмена.
     */
    protected function setupBinding()
    {
        if (isset($this->binding) && null !== ($oBinding=$this->getBinding())) {
            if ($oBinding instanceof QueueBinding) {
                $this->getConnectionWrapper()->declareQueueBinding($oBinding);
            } elseif ($oBinding instanceof ExchangeBinding) {
                $this->getConnectionWrapper()->declareExchangeBinding($oBinding);
            }
        }
    }

    /**
     * Метод выполняет настройку опций консьюмера.
     */
    protected function setupOptions()
    {
        $this->setupQosOptions();
    }

    /**
     * Метод выполняет настроку QoS консьмера.
     */
    protected function setupQosOptions()
    {
        if (isset($this->qosOptions) && null !== ($oQosOptions=$this->getQosOptions())) {
            $this->getConnectionWrapper()->declareQosOptions($oQosOptions);
        }
    }

    /**
     * Метод отвечает за настройку модели подписки (получения сообщений) из очереди.
     */
    protected function setupConsume(){}

    /**
     * Метод выполняет регистрацию событий для консьюмера.
     */
    protected function setupEvents(){}

    /**
     * Метод отвечает за обработку результата процессинга сообщения.
     * @param AMQPMessage $message
     * @param $processResult
     * @throws AMQPEmptyDeliveryTagException
     * @throws StopConsumerException
     */
    protected function handleProcessResult(AMQPMessage $message, $processResult)
    {
        $oChannel = $this->getMessageChannel($message);
        $sDeliveryTag = $message->getDeliveryTag();
        switch ($processResult) {
            case Consumer::RESULT_REJECT:
               $oChannel->basic_reject($sDeliveryTag, false);
               break;
            case Consumer::RESULT_REJECT_REQUEUE:
                $oChannel->basic_reject($sDeliveryTag, true);
                break;
            case Consumer::RESULT_SUCCESS:
                $oChannel->basic_ack($sDeliveryTag);
                break;
            default:
                $this->getLogger()->error('Invalid code from process message', ['code'=>$processResult, 'message'=>$message]);
                break;
        }
        $this->consumedMessages++;
        $this->maybeStopConsumer();
    }

    protected function getMessageChannel(AMQPMessage $message): AMQPChannel
    {
        return null !== $message->getChannel() ? $message->getChannel() : $this->channel();
    }

    /**
     * Метод выполняется в конце работы консьюмера.
     */
    public function stop()
    {
        $this->removeTemporaryData();
        $this->close();
    }

    /**
     * Метод удаляет все временные данные (связки, очереди, точки обмена).
     */
    protected function removeTemporaryData()
    {
        $con = $this->getConnectionWrapper();
        if (null !== ($oBinding=$this->getBinding()) && $oBinding->isTemporary()) {
            if ($oBinding instanceof ExchangeBinding) {
                $con->unbindExchange($oBinding);
            } elseif ($oBinding instanceof QueueBinding) {
                $con->unbindQueue($oBinding);
            }
        }
        if (null !== ($oExchange=$this->getExchange()) && $oExchange->isTemporary()) {
            $con->deleteExchange($oExchange, true);
        }
        if ($this->getQueue()->isTemporary()) {
            $con->deleteQueue($this->getQueue(), true);
        }
    }

    /**
     * Метод выбирает стратегию таймаута для метода $this->getConnectionWrapper()->getChannel()->wait().
     * @return array Of structure
     *  {
     *      timeoutType: string; // one of self::TIMEOUT_TYPE_*
     *      seconds: int;
     *  }
     * @throws Exception
     */
    protected function chooseWaitTimeout()
    {
        if ($this->gracefulMaxExecutionDateTime) {
            $oAllowedExecutionDateInterval = $this->gracefulMaxExecutionDateTime->diff(new DateTime());
            $iAllowedExecutionSeconds =  $oAllowedExecutionDateInterval->days * 86400
                + $oAllowedExecutionDateInterval->h * 3600
                + $oAllowedExecutionDateInterval->i * 60
                + $oAllowedExecutionDateInterval->s;

            if (!$oAllowedExecutionDateInterval->invert) {
                $iAllowedExecutionSeconds *= -1;
            }

            /*
             * Respect the idle timeout if it's set and if it's less than
             * the remaining allowed execution.
             */
            if (
                $this->getIdleTimeout()
                && $this->getIdleTimeout() < $iAllowedExecutionSeconds
            ) {
                return [
                    'timeoutType' => Consumer::TIMEOUT_TYPE_IDLE,
                    'seconds' => $this->getIdleTimeout(),
                ];
            }

            return [
                'timeoutType' => Consumer::TIMEOUT_TYPE_GRACEFUL_MAX_EXECUTION,
                'seconds' => $iAllowedExecutionSeconds,
            ];
        }

        return [
            'timeoutType' => Consumer::TIMEOUT_TYPE_IDLE,
            'seconds' => $this->getIdleTimeout(),
        ];
    }

    /**
     * @return Exchange|null
     */
    public function getExchange(): ?Exchange
    {
        if (null === $this->exchange) {
            $this->setExchange($this->createExchange());
        }
        return $this->exchange;
    }

    protected function createExchange(): ?Exchange
    {
        return null;
    }

    /**
     * @return Queue
     */
    public function getQueue(): Queue
    {
        if (null === $this->queue) {
            $this->setQueue($this->createQueue());
        }
        return $this->queue;
    }

    abstract protected function createQueue(): Queue;

    /**
     * @return Binding|null
     */
    public function getBinding(): ?Binding
    {
        if (null === $this->binding) {
            $this->setBinding($this->createBinding());
        }
        return $this->binding;
    }

    protected function createBinding(): ?Binding
    {
        return null;
    }

    /**
     * @return QosOptions|null
     */
    public function getQosOptions(): ?QosOptions
    {
        if (null === $this->qosOptions) {
            $this->setQosOptions($this->createQosOptions());
        }
        return $this->qosOptions;
    }

    protected function createQosOptions(): ?QosOptions
    {
        return $this->options->getQos();
    }

    /**
     * @return string
     */
    public function getConsumerTag(): string
    {
        return $this->consumerTag;
    }

    /**
     * @param string $consumerTag
     * @return AbstractConsumer
     */
    public function setConsumerTag(string $consumerTag): AbstractConsumer
    {
        $this->consumerTag = $consumerTag;
        return $this;
    }

    /**
     * @return int
     */
    public function getIdleTimeout(): int
    {
        return $this->idleTimeout;
    }

    /**
     * @param int $idleTimeout
     * @return AbstractConsumer
     */
    public function setIdleTimeout(int $idleTimeout): AbstractConsumer
    {
        $this->idleTimeout = $idleTimeout;
        return $this;
    }

    /**
     * @return int
     */
    public function getIdleTimeoutExitCode(): int
    {
        return $this->idleTimeoutExitCode;
    }

    /**
     * @param int $idleTimeoutExitCode
     * @return AbstractConsumer
     */
    public function setIdleTimeoutExitCode(int $idleTimeoutExitCode): AbstractConsumer
    {
        $this->idleTimeoutExitCode = $idleTimeoutExitCode;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getGracefulMaxExecutionDateTime(): ?DateTime
    {
        return $this->gracefulMaxExecutionDateTime;
    }

    /**
     * @param DateTime|null $gracefulMaxExecutionDateTime
     * @return $this
     */
    public function setGracefulMaxExecutionDateTime(?DateTime $gracefulMaxExecutionDateTime): self
    {
        $this->gracefulMaxExecutionDateTime = $gracefulMaxExecutionDateTime;
        return $this;
    }

    /**
     * @param int $secondsInTheFuture
     * @return $this
     * @throws Exception
     */
    public function setGracefulMaxExecutionDateTimeFromSecondsInTheFuture(int $secondsInTheFuture): self
    {
        return $this->setGracefulMaxExecutionDateTime(new DateTime("+{$secondsInTheFuture} seconds"));
    }

    /**
     * @return int
     */
    public function getGracefulTimeoutExitCode(): int
    {
        return $this->gracefulTimeoutExitCode;
    }

    /**
     * @param int $gracefulTimeoutExitCode
     * @return $this
     */
    public function setGracefulTimeoutExitCode(int $gracefulTimeoutExitCode): self
    {
        $this->gracefulTimeoutExitCode = $gracefulTimeoutExitCode;
        return $this;
    }

    /**
     * @return bool
     */
    public function isForceStop(): bool
    {
        return $this->forceStop;
    }

    /**
     * @param bool $forceStop
     * @return $this
     */
    public function setForceStop(bool $forceStop): self
    {
        $this->forceStop = $forceStop;
        return $this;
    }

    /**
     * @param Queue $queue
     * @return $this
     */
    public function setQueue(Queue $queue): self
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * @param Exchange|null $exchange
     * @return $this
     */
    public function setExchange(?Exchange $exchange): self
    {
        $this->exchange = $exchange;
        return $this;
    }

    /**
     * @param Binding|null $binding
     * @return $this
     */
    public function setBinding(?Binding $binding): self
    {
        $this->binding = $binding;
        return $this;
    }

    /**
     * @param QosOptions|null $qosOptions
     * @return $this
     */
    public function setQosOptions(?QosOptions $qosOptions): self
    {
        $this->qosOptions = $qosOptions;
        return $this;
    }

    /**
     * @return int
     */
    public function getUnexpectedErrorExitCode(): int
    {
        return $this->unexpectedErrorExitCode;
    }

    /**
     * @param int $unexpectedErrorExitCode
     * @return $this
     */
    public function setUnexpectedErrorExitCode(int $unexpectedErrorExitCode): self
    {
        $this->unexpectedErrorExitCode = $unexpectedErrorExitCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getMemoryLimit(): string
    {
        return $this->memoryLimit;
    }

    /**
     * @param string $memoryLimit
     * @return $this
     */
    public function setMemoryLimit(string $memoryLimit): self
    {
        $this->memoryLimit = $memoryLimit;
        return $this;
    }
}