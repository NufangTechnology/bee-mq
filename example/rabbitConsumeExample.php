<?php
require __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);


class RabbitExample extends \Bee\Mq\Consumer\Rabbit
{
    /**
     * 任务执行方法体
     *
     * @param \AMQPEnvelope $envelope
     * @param \AMQPQueue $queue
     *
     * @return mixed
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     */
    public function consume(\AMQPEnvelope $envelope, \AMQPQueue $queue)
    {
//        sleep(10);
//        exit();

        $hello = false;

        $hello->test;
        $hello->haha;

        go(function () {
            Co::sleep(4);
            file_put_contents(__DIR__ . '/go.log', time() . PHP_EOL, 8);
        });

        go(function () {
            file_put_contents(__DIR__ . '/go-1.log', time() . PHP_EOL, 8);
        });

        $queue->ack($envelope->getDeliveryTag());
    }

    /**
     * 消费者异常处理函数
     *
     * @param \Throwable $e
     * @return mixed
     */
    public function exception(\Throwable $e)
    {
    }

    /**
     * 错误处理
     *
     * @return mixed
     */
    public function error()
    {
        file_put_contents(__DIR__ . '/error.log', json_encode(func_get_args()) . PHP_EOL, 8);
    }
}

$server = require 'server.php';
$db = require 'db.php';
$worker = require 'worker.mq.php';

$di = new \Phalcon\Di;

try {
    $master = new \Bee\Mq\Master($server['mq']);
    $master->setConfig($db['mq'], $worker);
//    $master->restart();
    $master->status();
    $master->stop();

} catch (\Throwable $e) {
    print_r($e);
}
