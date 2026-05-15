<?php
// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 默认缓存驱动
    'default' => env('CACHE_DRIVER', 'redis'),
    // 缓存连接配置
    'stores' => [
        'file' => [
            // 驱动类型
            'type' => 'File',
            // 缓存保存目录
            'path' => '',
            // 缓存前缀
            'prefix' => '',
            // 缓存有效期 0 为永久
            'expire' => 0,
            // 缓存标签
            'tag_prefix' => '',
            // 缓存数据长度
            'length' => 0,
        ],
        'redis' => [
            // 驱动类型
            'type' => 'redis',
            // Redis 服务器地址
            'host' => env('REDIS_HOST', '127.0.0.1'),
            // Redis 服务器端口
            'port' => env('REDIS_PORT', 6379),
            // Redis 连接密码
            'password' => env('REDIS_PASSWORD', ''),
            // 缓存连接数据库编号
            'select' => env('REDIS_DB', 0),
            // 连接超时时间（秒）
            'timeout' => env('REDIS_TIMEOUT', 0),
            // 是否持久化连接
            'persistent' => false,
            // 缓存前缀
            'prefix' => env('CACHE_PREFIX', ''),
            // 缓存有效期 0 为永久
            'expire' => 0,
            // 缓存标签
            'tag_prefix' => '',
            // 缓存数据长度
            'length' => 0,
        ],
    ],
];
