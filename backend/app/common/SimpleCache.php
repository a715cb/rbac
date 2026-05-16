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
 *   6. TTL 为 0 表示永不过期，传递给 Cache 时使用 0 而非 null
 */
namespace app\common;

use think\facade\Cache;
use think\cache\driver\Redis as RedisDriver;

/**
 * 统一缓存操作类
 * 委托 ThinkPHP 缓存系统执行实际存储，自动适配 Redis/File 等驱动
 * 注意：increment 和 setIfNotExists 在非 Redis 驱动下不保证原子性
 */
class SimpleCache
{
    /**
     * 从缓存中读取指定键的值
     * @param string $key 缓存键名
     * @param mixed $default 缓存未命中时的默认返回值
     * @return mixed 缓存值，未命中时返回 $default
     */
    public static function get(string $key, $default = null)
    {
        return Cache::get($key, $default);
    }

    /**
     * 将数据写入缓存
     * @param string $key 缓存键名
     * @param mixed $value 缓存值，支持任意可序列化的 PHP 数据类型
     * @param int $ttl 存活时间（秒），0 表示永不过期
     * @return bool 写入是否成功
     */
    public static function set(string $key, $value, int $ttl = 0): bool
    {
        return Cache::set($key, $value, $ttl > 0 ? $ttl : 0);
    }

    /**
     * 删除指定缓存键
     * @param string $key 缓存键名
     * @return bool 删除是否成功
     */
    public static function delete(string $key): bool
    {
        return Cache::delete($key);
    }

    /**
     * 原子递增缓存值
     * @param string $key 缓存键名
     * @param int $step 递增步长，默认为 1
     * @param int $ttl 存活时间（秒），仅在新建缓存时生效
     * @return int 递增后的新值
     * @description Redis 驱动使用 INCRBY 原子递增，新建键时通过 TTL 检测补设过期时间；
     *              非 Redis 驱动使用读-改-写模式（非原子）
     */
    public static function increment(string $key, int $step = 1, int $ttl = 0): int
    {
        $driver = Cache::store();

        if ($driver instanceof RedisDriver) {
            $redis = $driver->handler();
            $cacheKey = $driver->getCacheKey($key);

            // INCRBY 原子递增，键不存在时自动创建并设为 0 后递增
            $newValue = $redis->incrby($cacheKey, $step);

            // 新建键无过期时间时补设 TTL（ttl 返回 -1 表示无过期时间）
            if ($ttl > 0 && (int) $redis->ttl($cacheKey) === -1) {
                $redis->expire($cacheKey, $ttl);
            }

            return (int) $newValue;
        }

        // 非 Redis 驱动回退：读-改-写（非原子操作）
        $current = Cache::get($key, 0);
        $newValue = (int) $current + $step;
        Cache::set($key, $newValue, $ttl > 0 ? $ttl : 0);
        return $newValue;
    }

    /**
     * 仅当缓存键不存在时才写入数据（Set If Not Exists）
     * @param string $key 缓存键名
     * @param mixed $value 缓存值
     * @param int $ttl 存活时间（秒），0 表示永不过期
     * @return bool 写入是否成功。键已存在时返回 false
     * @description Redis 驱动使用 SET NX EX 原子命令保证并发安全；
     *              非 Redis 驱动使用 has + set 检查（非原子，存在极小竞态窗口）
     */
    public static function setIfNotExists(string $key, $value, int $ttl = 0): bool
    {
        $driver = Cache::store();

        if ($driver instanceof RedisDriver) {
            $redis = $driver->handler();
            $cacheKey = $driver->getCacheKey($key);
            $serialized = is_numeric($value) ? $value : serialize($value);

            if ($ttl > 0) {
                if (get_class($redis) === 'Redis') {
                    // phpredis：SET NX EX 原子命令
                    $result = $redis->set($cacheKey, $serialized, ['EX' => $ttl, 'NX']);
                } else {
                    // Predis：SET key value EX ttl NX 原子命令
                    $result = $redis->set($cacheKey, $serialized, 'EX', $ttl, 'NX');
                    $result = $result !== null;
                }
            } else {
                $result = $redis->setnx($cacheKey, $serialized);
            }

            return (bool) $result;
        }

        // 非 Redis 驱动回退：has + set（非原子操作）
        if (Cache::has($key)) {
            return false;
        }
        return Cache::set($key, $value, $ttl > 0 ? $ttl : 0);
    }

    /**
     * 带互斥锁的缓存读取，未命中时执行回调写入缓存
     * @param string $key 缓存键名
     * @param int $ttl 存活时间（秒）
     * @param callable $callback 缓存未命中时的数据获取回调
     * @param string|null $tag 缓存标签，用于批量清除
     * @return mixed 缓存值或回调返回值
     * @description 先尝试缓存命中；未命中时获取互斥锁后执行回调写入缓存；
     *              获取锁失败则等待重试读取，超时后降级执行回调并尝试写入缓存
     */
    public static function remember(string $key, int $ttl, callable $callback, ?string $tag = null): mixed
    {
        try {
            $value = Cache::get($key);
            if ($value !== null) {
                return $value;
            }
        } catch (\Throwable $e) {
            return $callback();
        }

        try {
            $locked = self::lock($key, 10);
        } catch (\Throwable $e) {
            $locked = false;
        }

        if ($locked) {
            try {
                $value = Cache::get($key);
                if ($value !== null) {
                    return $value;
                }

                $value = $callback();

                self::tagSet($key, $value, $ttl, $tag);

                return $value;
            } finally {
                self::unlock($key);
            }
        }

        for ($i = 0; $i < 20; $i++) {
            usleep(50000);
            try {
                $value = Cache::get($key);
                if ($value !== null) {
                    return $value;
                }
            } catch (\Throwable $e) {
                break;
            }
        }

        $value = $callback();
        if ($value !== null) {
            self::tagSet($key, $value, $ttl, $tag);
        }
        return $value;
    }

    /**
     * 获取互斥锁
     * @param string $key 锁键名
     * @param int $ttl 锁的存活时间（秒）
     * @return bool 是否获取成功
     */
    private static function lock(string $key, int $ttl): bool
    {
        return self::setIfNotExists("lock:" . $key, 1, $ttl);
    }

    /**
     * 释放互斥锁
     * @param string $key 锁键名
     */
    private static function unlock(string $key): void
    {
        self::delete("lock:" . $key);
    }

    /**
     * 计算带 ±10% 随机抖动的 TTL，防止缓存雪崩
     * @param int $baseTtl 基础 TTL（秒）
     * @return int 抖动后的 TTL，最小为 1
     */
    public static function getJitteredTtl(int $baseTtl): int
    {
        $jitter = (int) ($baseTtl * 0.1 * (mt_rand(-10, 10) / 10));
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
    private static function tagSet(string $key, $value, int $ttl, ?string $tag = null): bool
    {
        if ($tag !== null) {
            return Cache::tag($tag)->set($key, $value, $ttl);
        }
        return Cache::set($key, $value, $ttl);
    }
}
