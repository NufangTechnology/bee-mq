<?php
require __DIR__ . '/../vendor/autoload.php';

$server = require 'server.php';
$db = require 'db.php';
$worker = require 'worker.mq.php';

$di = new \Phalcon\Di;

try {
    $producer = new \Bee\Mq\Producer\Rabbit($db['mq']['default']);
    $producer->publish(
        'dev',
        'mq/developTest',
        [
            'user_id' => 0,
        ]
    );

} catch (\Throwable $e) {
    print_r($e);
}