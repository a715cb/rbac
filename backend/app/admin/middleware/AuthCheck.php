<?php
/**
 * @file AuthCheck.php
 * @purpose 后台管理员身份认证中间件
 * @description 拦截后台请求，验证 JWT Token 的有效性，并将解析后的用户信息注入请求对象。
 *              当 Token 缺失、无效或用户已被禁用时，返回 401 未授权响应。
 * @note 本中间件应配置在路由中间件栈中，确保需要认证的接口均经过此中间件校验
 */

namespace app\admin\middleware;

use app\common\JwtToken;
use think\facade\Config;

/**
 * 后台认证检查中间件
 *
 * 负责从请求头中提取 Bearer Token，解析并验证用户身份，
 * 校验通过后将用户信息（id、username、realname）挂载到 $request->userInfo 上，
 * 供后续控制器和中间件使用。
 */
class AuthCheck
{
    /**
     * 处理请求：执行身份认证校验
     *
     * @param \think\Request $request 当前请求对象
     * @param \Closure $next 下一个中间件或控制器处理函数
     * @return \think\Response 认证通过则继续执行后续处理；认证失败则返回 401 JSON 响应
     *
     * 处理流程：
     *   1. 从 Authorization 请求头提取 Bearer Token
     *   2. 若 Token 为空，直接返回 401
     *   3. 使用 JwtToken::parse() 解析 Token，提取 user_id
     *   4. 若配置要求验证用户存在性（auth.verify_user_exists），查询数据库确认用户状态
     *   5. 将用户信息注入 $request->userInfo，放行请求
     */
    public function handle($request, \Closure $next)
    {
        $token = $request->header('Authorization', '');

        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }

        if (empty($token)) {
            return json([
                'code' => 401,
                'msg' => '未提供认证令牌',
                'data' => []
            ], 401);
        }

        try {
            $payload = JwtToken::parse($token);

            $userId = (int) ($payload['user_id'] ?? $payload['sub'] ?? 0);

            if ($userId <= 0) {
                throw new \RuntimeException('Token 中缺少有效的用户 ID');
            }

            if (Config::get('auth.verify_user_exists', true)) {
                if (class_exists('\app\model\User')) {
                    $user = \app\model\User::where('id', $userId)->whereNull('delete_time')->find();
                    if (!$user || $user->status !== 1) {
                        throw new \RuntimeException('用户不存在或已被禁用');
                    }
                }
            }

            $request->userInfo = [
                'id' => $userId,
                'username' => $payload['username'] ?? '',
                'realname' => $payload['realname'] ?? '',
            ];

            return $next($request);
        } catch (\Exception $e) {
            return json([
                'code' => 401,
                'msg' => '认证失败：' . $e->getMessage(),
                'data' => []
            ], 401);
        }
    }
}
