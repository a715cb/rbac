<?php
// +----------------------------------------------------------------------
// | API 路由配置
// +----------------------------------------------------------------------
use think\facade\Route;

// 默认路由
Route::get('/', function () {
    return json([
        'code' => 200,
        'msg' => 'Welcome to RBAC API',
        'data' => [
            'version' => '1.0.0',
            'name' => 'RBAC Permission System'
        ]
    ]);
});
