<?php
// +----------------------------------------------------------------------
// | 跨域中间件
// +----------------------------------------------------------------------
namespace app\middleware;

use think\Request;
use think\Response;

/**
 * 跨域资源共享（CORS）处理中间件
 * @description 处理跨域请求的预检请求（OPTIONS）和响应头设置，支持跨域 API 调用。
 *              当浏览器发起跨域请求时，会先发送 OPTIONS 预检请求询问服务器是否允许，
 *              本中间件负责响应预检请求并为所有响应添加 CORS 头信息。
 * @使用场景:
 *   - 前后端分离项目，前端页面与后端 API 部署在不同域名
 *   - 微服务架构中，不同服务间的 API 调用
 *   - 第三方应用接入开放 API
 * @注意事项:
 *   - 生产环境建议将 Access-Control-Allow-Origin 替换为具体域名
 *   - 如果需要携带 Cookie，应将 Access-Control-Allow-Credentials 设为 true
 *   - Access-Control-Allow-Headers 需要包含前端实际使用的所有自定义请求头
 */
class AllowCrossDomain
{
    /**
     * 处理跨域请求
     * @param Request $request 请求对象
     * @param \Closure $next 下一个中间件或控制器
     * @return Response 响应对象，包含 CORS 头信息
     * @description 处理流程：
     *   1. 判断是否为 OPTIONS 预检请求，如果是则直接返回 204 空响应
     *   2. 对于普通请求，继续执行后续中间件/控制器
     *   3. 为所有响应添加 CORS 相关 HTTP 头
     * @header说明:
     *   - Access-Control-Allow-Origin: 允许的来源，* 表示允许所有（生产环境建议指定域名）
     *   - Access-Control-Allow-Methods: 允许的 HTTP 方法
     *   - Access-Control-Allow-Headers: 允许的请求头
     *   - Access-Control-Expose-Headers: 允许前端访问的响应头
     *   - Access-Control-Allow-Credentials: 是否允许携带凭证（Cookie）
     *   - Access-Control-Max-Age: 预检请求结果的缓存时间（秒），减少OPTIONS请求
     */
    public function handle($request, \Closure $next)
    {
        if ($request->isOptions()) {
            return response('', 204)
                ->header([
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With, token, Accept, Origin',
                    'Access-Control-Expose-Headers' => 'Authorization',
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Max-Age' => 1728000,
                ]);
        }

        $response = $next($request);

        $response->header([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With, token, Accept, Origin',
            'Access-Control-Expose-Headers' => 'Authorization',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => 1728000,
        ]);

        return $response;
    }
}
