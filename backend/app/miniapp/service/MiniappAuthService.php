<?php
namespace app\miniapp\service;

use app\common\JwtToken;
use app\model\WxUser;
use app\model\User;
use think\facade\Config;
use think\facade\Log;

class MiniappAuthService
{
    private static ?MiniappAuthService $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function loginByCode(string $code): array
    {
        $wechatService = WechatService::getInstance();
        $session = $wechatService->code2Session($code);

        $openid = $session['openid'];
        $sessionKey = $session['session_key'];
        $unionid = $session['unionid'] ?? '';

        if (empty($openid)) {
            throw new \RuntimeException('获取openid失败');
        }

        $wxUser = WxUser::findByOpenid($openid);

        if (!$wxUser) {
            $wxUser = new WxUser();
            $wxUser->openid = $openid;
            $wxUser->session_key = $sessionKey;
            $wxUser->unionid = $unionid;
            $wxUser->status = 1;
            $wxUser->save();
        } else {
            $wxUser->session_key = $sessionKey;
            if ($unionid) {
                $wxUser->unionid = $unionid;
            }
            $wxUser->save();
        }

        return $this->generateTokenResponse($wxUser);
    }

    public function updatePhone(int $wxUserId, string $iv, string $encryptedData): array
    {
        if (empty($iv) || empty($encryptedData)) {
            throw new \InvalidArgumentException('解密参数不能为空');
        }

        $wxUser = WxUser::find($wxUserId);
        if (!$wxUser) {
            throw new \RuntimeException('用户不存在');
        }

        if (empty($wxUser->session_key)) {
            throw new \RuntimeException('session_key已过期，请重新登录');
        }

        $wechatService = WechatService::getInstance();
        $phone = $wechatService->decryptPhone($wxUser->session_key, $iv, $encryptedData);

        if (empty($phone)) {
            throw new \RuntimeException('手机号解密失败');
        }

        $wxUser->phone = $phone;

        $sysUser = User::where('mobile', $phone)->whereNull('delete_time')->find();
        if ($sysUser && !$wxUser->sys_user_id) {
            $wxUser->sys_user_id = $sysUser->id;
        }

        $wxUser->save();

        return $this->generateTokenResponse($wxUser);
    }

    public function updateProfile(int $wxUserId, string $nickname = '', string $avatar = ''): array
    {
        if (empty($nickname) && empty($avatar)) {
            throw new \InvalidArgumentException('至少需要更新昵称或头像');
        }

        $wxUser = WxUser::find($wxUserId);
        if (!$wxUser) {
            throw new \RuntimeException('用户不存在');
        }

        if ($nickname) {
            $wxUser->nickname = $nickname;
        }
        if ($avatar) {
            $wxUser->avatar = $avatar;
        }

        $wxUser->save();

        return $this->generateTokenResponse($wxUser);
    }

    public function refreshToken(string $refreshToken): array
    {
        try {
            if (TokenBlacklistService::getInstance()->isBlacklisted($refreshToken)) {
                throw new \RuntimeException('刷新令牌已失效，请重新登录');
            }

            $payload = JwtToken::parse($refreshToken);

            if (!isset($payload['type']) || $payload['type'] !== 'refresh_miniapp') {
                throw new \RuntimeException('无效的刷新令牌');
            }

            $now = time();
            $originalIat = $payload['iat'] ?? $now;
            $refreshTtl = (int) (Config::get('jwt.refresh_ttl') ?: env('JWT_REFRESH_TTL', 10080));
            if ($now - $originalIat > $refreshTtl * 60) {
                throw new \RuntimeException('刷新令牌已超过最大有效期，请重新登录');
            }

            $wxUserId = $payload['wx_user_id'] ?? 0;
            $wxUser = WxUser::find($wxUserId);
            if (!$wxUser || $wxUser->status !== 1) {
                throw new \RuntimeException('用户不存在或已被禁用');
            }

            $expireAt = (int) ($payload['exp'] ?? 0);
            TokenBlacklistService::getInstance()->add($refreshToken, $expireAt);

            return $this->generateTokenResponse($wxUser);
        } catch (\Exception $e) {
            throw new \RuntimeException('Token刷新失败：' . $e->getMessage());
        }
    }

    private function generateTokenResponse(WxUser $wxUser): array
    {
        $payload = [
            'wx_user_id' => $wxUser->id,
            'openid' => $wxUser->openid,
            'source' => 'miniapp',
            'type' => 'access_miniapp',
        ];

        if ($wxUser->sys_user_id) {
            $payload['sys_user_id'] = $wxUser->sys_user_id;
        }

        $accessToken = JwtToken::generate($payload);

        $refreshPayload = array_merge($payload, ['type' => 'refresh_miniapp']);
        $refreshTtl = (int) (Config::get('jwt.refresh_ttl') ?: env('JWT_REFRESH_TTL', 10080));
        $refreshToken = JwtToken::generate($refreshPayload, $refreshTtl);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => (int) Config::get('jwt.ttl', 1440) * 60,
            'user_info' => [
                'id' => $wxUser->id,
                'openid' => $wxUser->openid,
                'nickname' => $wxUser->nickname,
                'avatar' => $wxUser->avatar,
                'phone' => $wxUser->phone,
                'sys_user_id' => $wxUser->sys_user_id,
                'is_linked' => !empty($wxUser->sys_user_id),
            ],
        ];
    }
}
