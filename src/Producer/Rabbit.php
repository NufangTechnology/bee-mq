<?php
namespace Bee\Mq\Producer;

use Bee\Mq\ProducerInterface;
use Bee\Serialize\Factory;

/**
 * MQ生产者服务
 *
 * @package Ant\Mq
 */
class Rabbit implements ProducerInterface
{
    /**
     * @var \AMQPConnection
     */
    private $connection;

    /**
     * @var \Bee\Serialize\AdapterInterface
     */
    private $serializer;

    /**
     * Rabbit constructor.
     *
     * @param $config
     *
     * @throws \AMQPConnectionException
     */
    public function __construct($config)
    {
        // 序列化数据格式
        $format     = $config['format'] ?? '';
        $serializer = Factory::instance($format);

        // 连接MQ
        $connection = new \AMQPConnection($config);
        $connection->connect();

        $this->connection = $connection;
        $this->serializer = $serializer;
    }

    /**
     * 获取MQ链接对象
     *
     * @return \AMQPConnection
     */
    public function connect()
    {
        if (!$this->connection->isConnected()) {
            $this->connection->reconnect();
        }

        return $this->connection;
    }

    /**
     * 发布消息
     *
     * @param string $exchange 交换器
     * @param string $route 路由
     * @param mixed $data 数据
     *
     * @return Rabbit
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     */
    public function publish($exchange, $route, $data)
    {
        if (is_array($data)) {
            $_data = $this->serializer::pack($data);
        } else {
            $_data = $data;
        }

        // MQ连接
        $connection = $this->connect();
        // 频道
        $channel    = new \AMQPChannel($connection);

        // 交换机
        $ex = new \AMQPExchange($channel);
        $ex->setName($exchange);
        // 发布消息
        $ex->publish($_data, $route);

        return $this;
    }

    /**
     * 关闭MQ连接
     */
    public function close()
    {
        $this->connection->disconnect();
    }
}