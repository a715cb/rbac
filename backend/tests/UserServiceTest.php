<?php
declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use app\admin\service\UserService;
use ReflectionMethod;

/**
 * 用户管理服务测试
 *
 * @测试目标: UserService
 * @测试内容:
 *   - 单例模式验证
 *   - 查询条件构建逻辑
 *   - 数据权限过滤逻辑
 *   - 密码强度校验逻辑
 *   - 主部门唯一性校验
 *   - 返回值结构一致性
 *
 * @覆盖场景:
 *   - 服务单例获取
 *   - 空参数列表查询
 *   - 关键词搜索条件构建
 *   - 状态/性别筛选条件构建
 *   - 部门筛选条件构建
 *   - 数据权限过滤（超管/仅本人/部门级）
 *   - 空列表关联数据预加载
 *   - 密码强度校验（合法/非法）
 *   - 主部门校验（无主部门/多个主部门/正常）
 */
class UserServiceTest extends TestCase
{
    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = UserService::getInstance();
    }

    /**
     * 测试单例模式
     */
    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = UserService::getInstance();
        $instance2 = UserService::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    /**
     * 测试空参数构建查询条件
     */
    public function testBuildListConditionsWithEmptyParams(): void
    {
        $method = $this->getProtectedMethod('buildListConditions');
        $result = $method->invoke($this->userService, []);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试关键词搜索条件构建
     */
    public function testBuildListConditionsKeyword(): void
    {
        $method = $this->getProtectedMethod('buildListConditions');
        $result = $method->invoke($this->userService, ['keyword' => 'test']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('like', $result[0][1]);
        $this->assertEquals('%test%', $result[0][2]);
    }

    /**
     * 测试状态筛选条件构建
     */
    public function testBuildListConditionsStatus(): void
    {
        $method = $this->getProtectedMethod('buildListConditions');

        $result = $method->invoke($this->userService, ['status' => 1]);
        $this->assertCount(1, $result);
        $this->assertEquals('=', $result[0][1]);
        $this->assertEquals(1, $result[0][2]);

        $resultNull = $method->invoke($this->userService, ['status' => null]);
        $this->assertEmpty($resultNull);

        $resultEmpty = $method->invoke($this->userService, ['status' => '']);
        $this->assertEmpty($resultEmpty);
    }

    /**
     * 测试性别筛选条件构建
     */
    public function testBuildListConditionsGender(): void
    {
        $method = $this->getProtectedMethod('buildListConditions');

        $result = $method->invoke($this->userService, ['gender' => 1]);
        $this->assertCount(1, $result);
        $this->assertEquals('=', $result[0][1]);
        $this->assertEquals(1, $result[0][2]);

        $resultNull = $method->invoke($this->userService, ['gender' => null]);
        $this->assertEmpty($resultNull);
    }

    /**
     * 测试部门筛选条件构建（含闭包条件）
     */
    public function testBuildListConditionsDeptId(): void
    {
        try {
            $method = $this->getProtectedMethod('buildListConditions');
            $result = $method->invoke($this->userService, ['dept_id' => 1]);

            $this->assertIsArray($result);
            $this->assertCount(1, $result);
            $this->assertIsCallable($result[0]);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过部门筛选条件测试');
            }
            throw $e;
        }
    }

    /**
     * 测试多条件组合构建
     */
    public function testBuildListConditionsMultipleParams(): void
    {
        $method = $this->getProtectedMethod('buildListConditions');
        $result = $method->invoke($this->userService, [
            'keyword' => 'admin',
            'status' => 1,
            'gender' => 2,
        ]);

        $this->assertCount(3, $result);
    }

    /**
     * 测试空列表关联数据预加载
     */
    public function testEnrichUserListWithRelationsEmptyList(): void
    {
        $method = $this->getProtectedMethod('enrichUserListWithRelations');
        $result = $method->invoke($this->userService, []);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试批量加载用户角色（空用户ID列表）
     */
    public function testBatchLoadUserRolesEmptyUserIds(): void
    {
        $method = $this->getProtectedMethod('batchLoadUserRoles');
        $result = $method->invoke($this->userService, []);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试批量加载部门名称（空部门ID列表）
     */
    public function testBatchLoadDeptNamesEmptyDeptIds(): void
    {
        $method = $this->getProtectedMethod('batchLoadDeptNames');
        $result = $method->invoke($this->userService, []);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试批量加载用户部门关联（空用户ID列表）
     */
    public function testBatchLoadUserDeptsEmptyUserIds(): void
    {
        $method = $this->getProtectedMethod('batchLoadUserDepts');
        $result = $method->invoke($this->userService, []);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试 applyDataScopeFilter 超管不追加过滤
     */
    public function testApplyDataScopeFilterSuperAdmin(): void
    {
        $authMock = $this->createMock(\app\common\AdminAuth::class);
        $authMock->method('isSuperAdmin')->willReturn(true);

        $where = [];
        $this->userService->applyDataScopeFilter($where, $authMock);

        $this->assertEmpty($where);
    }

    /**
     * 测试 applyDataScopeFilter 仅本人权限追加 id 条件
     */
    public function testApplyDataScopeFilterSelfOnly(): void
    {
        $authMock = $this->createMock(\app\common\AdminAuth::class);
        $authMock->method('isSuperAdmin')->willReturn(false);
        $authMock->method('isSelfOnly')->willReturn(true);
        $authMock->method('getUserId')->willReturn(42);

        $where = [];
        $this->userService->applyDataScopeFilter($where, $authMock);

        $this->assertCount(1, $where);
        $this->assertEquals('id', $where[0][0]);
        $this->assertEquals('=', $where[0][1]);
        $this->assertEquals(42, $where[0][2]);
    }

    /**
     * 测试 applyDataScopeFilter 部门级权限追加闭包条件
     */
    public function testApplyDataScopeFilterDeptScope(): void
    {
        try {
            $authMock = $this->createMock(\app\common\AdminAuth::class);
            $authMock->method('isSuperAdmin')->willReturn(false);
            $authMock->method('isSelfOnly')->willReturn(false);
            $authMock->method('getScopedDeptIds')->willReturn([1, 2, 3]);

            $where = [];
            $this->userService->applyDataScopeFilter($where, $authMock);

            $this->assertCount(1, $where);
            $this->assertIsCallable($where[0]);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过部门级权限过滤测试');
            }
            throw $e;
        }
    }

    /**
     * 测试 applyDataScopeFilter 空部门范围不追加条件
     */
    public function testApplyDataScopeFilterEmptyDeptScope(): void
    {
        $authMock = $this->createMock(\app\common\AdminAuth::class);
        $authMock->method('isSuperAdmin')->willReturn(false);
        $authMock->method('isSelfOnly')->willReturn(false);
        $authMock->method('getScopedDeptIds')->willReturn([]);

        $where = [];
        $this->userService->applyDataScopeFilter($where, $authMock);

        $this->assertEmpty($where);
    }

    /**
     * 测试密码强度校验 - 合法密码
     */
    public function testValidatePasswordStrengthValid(): void
    {
        $method = $this->getProtectedMethod('validatePasswordStrength');

        $this->assertTrue($method->invoke($this->userService, 'Abc123!@'));
        $this->assertTrue($method->invoke($this->userService, 'MyP@ssw0rd'));
        $this->assertTrue($method->invoke($this->userService, 'Test123$'));
    }

    /**
     * 测试密码强度校验 - 非法密码
     */
    public function testValidatePasswordStrengthInvalid(): void
    {
        $method = $this->getProtectedMethod('validatePasswordStrength');

        $this->assertFalse($method->invoke($this->userService, ''));
        $this->assertFalse($method->invoke($this->userService, 'short'));
        $this->assertFalse($method->invoke($this->userService, 'alllowercase1!'));
        $this->assertFalse($method->invoke($this->userService, 'ALLUPPERCASE1!'));
        $this->assertFalse($method->invoke($this->userService, 'NoDigits!@'));
        $this->assertFalse($method->invoke($this->userService, 'NoSpecial123'));
        $this->assertFalse($method->invoke($this->userService, 'Abc12!'));
    }

    /**
     * 测试主部门校验 - 无主部门
     */
    public function testValidatePrimaryDeptNoPrimary(): void
    {
        $method = $this->getProtectedMethod('validatePrimaryDept');
        $result = $method->invoke($this->userService, [
            ['dept_id' => 1, 'is_primary' => 0],
            ['dept_id' => 2, 'is_primary' => 0],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertEquals('必须指定一个主部门', $result['error']);
    }

    /**
     * 测试主部门校验 - 多个主部门
     */
    public function testValidatePrimaryDeptMultiplePrimary(): void
    {
        $method = $this->getProtectedMethod('validatePrimaryDept');
        $result = $method->invoke($this->userService, [
            ['dept_id' => 1, 'is_primary' => 1],
            ['dept_id' => 2, 'is_primary' => 1],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertEquals('只能有一个主部门', $result['error']);
    }

    /**
     * 测试主部门校验 - 正常情况
     */
    public function testValidatePrimaryDeptValid(): void
    {
        $method = $this->getProtectedMethod('validatePrimaryDept');
        $result = $method->invoke($this->userService, [
            ['dept_id' => 1, 'is_primary' => 1],
            ['dept_id' => 2, 'is_primary' => 0],
        ]);

        $this->assertTrue($result['valid']);
        $this->assertEquals(1, $result['primary_dept_id']);
    }

    /**
     * 测试服务初始化性能
     */
    public function testServiceInitializationPerformance(): void
    {
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $service = UserService::getInstance();
        }
        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, '单例获取100次应在1秒内完成');
    }

    /**
     * 通过反射获取 protected 方法
     */
    private function getProtectedMethod(string $methodName): ReflectionMethod
    {
        $method = new ReflectionMethod(UserService::class, $methodName);
        $method->setAccessible(true);
        return $method;
    }
}
