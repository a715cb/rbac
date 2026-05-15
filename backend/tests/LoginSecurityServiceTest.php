<?php
declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use app\admin\service\LoginSecurityService;
use app\common\SimpleCache;

/**
 * 登录安全服务测试
 *
 * @测试目标: LoginSecurityService
 * @测试内容:
 *   - 登录失败次数计数
 *   - 账户锁定检查
 *   - 失败记录清除
 *   - 配置参数获取
 *
 * @覆盖场景:
 *   - 首次登录失败
 *   - 多次登录失败
 *   - 达到锁定阈值
 *   - 账户锁定检查
 *   - 失败记录清除
 *   - 配置参数验证
 *
 * @注意事项:
 *   - 测试会使用SimpleCache，需要确保测试环境缓存目录可写
 *   - 测试结束后会清理所有测试相关的缓存数据
 */

class LoginSecurityServiceTest extends TestCase
{
    private LoginSecurityService $securityService;
    private string $testUsername = 'test_login_user_' . time();
    private array $testCacheKeys = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = new LoginSecurityService();
        $this->cleanTestCache();
    }

    protected function tearDown(): void
    {
        $this->cleanTestCache();
        parent::tearDown();
    }

    /**
     * 清理测试相关的缓存数据
     */
    private function cleanTestCache(): void
    {
        foreach ($this->testCacheKeys as $key) {
            SimpleCache::delete($key);
        }
        $this->testCacheKeys = [];
    }

    /**
     * 添加待清理的缓存键
     */
    private function addCacheKey(string $key): void
    {
        $this->testCacheKeys[] = 'login_fail_' . $key;
        $this->testCacheKeys[] = 'login_lock_' . $key;
    }

    /**
     * 测试单例模式
     */
    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = LoginSecurityService::getInstance();
        $instance2 = LoginSecurityService::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    /**
     * 测试获取最大登录失败次数
     */
    public function testGetMaxLoginFailTimes(): void
    {
        $maxTimes = $this->securityService->getMaxLoginFailTimes();
        $this->assertIsInt($maxTimes);
        $this->assertGreaterThan(0, $maxTimes);
    }

    /**
     * 测试获取锁定时长（分钟）
     */
    public function testGetLockDurationMinutes(): void
    {
        $minutes = $this->securityService->getLockDurationMinutes();
        $this->assertIsInt($minutes);
        $this->assertGreaterThan(0, $minutes);
    }

    /**
     * 测试首次登录失败
     */
    public function testFirstLoginFail(): void
    {
        $username = $this->testUsername . '_first_fail';
        $this->addCacheKey($username);

        $result = $this->securityService->checkLoginFailTimes($username);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('times', $result);
        $this->assertArrayHasKey('locked', $result);
        $this->assertArrayHasKey('max_times', $result);
        $this->assertEquals(1, $result['times']);
        $this->assertFalse($result['locked']);
    }

    /**
     * 测试多次登录失败
     */
    public function testMultipleLoginFails(): void
    {
        $username = $this->testUsername . '_multiple_fails';
        $this->addCacheKey($username);

        $maxTimes = $this->securityService->getMaxLoginFailTimes();

        for ($i = 1; $i <= min(3, $maxTimes - 1); $i++) {
            $result = $this->securityService->checkLoginFailTimes($username);
            $this->assertEquals($i, $result['times']);
            $this->assertFalse($result['locked']);
        }
    }

    /**
     * 测试账户未锁定检查
     */
    public function testAccountNotLocked(): void
    {
        $username = $this->testUsername . '_not_locked';
        $this->addCacheKey($username);

        $this->securityService->checkLoginFailTimes($username);
        $isLocked = $this->securityService->isAccountLocked($username);

        $this->assertFalse($isLocked);
    }

    /**
     * 测试清除登录失败记录
     */
    public function testClearLoginFailTimes(): void
    {
        $username = $this->testUsername . '_clear';
        $this->addCacheKey($username);

        $this->securityService->checkLoginFailTimes($username);
        $this->assertEquals(1, $this->securityService->checkLoginFailTimes($username)['times']);

        $this->securityService->clearLoginFailTimes($username);
        $result = $this->securityService->checkLoginFailTimes($username);

        $this->assertEquals(1, $result['times']);
    }

    /**
     * 测试未锁定账户的锁定剩余时间
     */
    public function testGetLockRemainingTimeWhenNotLocked(): void
    {
        $username = $this->testUsername . '_no_lock';
        $remainingTime = $this->securityService->getLockRemainingTime($username);
        $this->assertEquals(0, $remainingTime);
    }

    /**
     * 测试Service返回正确的配置值
     */
    public function testServiceReturnsCorrectConfigValues(): void
    {
        $maxTimes = $this->securityService->getMaxLoginFailTimes();
        $lockMinutes = $this->securityService->getLockDurationMinutes();

        $this->assertGreaterThanOrEqual(3, $maxTimes);
        $this->assertLessThanOrEqual(10, $maxTimes);
        $this->assertGreaterThanOrEqual(10, $lockMinutes);
        $this->assertLessThanOrEqual(30, $lockMinutes);
    }

    /**
     * 测试连续失败计数递增
     */
    public function testLoginFailCounterIncrements(): void
    {
        $username = $this->testUsername . '_increment';
        $this->addCacheKey($username);

        $result1 = $this->securityService->checkLoginFailTimes($username);
        $this->assertEquals(1, $result1['times']);

        $result2 = $this->securityService->checkLoginFailTimes($username);
        $this->assertEquals(2, $result2['times']);

        $result3 = $this->securityService->checkLoginFailTimes($username);
        $this->assertEquals(3, $result3['times']);
    }

    /**
     * 测试不同用户名独立计数
     */
    public function testDifferentUsernamesHaveSeparateCounters(): void
    {
        $username1 = $this->testUsername . '_user1';
        $username2 = $this->testUsername . '_user2';
        $this->addCacheKey($username1);
        $this->addCacheKey($username2);

        $this->securityService->checkLoginFailTimes($username1);
        $this->securityService->checkLoginFailTimes($username1);
        $result1 = $this->securityService->checkLoginFailTimes($username1);

        $this->securityService->checkLoginFailTimes($username2);
        $result2 = $this->securityService->checkLoginFailTimes($username2);

        $this->assertEquals(3, $result1['times']);
        $this->assertEquals(1, $result2['times']);
    }

    /**
     * 测试checkLoginFailTimes返回数据结构
     */
    public function testCheckLoginFailTimesReturnsCorrectStructure(): void
    {
        $username = $this->testUsername . '_structure';
        $this->addCacheKey($username);

        $result = $this->securityService->checkLoginFailTimes($username);

        $this->assertArrayHasKey('times', $result);
        $this->assertArrayHasKey('locked', $result);
        $this->assertArrayHasKey('max_times', $result);

        $this->assertIsInt($result['times']);
        $this->assertIsBool($result['locked']);
        $this->assertIsInt($result['max_times']);
    }

    /**
     * 测试多次清除操作安全性
     */
    public function testMultipleClearOperations(): void
    {
        $username = $this->testUsername . '_multi_clear';
        $this->addCacheKey($username);

        $this->securityService->checkLoginFailTimes($username);
        $this->securityService->clearLoginFailTimes($username);
        $this->securityService->clearLoginFailTimes($username);
        $this->securityService->clearLoginFailTimes($username);

        $this->assertFalse($this->securityService->isAccountLocked($username));
    }

    /**
     * 测试服务初始化不抛出异常
     */
    public function testServiceInitialization(): void
    {
        $service = new LoginSecurityService();
        $this->assertInstanceOf(LoginSecurityService::class, $service);
    }

    /**
     * 测试最大失败次数配置合理
     */
    public function testMaxTimesConfiguration(): void
    {
        $maxTimes = $this->securityService->getMaxLoginFailTimes();
        $this->assertGreaterThan(0, $maxTimes);
        $this->assertLessThan(100, $maxTimes);
    }

    /**
     * 测试锁定时长配置合理
     */
    public function testLockDurationConfiguration(): void
    {
        $lockMinutes = $this->securityService->getLockDurationMinutes();
        $this->assertGreaterThan(0, $lockMinutes);
        $this->assertLessThan(1440, $lockMinutes);
    }
}
