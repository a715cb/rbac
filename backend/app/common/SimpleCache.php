<?php
/**
 * @文件: SimpleCache.php
 * @用途: 统一缓存操作入口，委托 ThinkPHP 缓存系统执行实际存储
 * @描述: 通过 ThinkPHP 的 Cache 门面统一调度缓存驱动，确保 CACHE_DRIVER 配置生效。
 *        当驱动为 Redis 时，利用 Redis 原生命令（INCRBY、SET NX EX）实现原子递增和
 *        SetIfNotExists 语义；非 Redis 驱动下回退为读-改-写模式。
 *        提供 remember 方法实现带互斥锁的缓存回填，支持标签分组和 TTL 抖动防雪崩
 * @核心逻辑:
 *   1. 所有缓存操作委托 ThinkPHP Cache 门面，遵循 CACHE_DRIVER 配置
 *   2. increment 操作：Redis 驱动使用 INCRBY 原子递增，其他驱动使用读-改-写
 *   3. setIfNotExists 操作：Redis 驱动使用 SET NX EX 原子命令，其他驱动使用 has+set
 *   4. remember 操作：互斥锁保护缓存回填，锁失败时等待重试，超时降级执行回调并尝试写入缓存
 *   5. 标签缓存：tagSet 写入带标签缓存，clearTag 按标签批量清除
 *   6. TTL 为 0 表示永不过期，负数视为 0；remember 使用 has+get 双重检测避免 null 值误判
 *   7. 互斥锁携带唯一标识，unlock 时验证所有权防止误释放
 */
namespace app\common;

use think\facade\Cache;
use think\cache\driver\Redis as RedisDriver;

class SimpleCache
{
    private const LOCK_PREFIX = 'lock:';
    private const LOCK_TTL = 10;
    private const LOCK_WAIT_INTERVAL_MS = 50000;
    private const LOCK_WAIT_ATTEMPTS = 20;
    private const TTL_JITTER_RATIO = 0.1;

    private static ?bool $isPhpRedis = null;

    /**
     * 规范化 TTL 值
     * @param int $ttl 原始 TTL（秒）
     * @return int 规范化后的 TTL。TTL 小于等于 0 时统一返回 0（表示永不过期）
     */
    private static function normalizeTtl(int $ttl): int
    {
        return $ttl > 0 ? $ttl : 0;
    }

    /**
     * 判断当前缓存驱动是否为 Redis
     * @return bool 当前驱动为 Redis 时返回 true，否则返回 false
     */
    private static function isRedisDriver(): bool
    {
        return Cache::store() instanceof RedisDriver;
    }

    /**
     * 判断 Redis 客户端是否为 phpredis 扩展
     * @param object $redis Redis 客户端实例，支持 \Redis（phpredis）或 \Predis\Client
     * @return bool phpredis 扩展返回 true，Predis 库返回 false
     * @description 第一次调用时通过 get_class 判断并缓存结果，后续调用直接返回缓存值
     */
    private static function isPhpRedisClient(object $redis): bool
    {
        if (self::$isPhpRedis === null) {
            self::$isPhpRedis = get_class($redis) === 'Redis';
        }
        return self::$isPhpRedis;
    }

    /**
     * 生成互斥锁唯一标识
     * @return string 由进程ID、对象哈希、随机数组成的唯一标识，格式为 "pid:objectId:random"
     * @description 组合多元素确保在分布式环境下同一进程的多个锁实例也不会冲突
     */
    private static function generateLockId(): string
    {
        return getmypid() . ':' . spl_object_id(new \stdClass()) . ':' . mt_rand();
    }

    /**
     * 获取缓存值
     * @param string $key 缓存键名
     * @param mixed $default 缓存不存在时返回的默认值
     * @return mixed 缓存值或默认值
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * 设置缓存值
     * @param string $key 缓存键名
     * @param mixed $value 缓存值，支持任意可序列化类型
     * @param int $ttl 存活时间（秒），0 表示永不过期，负数视为 0
     * @return bool 设置是否成功
     */
    public static function set(string $key, mixed $value, int $ttl = 0): bool
    {
        return Cache::set($key, $value, self::normalizeTtl($ttl));
    }

    /**
     * 删除缓存
     * @param string $key 缓存键名
     * @return bool 删除是否成功
     */
    public static function delete(string $key): bool
    {
        return Cache::delete($key);
    }

    /**
     * 检查缓存键是否存在
     * @param string $key 缓存键名
     * @return bool 键存在返回 true，不存在返回 false
     */
    public static function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * 原子递增缓存值
     * @param string $key 缓存键名
     * @param int $step 递增步长，默认为 1
     * @param int $ttl 存活时间（秒），仅在新建缓存时生效
     * @return int 递增后的新值
     */
    public static function increment(string $key, int $step = 1, int $ttl = 0): int
    {
        if (!self::isRedisDriver()) {
            $current = Cache::get($key, 0);
            $newValue = (int) $current + $step;
            Cache::set($key, $newValue, self::normalizeTtl($ttl));
            return $newValue;
        }

        $driver = Cache::store();
        $redis = $driver->handler();
        $cacheKey = $driver->getCacheKey($key);

        $newValue = $redis->incrby($cacheKey, $step);

        if ($ttl > 0 && (int) $redis->ttl($cacheKey) === -1) {
            $redis->expire($cacheKey, $ttl);
        }

        return (int) $newValue;
    }

