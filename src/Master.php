<?php
namespace Bee\Mq;

/**
 * Master
 *
 * @package Bee\Mq
 */
class Master extends \Bee\Process\Master
{
    /**
     * @var array
     */
    private $servers = [];

    /**
     * @var array
     */
    private $workers = [];

    /**
     * 设置MQ服务连接配置
     *
     * @param $serverConfig
     * @param $workerConfig
     */
    public function setConfig($serverConfig, $workerConfig)
    {
        $this->servers = $serverConfig;
        $this->workers = $workerConfig;
    }

    /**
     * 主进程业务配置
     *
     * @throws Exception
     */
    public function configure()
    {
        $instances = [];
        // 工作进程
        $workers   = $this->workers;
        // MQ服务器连接信息
        $servers   = $this->servers;

        // 检查MQ配置
        foreach ($workers as $key => $worker) {
            // 所使用的服务配置
            $name = $worker['server'];

            if (!isset($servers[$name])) {
                throw new Exception("配置'{$name}'在\$servers中未找到");
            }

            $instances[$key] = new $worker['class'];
            $instances[$key]->setConfig($servers[$name], $worker['params']);
        }

        // 启动MQ消费者实例
        foreach ($workers as $key => $worker) {
            for ($i = 0; $i < $worker['num']; $i++) {
                $this->fork([$instances[$key], 'handle'], $worker['params']['route']);
            }
        }
    }
}
