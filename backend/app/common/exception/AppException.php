<?php
// +----------------------------------------------------------------------
// | 应用异常处理类
// +----------------------------------------------------------------------
namespace app\common\exception;

use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用自定义异常处理
 */
class AppException extends Handle
{
    /**
     * 渲染异常输出
     *
     * @param \think\Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 参数验证错误
        if ($e instanceof ValidateException) {
            return json([
                'code' => 422,
                'msg' => $e->getMessage(),
                'data' => []
            ], 422);
        }

        // HTTP 异常
        if ($e instanceof HttpException) {
            return json([
                'code' => $e->getStatusCode(),
                'msg' => $e->getMessage(),
                'data' => []
            ], $e->getStatusCode());
        }

        // 其他错误
        return json([
            'code' => 500,
            'msg' => env('APP_DEBUG', false) ? $e->getMessage() : '服务器内部错误',
            'data' => []
        ], 500);
    }
}
