<?php
// +----------------------------------------------------------------------
// | 公共函数文件
// +----------------------------------------------------------------------

use app\common\JwtToken;

/**
 * 生成 JWT Token
 *
 * @param array $payload 载荷数据
 * @return string
 */
function generate_jwt(array $payload): string
{
    return JwtToken::generate($payload);
}

/**
 * 解析 JWT Token
 *
 * @param string $token JWT Token
 * @return array
 */
function parse_jwt(string $token): array
{
    return JwtToken::parse($token);
}

/**
 * 返回成功响应
 *
 * @param mixed $data 数据
 * @param string $msg 消息
 * @param int $code 状态码
 * @return \think\response\Json
 */
function success(mixed $data = [], string $msg = '操作成功', int $code = 200): \think\response\Json
{
    return json([
        'code' => $code,
        'msg' => $msg,
        'data' => $data,
    ]);
}

/**
 * 返回失败响应
 *
 * @param string $msg 消息
 * @param int $code 状态码
 * @param mixed $data 数据
 * @return \think\response\Json
 */
function error(string $msg = '操作失败', int $code = 400, mixed $data = []): \think\response\Json
{
    return json([
        'code' => $code,
        'msg' => $msg,
        'data' => $data,
    ]);
}
