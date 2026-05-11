<?php
// +----------------------------------------------------------------------
// | Admin 应用中间件配置
// +----------------------------------------------------------------------

return [
    // Admin 应用中间件
    'admin' => [
        // Admin 认证中间件
        \app\admin\middleware\AuthCheck::class,
        // API 权限验证中间件
        \app\admin\middleware\ApiPermission::class,
    ],
];