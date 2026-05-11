<?php
// +----------------------------------------------------------------------
// | 路由配置文件
// +----------------------------------------------------------------------
use think\facade\Route;

// 加载 admin 路由
require __DIR__ . '/admin.php';

// 加载 api 路由
require __DIR__ . '/api.php';

// 加载 miniapp 路由
require __DIR__ . '/miniapp.php';
