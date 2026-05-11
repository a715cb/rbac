<?php
namespace app\miniapp\service;

use think\facade\Cache;

class TokenBlacklistService
{
    private static ?TokenBlacklistService $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function add(string $token, int $expireAt): bool
    {
        $key = $this->getCacheKey($token);
        $ttl = max($expireAt - time(), 0);
        if ($ttl <= 0) {
            return true;
        }
        return Cache::set($key, 1, $ttl);
    }

    public function isBlacklisted(string $token): bool
    {
        $key = $this->getCacheKey($token);
        return Cache::has($key);
    }

    private function getCacheKey(string $token): string
    {
        return 'token_blacklist:' . md5($token);
    }
}
