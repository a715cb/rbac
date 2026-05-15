<?php
/**
 * @文件: SimpleCache.php
 * @用途: 统一缓存操作入口，委托 ThinkPHP 缓存系统执行实际存储
 * @描述: 通过 ThinkPHP 的 Cache 门面统一调度缓存驱动，确保 CACHE_DRIVER 配置生效。
 *        当驱动为 Redis 时，利用 Redis 原生命令（INCRBY、SET NX EX）实现原子递增和
 *        SetIfNotExists 语义；非 Redis 驱动下回退为读-改-写模式
 * @核心逻辑:
 *   1. 所有缓存操作委托 ThinkPHP Cache 门面，遵循 CACHE_DRIVER 配置
 *   2. increment 操作：Redis 驱动使用 INCRBY 原子递增，其他驱动使用读-改-写
 *   3. setIfNotExists 操作：Redis 驱动使用 SET NX EX 原子命令，其他驱动使用 has+set
 *   4. TTL 为 0 表示永不过期，传递给 Cache 时使用 0 而非 null
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
                    // Predis：SETNX + EXPIRE（SETNX 与 EXPIRE 间存在极小窗口）
                    $result = $redis->setnx($cacheKey, $serialized);
                    if ($result) {
                        $redis->expire($cacheKey, $ttl);
                    }
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
}
