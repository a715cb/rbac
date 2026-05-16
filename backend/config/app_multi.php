<?php
// +----------------------------------------------------------------------
// | 多应用模式配置
// +----------------------------------------------------------------------

return [
    // 是否开启多应用模式
    'enabled' => true,
    // 默认应用
    'default' => 'admin',
    // 允许访问的应用列表
    'allow_list' => ['admin', 'miniapp'],
    // 应用映射（用于域名部署）
    'app_map' => [],
    // 域名部署
    'domain_deploy' => false,
    // 域名路由
    'domain_route' => false,
    // 禁止访问的应用
    'deny_list' => [],
    // 合并额外的路由配置
    'route_merge_rule' => [],
    // 默认路由行为
    'default_route_behavior' => '',
    // 异常处理
    'exception_handle' => '',
    // 错误显示
    'error_display' => false,
    // 显示详细信息
    'show_error_msg' => false,
    // URL 参数绑定顺序
    'url_param_type' => 0,
    // 应用调试模式
    'app_debug' => false,
];