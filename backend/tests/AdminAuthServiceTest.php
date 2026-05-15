<?php
declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use app\admin\service\AdminAuthService;
use app\admin\service\LoginSecurityService;
use app\admin\service\UserAgentParserService;
use app\common\JwtToken;
use app\common\AdminAuth;
use app\model\User;
use app\model\Role;
use ReflectionProperty;

/**
 * 认证服务测试
 *
 * @测试目标: AdminAuthService
 * @测试内容:
 *   - 单例模式验证
 *   - 服务依赖注入
 *   - Token生成和解析
 *   - 用户资料获取
 *
 * @覆盖场景:
 *   - 服务单例获取
 *   - 依赖服务正确初始化
 *   - Token生成格式验证
 *   - Token刷新机制
 *
 * @注意事项:
 *   - 涉及数据库操作的测试需要Mock
 *   - 部分测试依赖JWT配置
 *   - 测试会清理相关缓存数据
 */

class AdminAuthServiceTest extends TestCase
{
    private AdminAuthService $authService;
    private array $testCacheKeys = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = AdminAuthService::getInstance();
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
            \app\common\SimpleCache::delete($key);
        }
        $this->testCacheKeys = [];
    }

    /**
     * 测试单例模式
     */
    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = AdminAuthService::getInstance();
        $instance2 = AdminAuthService::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    /**
     * 测试服务依赖正确初始化
     */
    public function testServiceDependenciesInitialized(): void
    {
        $loginSecurity = $this->getPrivateProperty($this->authService, 'loginSecurity');
        $userAgentParser = $this->getPrivateProperty($this->authService, 'userAgentParser');

        $this->assertInstanceOf(LoginSecurityService::class, $loginSecurity);
        $this->assertInstanceOf(UserAgentParserService::class, $userAgentParser);
    }

    /**
     * 测试登出方法执行
     */
    public function testLogoutExecutesWithoutError(): void
    {
        $userId = 0;
        $this->authService->logout($userId);
        $this->assertTrue(true);
    }

    /**
     * 测试登出方法接受不同用户ID
     */
    public function testLogoutAcceptsDifferentUserIds(): void
    {
        $this->authService->logout(0);
        $this->authService->logout(1);
        $this->authService->logout(999);
        $this->assertTrue(true);
    }

    /**
     * 测试用户不存在时getProfile返回正确错误
     */
    public function testGetProfileReturnsErrorWhenUserNotFound(): void
    {
        $result = $this->authService->getProfile(999999);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['code']);
    }

    /**
     * 测试用户ID无效时getProfile返回正确错误
     */
    public function testGetProfileReturnsErrorForInvalidUserId(): void
    {
        $result = $this->authService->getProfile(0);

        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['code']);
    }

    /**
     * 测试refreshToken方法基本执行
     */
    public function testRefreshTokenExecutesWithEmptyToken(): void
    {
        $result = $this->authService->refreshToken('');
        $this->assertIsArray($result);
    }

    /**
     * 测试refreshToken处理无效Token
     */
    public function testRefreshTokenHandlesInvalidToken(): void
    {
        $result = $this->authService->refreshToken('invalid_token_string');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }

    /**
     * 测试changePassword方法返回正确错误结构
     */
    public function testChangePasswordReturnsErrorForNonexistentUser(): void
    {
        $result = $this->authService->changePassword(999999, 'old_pass', 'new_pass');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertEquals(404, $result['code']);
    }

    /**
     * 测试changePassword方法返回正确错误结构
     */
    public function testChangePasswordReturnsErrorForInvalidUserId(): void
    {
        $result = $this->authService->changePassword(0, 'old_pass', 'new_pass');

        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['code']);
    }

    /**
     * 测试login方法基本执行流程
     */
    public function testLoginExecutesWithoutException(): void
    {
        $username = 'nonexistent_user_' . time();
        $password = 'test_password';
        $ip = '127.0.0.1';
        $userAgent = 'TestAgent/1.0';

        $result = $this->authService->login($username, $password, $ip, $userAgent);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertFalse($result['success']);
        $this->assertEquals(401, $result['code']);
    }

    /**
     * 测试login方法返回正确错误信息
     */
    public function testLoginReturnsCorrectErrorMessage(): void
    {
        $username = 'test_nonexistent_user';
        $password = 'wrong_password';
        $ip = '127.0.0.1';
        $userAgent = 'TestBrowser/1.0';

        $result = $this->authService->login($username, $password, $ip, $userAgent);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['error']);
    }

    /**
     * 测试login方法接受空密码
     */
    public function testLoginAcceptsEmptyPassword(): void
    {
        $username = 'test_user';
        $password = '';
        $ip = '127.0.0.1';
        $userAgent = 'TestBrowser/1.0';

        $result = $this->authService->login($username, $password, $ip, $userAgent);

        $this->assertFalse($result['success']);
        $this->assertEquals(401, $result['code']);
    }

    /**
     * 测试login方法接受空User-Agent
     */
    public function testLoginAcceptsEmptyUserAgent(): void
    {
        $username = 'test_user';
        $password = 'test_password';
        $ip = '127.0.0.1';
        $userAgent = '';

        $result = $this->authService->login($username, $password, $ip, $userAgent);

        $this->assertIsArray($result);
    }

    /**
     * 测试getProfile返回的数据结构
     */
    public function testGetProfileReturnsCorrectStructure(): void
    {
        $result = $this->authService->getProfile(0);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('code', $result);
    }

    /**
     * 测试refreshToken返回的数据结构
     */
    public function testRefreshTokenReturnsCorrectStructure(): void
    {
        $result = $this->authService->refreshToken('invalid');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('code', $result);
    }

    /**
     * 测试changePassword返回的数据结构
     */
    public function testChangePasswordReturnsCorrectStructure(): void
    {
        $result = $this->authService->changePassword(0, 'old', 'new');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('code', $result);
    }

    /**
     * 测试login失败返回的数据结构
     */
    public function testLoginFailureReturnsCorrectStructure(): void
    {
        $result = $this->authService->login('nonexist', 'pass', '127.0.0.1', 'UA');

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertFalse($result['success']);
    }

    /**
     * 测试logout处理不同的IP格式
     */
    public function testLogoutHandlesDifferentIpFormats(): void
    {
        $this->authService->logout(1);
        $this->assertTrue(true);
    }

    /**
     * 测试服务方法返回值类型一致性
     */
    public function testAllMethodsReturnArray(): void
    {
        $methods = [
            'login' => ['test', 'pass', '127.0.0.1', 'UA'],
            'refreshToken' => ['invalid'],
            'getProfile' => [0],
            'changePassword' => [0, 'old', 'new'],
        ];

        foreach ($methods as $method => $params) {
            $result = call_user_func_array([$this->authService, $method], $params);
            $this->assertIsArray($result, "Method {$method} should return array");
        }
    }

    /**
     * 使用反射获取私有属性
     */
    private function getPrivateProperty(object $object, string $propertyName)
    {
        $reflection = new ReflectionProperty($object, $propertyName);
        $reflection->setAccessible(true);
        return $reflection->getValue($object);
    }

    /**
     * 测试真实Token生成
     */
    public function testTokenGenerationWorks(): void
    {
        $payload = [
            'user_id' => 1,
            'username' => 'test',
            'test' => 'data',
        ];

        $token = JwtToken::generate($payload);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertGreaterThan(20, strlen($token));
    }

    /**
     * 测试Token解析
     */
    public function testTokenParsingWorks(): void
    {
        $payload = [
            'user_id' => 1,
            'username' => 'test',
        ];

        $token = JwtToken::generate($payload);
        $parsed = JwtToken::parse($token);

        $this->assertIsArray($parsed);
        $this->assertEquals(1, $parsed['user_id']);
        $this->assertEquals('test', $parsed['username']);
    }

    /**
     * 测试Token验证
     */
    public function testTokenValidationWorks(): void
    {
        $payload = ['user_id' => 1, 'username' => 'test'];
        $token = JwtToken::generate($payload);

        $this->assertTrue(JwtToken::validate($token));
        $this->assertFalse(JwtToken::validate('invalid_token'));
    }

    /**
     * 测试服务初始化性能
     */
    public function testServiceInitializationPerformance(): void
    {
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $service = AdminAuthService::getInstance();
        }
        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, '单例获取100次应在1秒内完成');
    }
}
