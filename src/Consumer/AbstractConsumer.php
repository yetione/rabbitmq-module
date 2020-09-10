<?php


namespace Yetione\RabbitMQ\Consumer;


use DateTime;
use InvalidArgumentException;
use PhpAmqpLib\Exception\AMQPEmptyDeliveryTagException;
use Yetione\DTO\Serializer;
use ErrorException;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPOutOfBoundsException;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use Yetione\Json\Json;
use Yetione\RabbitMQ\Connection\ConnectionInterface;
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
use Yetione\RabbitMQ\Exception\ConnectionException;
use Yetione\RabbitMQ\Exception\StopConsumerException;
use Yetione\RabbitMQ\Service\RabbitMQService;
use Throwable;

/**
 * TODO: Проверка памяти
 * TODO: Проверка времени выполнения
 * TODO: Разобраться с таймаутами
 * TODO: EventManager
 * Class AbstractConsumer
 * @package RabbitMQ\Consumer
 */
abstract class AbstractConsumer implements ConsumerInterface
{

    protected ConnectionInterface $connectionWrapper;

    protected string $memoryLimit = '6144M';

    protected int $maxExecutionTime = 0;

    protected string $connectionOptionsName = 'consumer';

    protected string $connectionName = 'default_consumer';

    protected int $maxMessages = 0;

    protected int $consumedMessages = 0;

    protected int $unexpectedErrorExitCode = 3;

    protected int $idleTimeout = 0;

    protected int $idleTimeoutExitCode = 0;

    protected ?DateTime $gracefulMaxExecutionDateTime;

    protected int $gracefulTimeoutExitCode = 0;

    protected string $consumerTag;

    protected bool $forceStop = false;

    protected Queue $queue;

    protected ?Exchange $exchange;

    protected ?Binding $binding;

    protected ?QosOptions $qosOptions;

    protected array $metrics = [];

    protected RabbitMQService $rabbitMQService;

    protected Serializer $serializer;

    protected EventDispatcherInterface $eventDispatcher;

    /**
     * AbstractConsumer constructor.
     * @param RabbitMQService $rabbitMQService
     * @param Serializer $serializer
     * @param EventDispatcherInterface $eventDispatcher
     * @throws ConnectionException
     */
    public function __construct(RabbitMQService $rabbitMQService, Serializer $serializer, EventDispatcherInterface $eventDispatcher)
    {
        $this->rabbitMQService = $rabbitMQService;
        $this->serializer = $serializer;
        $this->eventDispatcher = $eventDispatcher;
        $oConnection = $this->rabbitMQService->getConnection($this->connectionName, $this->connectionOptionsName);
        if (null === $oConnection) {
            throw new ConnectionException("Cannot create connection {$this->connectionName} with option {$this->connectionOptionsName}.");
        }
        $this->setConnectionWrapper($oConnection);
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
            // TODO: Log
            $this->handleProcessResult($message, $e->getResultCode());
            $oEvent = (new OnAfterProcessingMessageEvent())
                ->setConsumer($this)
                ->setMessage($message)
                ->setError($e);
            $this->eventDispatcher->dispatch($oEvent);
            $this->stop();
        } catch (Exception | Throwable $e) {
            // TODO: Log
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
        return $this->isMessageExists() && $this->checkConnection();
    }

    /**
     * Метод проверяет состояние соединения
     * @return bool
     */
    protected function checkConnection()
    {
        $oConnection = $this->getConnectionWrapper();
        return $oConnection->isConnectionOpen() && $oConnection->isChannelOpen();
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
            // TODO: Log
            return -1;
        }
        $this->metrics['start_time'] = microtime(true);
        $this->metrics['start_date'] = new DateTime();
        $this->metrics['connection_class'] = get_class($this->getConnectionWrapper()->getConnection());

