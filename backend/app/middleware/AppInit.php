<?php
// +----------------------------------------------------------------------
// | 应用初始化中间件
// +----------------------------------------------------------------------
namespace app\middleware;

use think\Request;

/**
 * 应用初始化中间件
 */
class AppInit
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        // 应用初始化逻辑
        // 可以在这里执行一些全局的初始化操作
        
        // 继续处理请求
        return $next($request);
    }
}
