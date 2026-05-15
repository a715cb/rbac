<?php
/**
 * @文件: AppException.php
 * @用途: 全局异常处理器，将所有异常统一转换为 JSON 响应
 * @描述: 继承 ThinkPHP 的 Handle 类，覆盖 render 方法实现自定义异常输出格式。
 *        按异常类型分级处理：验证错误返回 422、HTTP 异常返回对应状态码、
 *        其他异常返回 500（调试模式下暴露详情，生产模式下隐藏）
 * @核心逻辑:
 *   1. ValidateException → 422，返回验证错误信息
 *   2. HttpException → 对应 HTTP 状态码，返回异常信息
 *   3. 其他异常 → 500，调试模式返回详情，生产模式返回固定提示
 */
namespace app\common\exception;

use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用自定义异常处理器
 * 通过 config/exception.php 配置绑定到 ThinkPHP 异常处理流程，
 * 确保所有未捕获异常均以统一 JSON 格式返回前端
 */
class AppException extends Handle
{
    /**
     * 渲染异常输出
     * @param \think\Request $request 当前请求对象
     * @param Throwable $e 捕获到的异常
     * @return Response 统一格式的 JSON 响应
     * @description 按异常类型返回不同 HTTP 状态码和消息体：
     *              - ValidateException: 422 参数验证失败
     *              - HttpException: 对应状态码（如 404、403）
     *              - 其他: 500 服务器内部错误
     */
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof ValidateException) {
            return json([
                'code' => 422,
                'msg' => $e->getMessage(),
                'data' => []
            ], 422);
        }

        if ($e instanceof HttpException) {
            return json([
                'code' => $e->getStatusCode(),
                'msg' => $e->getMessage(),
                'data' => []
            ], $e->getStatusCode());
        }

        // 调试模式返回实际错误信息，生产模式返回固定提示避免泄露内部细节
        return json([
            'code' => 500,
            'msg' => env('APP_DEBUG', false) ? $e->getMessage() : '服务器内部错误',
            'data' => []
        ], 500);
    }
}