    /**
     * 仅当缓存键不存在时才写入数据（Set If Not Exists）
     * @param string $key 缓存键名
     * @param mixed $value 缓存值
     * @param int $ttl 存活时间（秒），0 表示永不过期
     * @return bool 写入是否成功。键已存在时返回 false
     */
    public static function setIfNotExists(string $key, mixed $value, int $ttl = 0): bool
    {
        if (!self::isRedisDriver()) {
            if (Cache::has($key)) {
                return false;
            }
            return Cache::set($key, $value, self::normalizeTtl($ttl));
        }

        $driver = Cache::store();
        $redis = $driver->handler();
        $cacheKey = $driver->getCacheKey($key);
        $serialized = is_numeric($value) ? $value : serialize($value);

        if ($ttl > 0) {
            if (self::isPhpRedisClient($redis)) {
                $result = $redis->set($cacheKey, $serialized, ['EX' => $ttl, 'NX']);
            } else {
                $result = $redis->set($cacheKey, $serialized, 'EX', $ttl, 'NX');
                $result = $result !== null;
            }
        } else {
            $result = $redis->setnx($cacheKey, $serialized);
        }

        return (bool) $result;
    }

    /**
     * 带互斥锁的缓存读取，未命中时执行回调写入缓存
     * @param string $key 缓存键名
     * @param int $ttl 存活时间（秒）
     * @param callable $callback 缓存未命中时的数据获取回调
     * @param string|null $tag 缓存标签，用于批量清除
     * @return mixed 缓存值或回调返回值
     */
    public static function remember(string $key, int $ttl, callable $callback, ?string $tag = null): mixed
    {
        try {
            if (Cache::has($key)) {
                return Cache::get($key);
            }
        } catch (\Throwable) {
            return self::executeCallbackSafely($callback);
        }

        $lockId = self::generateLockId();

        try {
            $locked = self::lock($key, self::LOCK_TTL, $lockId);
        } catch (\Throwable) {
            $locked = false;
        }

        if ($locked) {
            try {
                if (Cache::has($key)) {
                    return Cache::get($key);
                }

                $value = self::executeCallbackSafely($callback);

                self::tagSet($key, $value, $ttl, $tag);

                return $value;
            } finally {
                self::unlock($key, $lockId);
            }
        }

        for ($i = 0; $i < self::LOCK_WAIT_ATTEMPTS; $i++) {
            usleep(self::LOCK_WAIT_INTERVAL_MS);
            try {
                if (Cache::has($key)) {
                    return Cache::get($key);
                }
            } catch (\Throwable) {
                break;
            }
        }

        $value = self::executeCallbackSafely($callback);
        self::tagSet($key, $value, $ttl, $tag);
        return $value;
    }

    /**
     * 安全执行回调函数
     * @param callable $callback 数据获取回调
     * @return mixed 回调返回值，回调抛出异常时返回 null
     * @description 捕获回调执行中的所有异常，防止异常导致缓存回填失败，保证服务可用性
     */
    private static function executeCallbackSafely(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 获取互斥锁
     * @param string $key 锁键名
     * @param int $ttl 锁的存活时间（秒）
     * @param string $lockId 锁唯一标识，用于验证所有权
     * @return bool 是否获取成功
     */
    private static function lock(string $key, int $ttl, string $lockId): bool
    {
        return self::setIfNotExists(self::LOCK_PREFIX . $key, $lockId, $ttl);
    }

    /**
     * 释放互斥锁（验证所有权后释放）
     * @param string $key 锁键名
     * @param string $lockId 锁唯一标识，与获取锁时的标识一致才允许释放
     */
    private static function unlock(string $key, string $lockId): void
    {
        $lockKey = self::LOCK_PREFIX . $key;

        if (self::isRedisDriver()) {
            try {
                $driver = Cache::store();
                $redis = $driver->handler();
                $cacheKey = $driver->getCacheKey($lockKey);

                $script = <<<'LUA'
if redis.call("GET", KEYS[1]) == ARGV[1] then
    return redis.call("DEL", KEYS[1])
else
    return 0
end
LUA;
                $redis->eval($script, [$cacheKey, $lockId], 1);
            } catch (\Throwable) {
                self::delete($lockKey);
            }
            return;
        }

        $currentId = self::get($lockKey);
        if ($currentId === $lockId) {
            self::delete($lockKey);
        }
    }

    /**
     * 计算带 ±10% 随机抖动的 TTL，防止缓存雪崩
     * @param int $baseTtl 基础 TTL（秒）
     * @return int 抖动后的 TTL，最小为 1
     */
    public static function getJitteredTtl(int $baseTtl): int
    {
        $jitter = (int) ($baseTtl * self::TTL_JITTER_RATIO * (mt_rand(-10, 10) / 10));
        return max(1, $baseTtl + $jitter);
    }

    /**
     * 按标签清除缓存
     * @param string $tag 缓存标签名
     * @return bool 清除是否成功
     */
    public static function clearTag(string $tag): bool
    {
        return Cache::tag($tag)->clear();
    }

    /**
     * 带标签的缓存写入
     * @param string $key 缓存键名
     * @param mixed $value 缓存值
     * @param int $ttl 存活时间（秒）
     * @param string|null $tag 缓存标签，为 null 时不使用标签
     * @return bool 写入是否成功
     */
    private static function tagSet(string $key, mixed $value, int $ttl, ?string $tag = null): bool
    {
        if ($tag !== null) {
            return Cache::tag($tag)->set($key, $value, self::normalizeTtl($ttl));
        }
        return Cache::set($key, $value, self::normalizeTtl($ttl));
    }
}
