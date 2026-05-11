<?php
namespace app\miniapp\middleware;

use app\common\JwtToken;
use app\miniapp\service\TokenBlacklistService;
use app\model\WxUser;
use think\Request;

class MiniappAuth
{
    public function handle(Request $request, \Closure $next)
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

            if (TokenBlacklistService::getInstance()->isBlacklisted($token)) {
                throw new \RuntimeException('令牌已失效');
            }

            $source = $payload['source'] ?? '';
            if ($source !== 'miniapp') {
                throw new \RuntimeException('无效的小程序令牌');
            }

            $type = $payload['type'] ?? '';
            if ($type !== 'access_miniapp') {
                throw new \RuntimeException('令牌类型错误');
            }

            $wxUserId = (int) ($payload['wx_user_id'] ?? 0);
            if ($wxUserId <= 0) {
                throw new \RuntimeException('Token 中缺少有效的用户 ID');
            }

            $wxUser = WxUser::find($wxUserId);
            if (!$wxUser || $wxUser->status !== 1) {
                throw new \RuntimeException('用户不存在或已被禁用');
            }

            $request->wxUser = [
                'id' => $wxUser->id,
                'openid' => $wxUser->openid,
                'nickname' => $wxUser->nickname,
                'avatar' => $wxUser->avatar,
                'phone' => $wxUser->phone,
                'sys_user_id' => $wxUser->sys_user_id,
            ];

            return $next($request);
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, '已过期') !== false) {
                return json([
                    'code' => 401,
                    'msg' => '令牌已过期，请重新登录',
                    'data' => []
                ], 401);
            }

            return json([
                'code' => 401,
                'msg' => '认证失败：' . $msg,
                'data' => []
            ], 401);
        } catch (\Exception $e) {
            return json([
                'code' => 401,
                'msg' => '认证失败：' . $e->getMessage(),
                'data' => []
            ], 401);
        }
    }
}
