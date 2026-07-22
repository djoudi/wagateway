<?php

return [
    'domain'  => null,
    'path'    => 'horizon',
    'use'     => 'default',
    'prefix'  => env('HORIZON_PREFIX', 'wg_'),
    'middleware'=> ['web', 'auth'],

    'waits'   => ['redis:default' => 60],
    'trim'    => ['recent' => 60, 'pending' => 60, 'completed' => 60, 'recent_failed' => 10080, 'failed' => 10080, 'monitored' => 10080],
    'fast_termination' => false,
    'memory_limit'     => 128,

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue'      => ['default','webhooks','bulk'],
            'balance'    => 'auto',
            'processes'  => 5,
            'tries'      => 3,
            'timeout'    => 60,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-default' => [
                'connection' => 'redis',
                'queue'      => ['default'],
                'balance'    => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 5,
                'tries'      => 3,
                'timeout'    => 60,
            ],
            'supervisor-webhooks' => [
                'connection' => 'redis',
                'queue'      => ['webhooks'],
                'balance'    => 'simple',
                'processes'  => 3,
                'tries'      => 3,
                'timeout'    => 20,
            ],
            'supervisor-bulk' => [
                'connection' => 'redis',
                'queue'      => ['bulk'],
                'balance'    => 'simple',
                'processes'  => 2,
                'tries'      => 1,
                'timeout'    => 3600,
            ],
        ],
    ],
];