        // TODO: Log
        gc_enable();
        try {
            if (!$this->setup()) {
                // TODO: Log
                return -1;
            }
            $oEvent = (new OnConsumerStart())->setConsumer($this);
            $oStartResult = $this->eventDispatcher->dispatch($oEvent);
            if ($oStartResult->stopped()) {
                $this->stop();
                // TODO: Log
                return -1;
            }
            $iResult = $this->consume();
            $oEvent = (new OnConsumerFinish())->setConsumer($this);
            $this->eventDispatcher->dispatch($oEvent);
            $this->stop();
            $this->metrics['end_time'] = microtime(true);
            $this->metrics['execution_time'] = $this->metrics['end_time'] - $this->metrics['start_time'];
            $this->metrics['end_date'] = new DateTime();
            // TODO: Log
        } catch (Exception | Throwable $e) {
            $this->metrics['end_time'] = microtime(true); $this->metrics['end_date'] = new DateTime();
            // TODO: Log
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
        // TODO: Log
        while ($this->nextIteration()) {
            $oEvent = (new OnConsumeEvent())->setConsumer($this);
            $this->eventDispatcher->dispatch($oEvent);
            $this->maybeStopConsumer();
            try {
                $aWaitTimeout = $this->chooseWaitTimeout();
            } catch (Exception $e) {
                // TODO: Log
                return $this->getUnexpectedErrorExitCode();
            }

            /*
             * Be careful not to trigger ::wait() with 0 or less seconds, when
             * graceful max execution timeout is being used.
             */
            if ($aWaitTimeout['timeoutType'] === Consumer::TIMEOUT_TYPE_GRACEFUL_MAX_EXECUTION && $aWaitTimeout['seconds'] < 1) {
                // TODO: Log
                return $this->getGracefulTimeoutExitCode();
            }

            if (!$this->isForceStop()) {
                try {
                    $this->wait($aWaitTimeout);
                } catch (AMQPTimeoutException | ErrorException | AMQPOutOfBoundsException | AMQPRuntimeException $e) {
                    if (Consumer::TIMEOUT_TYPE_GRACEFUL_MAX_EXECUTION === $aWaitTimeout['timeoutType']) {
                        // TODO: Log
                        return $this->getGracefulTimeoutExitCode();
                    }
                    /** @var OnIdleEvent $oEvent */
                    try {
                        $oEvent = (new OnIdleEvent())
                            ->setConsumer($this)
                            ->setParams(['timeout' => $aWaitTimeout['seconds'], 'forceStop' => $this->isForceStop()]);
                    } catch (InvalidArgumentException $e) {
                        // TODO: Log
                        return $this->getIdleTimeoutExitCode();
                    }
                    $this->eventDispatcher->dispatch($oEvent);
                    if ($oEvent->isForceStop()) {
                        if (null !== $this->getIdleTimeoutExitCode()) {
                            // TODO: Log
                            return $this->getIdleTimeoutExitCode();
                        } else {
                            throw $e;
                        }
                    }
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
            // TODO: Log
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
        if (null !== ($oExchange=$this->getExchange())) {
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
        if (null !== ($oBinding=$this->getBinding())) {
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
        if (null !== ($oQosOptions=$this->getQosOptions())) {
            try {
                $this->getConnectionWrapper()->getChannel()->basic_qos(
                    $oQosOptions->getPrefetchSize(),
                    $oQosOptions->getPrefetchCount(),
                    $oQosOptions->getGlobal()
                );
            } catch (AMQPTimeoutException $e) {
                // TODO: Log
            }
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
                // TODO: Log
                break;
        }
        $this->consumedMessages++;
        $this->maybeStopConsumer();
    }

    protected function getMessageChannel(AMQPMessage $message): AMQPChannel
    {
        return null !== $message->getChannel() ? $message->getChannel() : $this->getConnectionWrapper()->getChannel();
    }

    /**
     * Метод выполняется в конце работы консьюмера.
     */
    public function stop()
    {
        $this->removeTemporaryData();
        $this->closeConnection();
    }

    /**
     * Метод удаляет все временные данные (связки, очереди, точки обмена).
     */
    protected function removeTemporaryData()
    {
        if (null !== ($oBinding=$this->getBinding()) && $oBinding->isTemporary()) {
            if ($oBinding instanceof ExchangeBinding) {
                $this->getConnectionWrapper()->unbindExchange($oBinding);
            } elseif ($oBinding instanceof QueueBinding) {
                $this->getConnectionWrapper()->unbindQueue($oBinding);
            }
        }
        if (null !== ($oExchange=$this->getExchange()) && $oExchange->isTemporary()) {
            $this->getConnectionWrapper()->deleteExchange($oExchange, true);
        }
        if ($this->getQueue()->isTemporary()) {
            $this->getConnectionWrapper()->deleteQueue($this->getQueue(), true);
        }
    }

    /**
     * Метод закрывает соединение
     */
    protected function closeConnection()
    {
        if ($this->getConnectionWrapper()->isConnectionOpen()) {
            $this->getConnectionWrapper()->close();
        }
    }

    /**
     * Метод выбирает стратегию таймаута для метода $this->>getConnectionWrapper()->getChannel()->wait().
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
        return null;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnectionWrapper(): ConnectionInterface
    {
        return $this->connectionWrapper;
    }

    /**
     * @param ConnectionInterface $connectionWrapper
     * @return $this
     */
    public function setConnectionWrapper(ConnectionInterface $connectionWrapper): self
    {
        $this->connectionWrapper = $connectionWrapper;
        return $this;
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
     * @return string
     */
    public function getConnectionOptionsName(): string
    {
        return $this->connectionOptionsName;
    }

    /**
     * @param string $connectionOptionsName
     * @return $this
     */
    public function setConnectionOptionsName(string $connectionOptionsName): self
    {
        $this->connectionOptionsName = $connectionOptionsName;
        return $this;
    }

    /**
     * @return string
     */
    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    /**
     * @param string $connectionName
     * @return $this
     */
    public function setConnectionName(string $connectionName): self
    {
        $this->connectionName = $connectionName;
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

    /**
     * @param array $options
     * @return array
     */
    protected function getLoggerContext(array $options=[]): array
    {
        $aResult = [];
        $oQueue = $this->getQueue();
        $aConsumer = [
            'connection_name'=>$this->getConnectionName(),
            'connection_options_name'=>$this->getConnectionOptionsName(),
            'consumed_messages'=>$this->consumedMessages
        ];
        try {
            $aConsumer['tag'] = $this->getConsumerTag();
        } catch (Exception | Throwable $e) {
        }
        try {
            $aConsumer['queue'] = $this->serializer->toArray($oQueue);
            if (null !== ($oExchange=$this->getExchange())) {
                $aConsumer['exchange'] = $this->serializer->toArray($oExchange);
            }
            if (null !== ($oBinding=$this->getBinding())) {
                $aConsumer['binding'] = $this->serializer->toArray($oBinding);
            }
        } catch (Exception | Throwable $e) {
        }

        if (isset($this->metrics['start_date'])) {
            $aConsumer['start_date'] = $this->metrics['start_date'];
        }
        if (isset($this->metrics['end_date'])) {
            $aConsumer['end_date'] = $this->metrics['end_date'];
        }
        if (isset($this->metrics['execution_time'])) {
            $aConsumer['execution_time'] = $this->metrics['execution_time'];
        } elseif (isset($this->metrics['start_time'], $this->metrics['end_time'])) {
            $aConsumer['execution_time'] = $this->metrics['end_time'] - $this->metrics['start_time'];
        }
        if (isset($this->metrics['connection_class'])) {
            $aConsumer['connection_class'] = $this->metrics['connection_class'];
        }
        if (isset($options['result_code'])) {
            $aConsumer['result_code'] = $options['result_code'];
            unset($options['result_code']);
        }
        if (isset($options['message'])) {
            $message = $options['message'];
            if ($message instanceof AMQPMessage) {
                $aResult['message'] = [
                    'body'=>$message->getBody(),
                    'body_size'=>$message->getBodySize(),
                    'properties'=>$message->get_properties(),
                    'encoding'=>$message->getContentEncoding(),
                    'delivery_info'=>$message->delivery_info
                ];
            } else {
                $sBody = is_array($message) ? Json::encode($message) : (string) $message;
                $aResult['message'] = [
                    'body'=>$sBody,
                    'body_size'=>strlen($sBody)
                ];
            }
            unset($options['message']);
        }
        if (isset($options['error'])) {
            $error = $options['error'];
            if ($error instanceof Throwable) {
//                $aResult['error'] = ContextFormatter::formatException($error);
                // TODO: Format error
            }
            unset($options['error']);
        }
        if (isset($options['wait_timeout'])) {
            $aResult['wait_timeout'] = $options['wait_timeout'];
            unset($options['wait_timeout']);
        }
        $aResult['consumer'] = $aConsumer;
        return array_merge($options, $aResult);
    }
}