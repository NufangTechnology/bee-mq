<?php
namespace Bee\Mq;

/**
 * Interface ProducerInterface
 *
 * @package Bee\Mq
 */
interface ProducerInterface
{
    /**
     * 发布消息
     *
     * @param string $exchange 交换器
     * @param string $route 路由
     * @param mixed $data 数据
     *
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     */
    public function publish($exchange, $route, $data);

    /**
     * 获取MQ链接对象
     */
    public function connect();

    /**
     * 关闭MQ连接
     */
    public function close();
}
