<?php
// +----------------------------------------------------------------------
// | Redis 设置
// +----------------------------------------------------------------------

return [
    // 默认连接
    'default' => 'default',
    // 连接配置
    'connections' => [
        'default' => [
            // 主机地址
            'host' => env('REDIS_HOST', '127.0.0.1'),
            // 端口
            'port' => env('REDIS_PORT', 6379),
            // 密码
            'password' => env('REDIS_PASSWORD', null),
            // 数据库
            'database' => env('REDIS_DB', 0),
            // 超时时间（秒）
            'timeout' => env('REDIS_TIMEOUT', 0),
            // 持久化连接
            'persistent' => false,
            // 连接名
            'name' => 'default',
        ],
    ],
];
