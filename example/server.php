<?php
return [
    'mq' => [
        'name'     => 'bee-mq',
        'pidFile'  => __DIR__ . '/bee.pid',
        'logFile'  => __DIR__ . '/bee_mq.log',
        'daemon'   => true, // 以守护进程模式运行
        'redirect' => false, // 不启用标准输出
    ]
];
