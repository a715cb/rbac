<?php
namespace app\miniapp\service;

use think\facade\Config;
use think\facade\Log;

class WechatService
{
    private static ?WechatService $instance = null;

    private ?\EasyWeChat\MiniApp\Application $app = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getApp(): \EasyWeChat\MiniApp\Application
    {
        if ($this->app === null) {
            $appId = env('WX_MINIAPP_APPID', Config::get('wechat.miniapp.app_id', ''));
            $appSecret = env('WX_MINIAPP_SECRET', Config::get('wechat.miniapp.secret', ''));

            if (empty($appId) || empty($appSecret)) {
                throw new \RuntimeException('微信小程序配置不完整');
            }

            $this->app = new \EasyWeChat\MiniApp\Application([
                'app_id' => $appId,
                'secret' => $appSecret,
            ]);
        }
        return $this->app;
    }

    public function code2Session(string $code, int $maxRetries = 3, int $timeoutMs = 5000): array
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $app = $this->getApp();
                $utils = $app->getUtils();
                $session = $utils->codeToSession($code);

                return [
                    'openid' => $session['openid'] ?? '',
                    'session_key' => $session['session_key'] ?? '',
                    'unionid' => $session['unionid'] ?? '',
                ];
            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning('微信code2Session第' . $attempt . '次尝试失败：' . $e->getMessage());

                if ($attempt < $maxRetries) {
                    $delayMs = min($timeoutMs, 1000 * $attempt);
                    usleep($delayMs * 1000);
                }
            }
        }

        Log::error('微信code2Session失败，已重试' . $maxRetries . '次：' . $lastException->getMessage());
        throw new \RuntimeException('微信登录失败，请稍后重试');
    }

    public function decryptPhone(string $sessionKey, string $iv, string $encryptedData): string
    {
        try {
            $app = $this->getApp();
            $utils = $app->getUtils();
            $decrypted = $utils->decryptSession($sessionKey, $iv, $encryptedData);
            return $decrypted['phoneNumber'] ?? '';
        } catch (\Exception $e) {
            Log::error('微信手机号解密失败：' . $e->getMessage());
            throw new \RuntimeException('手机号解密失败');
        }
    }

    public function getAccessToken(): string
    {
        try {
            $app = $this->getApp();
            return $app->getAccessToken()->getToken();
        } catch (\Exception $e) {
            Log::error('获取微信AccessToken失败：' . $e->getMessage());
            throw new \RuntimeException('获取AccessToken失败');
        }
    }
}
