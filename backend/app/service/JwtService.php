<?php
namespace app\service;

use app\common\JwtToken;
use think\Service;
use think\facade\Log;

class JwtService extends Service
{
    public function register()
    {
        $this->app->bind('jwt.token', JwtToken::class);
    }

    public function boot()
    {
        $secret = config('jwt.secret') ?: env('JWT_SECRET', '');

        if (empty($secret)) {
            throw new \RuntimeException('JWT_SECRET 未配置，请在 .env 文件中设置强随机密钥');
        }

        if (strlen($secret) < 32 && !env('APP_DEBUG', false)) {
            throw new \RuntimeException('JWT_SECRET 长度不足 32 字符，生产环境禁止使用弱密钥！请通过环境变量 JWT_SECRET 设置强随机密钥');
        }
        
        $this->validateJwtConfig();
    }

    protected function validateJwtConfig(): void
    {
        $secret = config('jwt.secret') ?: env('JWT_SECRET', '');

        if (empty($secret)) {
            throw new \RuntimeException('JWT_SECRET 未配置，请在 .env 文件中设置强随机密钥');
        }

        if (strlen($secret) < 32) {
            if (!env('APP_DEBUG', false)) {
                throw new \RuntimeException('JWT_SECRET 长度不足 32 字符，生产环境禁止使用弱密钥！请通过环境变量 JWT_SECRET 设置强随机密钥');
            }
            Log::warning('JWT_SECRET 长度不足 32 字符，开发环境允许使用但生产环境必须更换强随机密钥');
        }

        $hasLower = preg_match('/[a-z]/', $secret);
        $hasUpper = preg_match('/[A-Z]/', $secret);
        $hasDigit = preg_match('/[0-9]/', $secret);
        $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $secret);

        $charsetTypes = (int)$hasLower + (int)$hasUpper + (int)$hasDigit + (int)$hasSpecial;
        if ($charsetTypes < 2) {
            throw new \RuntimeException('JWT_SECRET 必须包含至少 2 种字符类型，建议使用 64 字符强随机密钥');
        }
    }

    public function generateToken(array $payload): string
    {
        return JwtToken::generate($payload);
    }

    public function parseToken(string $token): array
    {
        return JwtToken::parse($token);
    }

    public function refreshToken(string $token): string
    {
        return JwtToken::refresh($token);
    }

    public function validateToken(string $token): bool
    {
        return JwtToken::validate($token);
    }
}
