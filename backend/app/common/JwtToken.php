<?php
namespace app\common;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use think\facade\Config;
use think\facade\Log;

class JwtToken
{
    private static ?string $cachedSecret = null;

    public static function generate(array $payload): string
    {
        $secret = self::getSecret();
        $ttl = (int) (Config::get('jwt.ttl') ?: env('JWT_TTL', 1440));

        $now = time();
        $expire = $now + ($ttl * 60);

        $payload['exp'] = $expire;
        $payload['nbf'] = $now;
        if (!isset($payload['iat'])) {
            $payload['iat'] = $now;
        }

        return JWT::encode($payload, $secret, 'HS256');
    }

    public static function parse(string $token): array
    {
        $secret = self::getSecret();

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new \RuntimeException('Token 已过期');
        } catch (SignatureInvalidException $e) {
            throw new \RuntimeException('Token 签名验证失败');
        } catch (\Exception $e) {
            throw new \RuntimeException('Token 解析失败：' . $e->getMessage());
        }
    }

    public static function validate(string $token): bool
    {
        try {
            self::parse($token);
            return true;
        } catch (\Exception $e) {
            Log::warning('JWT 验证失败：' . $e->getMessage());
            return false;
        }
    }

    public static function refresh(string $token): string
    {
        $payload = self::parse($token);

        if (($payload['type'] ?? '') !== 'refresh') {
            throw new \RuntimeException('非刷新令牌，无法刷新');
        }

        $now = time();
        if (isset($payload['nbf']) && $payload['nbf'] > $now) {
            throw new \RuntimeException('Token 尚未生效，无法刷新');
        }

        $originalIat = $payload['iat'] ?? $now;
        $maxTtl = (int) (Config::get('jwt.max_ttl') ?: env('JWT_MAX_TTL', 4320));
        if ($now - $originalIat > $maxTtl * 60) {
            throw new \RuntimeException('Token 已超过最大刷新有效期，请重新登录');
        }

        $payload['iat'] = $originalIat;
        unset($payload['exp'], $payload['nbf']);

        return self::generate($payload);
    }

    private static function getSecret(): string
    {
        if (self::$cachedSecret !== null) {
            return self::$cachedSecret;
        }

        $secret = Config::get('jwt.secret') ?: env('JWT_SECRET');
        self::validateSecret($secret);
        self::$cachedSecret = $secret;

        return $secret;
    }

    private static function validateSecret(string $secret): void
    {
        if (empty($secret)) {
            throw new \RuntimeException('JWT密钥不能为空');
        }
        if (strlen($secret) < 32) {
            throw new \RuntimeException('JWT密钥长度至少32位');
        }
    }

    public static function clearCache(): void
    {
        self::$cachedSecret = null;
    }
}