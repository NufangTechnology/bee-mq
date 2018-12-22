<?php
return [
    [
        'server' => 'default',
        'class'  => \RabbitExample::class,
        'num'    => 1,
        'params' => [
            'exchange' => 'dev',
            'type'     => 'direct',
            'route'    => 'mq/developTest',
            'flags'    => AMQP_DURABLE,
            'queue'    => 'mq/developTest',
        ],
    ]
];
