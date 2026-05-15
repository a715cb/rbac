<?php
// +----------------------------------------------------------------------
// | 数据库设置
// +----------------------------------------------------------------------

return [
    // 默认数据库标识
    'default' => env('DB_CONNECTION', 'mysql'),
    // 数据库连接配置
    'connections' => [
        'mysql' => [
            // 数据库类型
            'type' => 'mysql',
            // 服务器地址
            'hostname' => env('DB_HOST', '127.0.0.1'),
            // 数据库名
            'database' => env('DB_DATABASE', 'rbac_system'),
            // 用户名
            'username' => env('DB_USERNAME', 'root'),
            // 密码
            'password' => env('DB_PASSWORD', ''),
            // 端口
            'hostport' => env('DB_PORT', '3306'),
            // 数据库连接参数
            'params' => [],
            // 数据库编码默认 UTF8
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            // 数据库表前缀
            'prefix' => env('DB_PREFIX', 'sys_'),
            // 数据库部署方式：1 集中式 2 分布式
            'deploy' => 0,
            // 数据库读写是否分离 主从式有效
            'rw_separate' => false,
            // 读写分离后 主服务器数量
            'master_num' => 1,
            // 指定从服务器序号
            'slave_no' => '',
            // 是否严格检查字段是否存在
            'fields_strict' => true,
            // 是否需要断线重连
            'break_reconnect' => true,
            // 监听 SQL
            'trigger_sql' => env('APP_DEBUG', false),
            // 开启字段缓存
            'fields_cache' => false,
        ],
    ],
];
