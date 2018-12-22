<?php
namespace Bee\Mq\Consumer;

use Bee\Process\Worker;
use Bee\Mq\ConsumerInterface;
use Bee\Mq\Exception;
use Bee\Serialize\Factory;
use Swoole\Process;

/**
 * Rabbit
 *
 * @package Ant\Mq\Consumer
 */
abstract class Rabbit extends Worker implements ConsumerInterface
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    protected $exchange;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $queue;

    /**
     * @var string
     */
    protected $route;

    /**
     * @var int
     */
    protected $flags;

    /**
     * @var \AMQPConnection
     */
    protected $connect;

    /**
     * 每个消费者最大处理请求数
     *  - 处理数达到该值时自动退出当前进程
     *
     * @var int
     */
    protected $maximum = 1000;

    /**
     * 已处理计数
     *
     * @var int
     */
    protected $count = 0;

    /**
     * 队列数据序列化格式
     *
     * @var string
     */
    protected $format;

    /**
     * @var \Bee\Serialize\AdapterInterface
     */
    protected $serializer;

    /**
     * 进程启动
     *
     * @param Process $process
     * @param int $ppid
     */
    public function handle(Process $process, $ppid)
    {
        $this->process = $process;
        $this->ppid    = $ppid;

        // 错误处理回调
        set_error_handler([$this, 'error']);

        // 序列化对象
        $this->serializer = Factory::instance($this->format);
        // 启动消费者
        $this->receive();
    }

    /**
     * 设置MQ连接配置
     *
     * @param array $server
     * @param array $queue
     *
     * @throws Exception
     */
    public function setConfig(array $server, array $queue)
    {
        // 检查连接参数
        if (empty($server['host'])) {
            throw new Exception('host不能为空');
        }
        if (empty($server['port'])) {
            throw new Exception('port不能为空');
        }
        if (empty($server['login'])) {
            throw new Exception('username不能为空');
        }
        if (empty($server['password'])) {
            throw new Exception('password不能为空');
        }
        if (!isset($server['vhost'])) {
            $server['vhost'] = '/';
        }
        if (isset($server['format'])) {
            $this->format = $server['format'];
        }

        // 检查交换机，路由和队列参数
        if (!isset($queue['exchange'])) {
            throw new Exception('exchange不能为空');
        }
        if (!isset($queue['type'])) {
            throw new Exception('type不能为空');
        }
        if (!isset($queue['route'])) {
            throw new Exception('route不能为空');
        }
        if (!isset($queue['queue'])) {
            throw new Exception('queue不能为空');
        }
        if (!isset($queue['flags'])) {
            throw new Exception('flags不能为空');
        }

        // 连接配置信息
        $this->config   = $server;

        // 交换机，路由与队列信息
        $this->exchange = $queue['exchange'];
        $this->type     = $queue['type'];
        $this->route    = $queue['route'];
        $this->queue    = $queue['queue'];
        $this->flags    = $queue['flags'];
    }

    /**
     * 链接MQ
     *
     * @return \AMQPConnection
     * @throws \AMQPConnectionException
     */
    public function connect()
    {
        // 执行MQ链接
        $this->connect = new \AMQPConnection($this->config);
        $this->connect->connect();

        return $this->connect;
    }

    /**
     * 断开MQ连接
     */
    public function disconnect()
    {
        $this->connect->disconnect();
    }

    /**
     * 接收MQ消息
     *
     */
    public function receive()
    {
        try {
            // 获取RabbitMQ连接
            $connection = $this->connect();

            // 初始化频道
            $ch = new \AMQPChannel($connection);
            // 轮询分发
            $ch->qos(0, 1);

            // 设置换机
            $ex = new \AMQPExchange($ch);
            $ex->setName($this->exchange);
            $ex->setType($this->type);
            $ex->setFlags($this->flags);
            $ex->declareExchange();

            // 设置队列
            $qu = new \AMQPQueue($ch);
            $qu->setName($this->queue);
            $qu->setFlags($this->flags);
            $qu->declareQueue();
            $qu->bind($this->exchange, $this->route);
            // 开始接收消息
            $qu->consume(
                function (\AMQPEnvelope $envelope, \AMQPQueue $queue) {
                    // 已处理任务达到最大数
                    // 退出当前进程（主进程会自动拉起新进程）
                    if ($this->count >= $this->maximum) {
                        $this->workerExit();
                    }
                    // 已处理数加1
                    $this->count++;

                    // 标记进程为执行中
                    $this->idle = false;
                    // 消费业务处理
                    $this->consume($envelope, $queue);
                    // 标记进程未空闲
                    $this->idle = true;
                    // 检查主进程
                    $this->checkMaster();
                });
        } catch (\Throwable $e) {
            $this->exception($e);
        }
    }

    /**
     * 进程退出时回收资源
     */
    public function workerExit()
    {
        // 断开MQ连接
        $this->disconnect();
        // 退出当前进程
        $this->exit();
    }

    /**
     * 任务执行方法体
     *
     * @param \AMQPEnvelope $envelope
     * @param \AMQPQueue $queue
     *
     * @return mixed
     */
    abstract public function consume(\AMQPEnvelope $envelope, \AMQPQueue $queue);

    /**
     * 消费者异常处理函数
     *
     * @param \Throwable $e
     * @return mixed
     */
    abstract public function exception(\Throwable $e);

    /**
     * 错误处理
     *
     * @return mixed
     */
    abstract public function error();
}
