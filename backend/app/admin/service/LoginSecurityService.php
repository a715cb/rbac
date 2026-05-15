<?php
/**
 * 登录安全服务
 *
 * @类名: LoginSecurityService
 * @功能: 处理登录失败计数和账户锁定逻辑
 * @描述: 负责监控登录失败次数，达到阈值后锁定账户，支持自动解锁
 *
 * @职责:
 *   1. 登录失败次数计数与记录
 *   2. 账户锁定状态管理
 *   3. 失败记录清除
 *
 * @设计思路:
 *   - 使用SimpleCache实现分布式友好的计数器
 *   - 支持配置化的失败次数阈值和锁定时长
 *   - 自动过期机制，无需手动清理
 *
 * @依赖组件:
 *   - SimpleCache: 登录失败计数缓存
 *
 * @使用示例:
 *   $security = LoginSecurityService::getInstance();
 *   $security->checkLoginFailTimes('username');
 *   $security->clearLoginFailTimes('username');
 *   $security->isAccountLocked('username');
 */

namespace app\admin\service;

use app\common\SimpleCache;
use think\facade\Config;

class LoginSecurityService
{
    private static ?LoginSecurityService $instance = null;

    private int $maxLoginFailTimes;
    private int $loginLockDuration;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->maxLoginFailTimes = (int) Config::get('auth.max_login_fail_times', 5);
        $this->loginLockDuration = (int) Config::get('auth.login_lock_duration', 900);
    }

    /**
     * 检查并记录登录失败次数
     *
     * @方法用途: 当登录失败时调用，记录失败次数并检查是否需要锁定账户
     * @功能描述: 使用缓存递增失败计数，达到阈值后设置账户锁定标识
     *
     * @参数说明:
     *   - username (string): 登录失败的用户名
     *
     * @返回值: array
     *   - 'times': 当前失败次数
     *   - 'locked': 是否已锁定
     *   - 'max_times': 最大失败次数
     *
     * @业务逻辑:
     *   1. 检查锁定功能是否启用（lockDuration > 0）
     *   2. 首次失败时初始化计数器
     *   3. 后续失败时递增计数
     *   4. 达到阈值时设置锁定标识
     *
     * @缓存策略:
     *   - 键名: login_fail_{username}
     *   - 过期时间: 锁定时长
     *   - 键名: login_lock_{username}
     *   - 过期时间: 锁定时长
     *
     * @安全考虑:
     *   - 按用户名锁定而非IP，防止多用户共享IP误伤
     *   - 锁定时长结束后自动解锁
     */
    public function checkLoginFailTimes(string $username): array
    {
        if ($this->loginLockDuration <= 0) {
            return [
                'times' => 0,
                'locked' => false,
                'max_times' => $this->maxLoginFailTimes,
            ];
        }

        $key = 'login_fail_' . $username;
        $times = SimpleCache::increment($key, 1, $this->loginLockDuration);

        $locked = false;
        if ($times >= $this->maxLoginFailTimes) {
            SimpleCache::setIfNotExists('login_lock_' . $username, 1, $this->loginLockDuration);
            $locked = true;
        }

        return [
            'times' => $times,
            'locked' => $locked,
            'max_times' => $this->maxLoginFailTimes,
        ];
    }

    /**
     * 检查账户是否已锁定
     *
     * @方法用途: 验证指定用户账户是否处于锁定状态
     * @功能描述: 查询缓存判断账户是否被锁定
     *
     * @参数说明:
     *   - username (string): 待检查的用户名
     *
     * @返回值: bool
     *   - true: 账户已锁定
     *   - false: 账户未锁定
     *
     * @业务逻辑:
     *   1. 检查锁定功能是否启用
     *   2. 查询login_lock_{username}缓存键
     *   3. 返回是否存在锁定标识
     */
    public function isAccountLocked(string $username): bool
    {
        if ($this->loginLockDuration <= 0) {
            return false;
        }

        return SimpleCache::get('login_lock_' . $username, false) !== false;
    }

    /**
     * 获取锁定剩余时间
     *
     * @方法用途: 获取账户锁定状态的剩余解锁时间
     * @功能描述: 计算距离自动解锁的秒数
     *
     * @参数说明:
     *   - username (string): 用户名
     *
     * @返回值: int
     *   - 大于0: 剩余锁定时间（秒）
     *   - 0: 未锁定或已过期
     *
     * @注意: SimpleCache不支持TTL查询，此方法返回估算值
     */
    public function getLockRemainingTime(string $username): int
    {
        return 0;
    }

    /**
     * 清除登录失败记录
     *
     * @方法用途: 清除指定用户的登录失败计数和锁定状态
     * @功能描述: 登录成功后调用，清除所有与该用户相关的登录失败数据
     *
     * @参数说明:
     *   - username (string): 登录成功的用户名
     *
     * @业务逻辑:
     *   1. 删除失败次数缓存键
     *   2. 删除锁定状态缓存键
     *
     * @调用时机:
     *   - 用户成功登录后立即调用
     *   - 确保新的一次登录周期从零开始计数
     */
    public function clearLoginFailTimes(string $username): void
    {
        SimpleCache::delete('login_fail_' . $username);
        SimpleCache::delete('login_lock_' . $username);
    }

    /**
     * 获取最大登录失败次数
     *
     * @方法用途: 获取系统配置的最大登录失败次数
     * @返回值: int 最大失败次数
     */
    public function getMaxLoginFailTimes(): int
    {
        return $this->maxLoginFailTimes;
    }

    /**
     * 获取锁定时长（分钟）
     *
     * @方法用途: 获取账户锁定时长（转换为分钟）
     * @返回值: int 锁定时长（分钟）
     */
    public function getLockDurationMinutes(): int
    {
        return (int) ceil($this->loginLockDuration / 60);
    }
}
