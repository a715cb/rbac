<?php
namespace app\admin\middleware;

use app\common\JwtToken;
use think\facade\Config;

class AuthCheck
{
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