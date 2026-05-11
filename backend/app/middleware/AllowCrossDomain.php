<?php
// +----------------------------------------------------------------------
// | 跨域中间件
// +----------------------------------------------------------------------
namespace app\middleware;

use think\Request;
use think\Response;

/**
 * 跨域处理中间件
 */
class AllowCrossDomain
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
        // OPTIONS 预检请求直接返回
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