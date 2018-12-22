<?php
namespace Bee\Mq;

/**
 * Interface ConsumerInterface
 *
 * @package Ant\Mq
 */
interface ConsumerInterface
{
    /**
     * 连接MQ
     */
    public function connect();

    /**
     * 断开MQ连接
     *
     * @return mixed
     */
    public function disconnect();

    /**
     * 设置MQ消费者相关配置
     *
     * @param array $server
     * @param array $queue
     * @return mixed
     */
    public function setConfig(array $server, array $queue);

    /**
     * 接收MQ消息
     *
     * @return mixed
     */
    public function receive();
}