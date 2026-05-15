<?php
declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use app\admin\service\DepartmentService;
use ReflectionMethod;

/**
 * 部门管理服务测试
 *
 * @测试目标: DepartmentService
 * @测试内容:
 *   - 单例模式验证
 *   - 搜索关键词校验逻辑
 *   - 部门编码唯一性校验逻辑
 *   - 父部门有效性校验逻辑
 *   - 父部门名称获取逻辑
 *   - 返回值结构一致性
 *
 * @覆盖场景:
 *   - 服务单例获取
 *   - 关键词长度校验（超长/边界/合法）
 *   - 编码唯一性校验（新建/更新排除自身）
 *   - 父部门校验（顶级部门/子部门）
 *   - 父部门名称获取（顶级/子级/不存在）
 *   - 服务初始化性能
 */
class DepartmentServiceTest extends TestCase
{
    private DepartmentService $departmentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->departmentService = DepartmentService::getInstance();
    }

    /**
     * 测试单例模式
     */
    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = DepartmentService::getInstance();
        $instance2 = DepartmentService::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    /**
     * 测试关键词校验 - 合法关键词
     */
    public function testValidateKeywordValid(): void
    {
        $method = $this->getProtectedMethod('validateKeyword');

        $result = $method->invoke($this->departmentService, '');
        $this->assertTrue($result['valid']);

        $result = $method->invoke($this->departmentService, '技术部');
        $this->assertTrue($result['valid']);

        $result = $method->invoke($this->departmentService, str_repeat('测', 50));
        $this->assertTrue($result['valid']);
    }

    /**
     * 测试关键词校验 - 原始长度超过200字符
     */
    public function testValidateKeywordExceedsRawLimit(): void
    {
        $method = $this->getProtectedMethod('validateKeyword');

        $keyword = str_repeat('a', 201);
        $result = $method->invoke($this->departmentService, $keyword);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('200', $result['error']);
    }

    /**
     * 测试关键词校验 - trim后长度超过50字符
     */
    public function testValidateKeywordExceedsTrimmedLimit(): void
    {
        $method = $this->getProtectedMethod('validateKeyword');

        $keyword = str_repeat('a', 51);
        $result = $method->invoke($this->departmentService, $keyword);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('50', $result['error']);
    }

    /**
     * 测试关键词校验 - 边界值（恰好200字符原始且trim后不超过50/恰好50字符trim后）
     */
    public function testValidateKeywordBoundary(): void
    {
        $method = $this->getProtectedMethod('validateKeyword');

        $keyword50WithSpaces = str_repeat('a', 50) . str_repeat(' ', 150);
        $result = $method->invoke($this->departmentService, $keyword50WithSpaces);
        $this->assertTrue($result['valid']);

        $result = $method->invoke($this->departmentService, str_repeat('a', 50));
        $this->assertTrue($result['valid']);
    }

    /**
     * 测试关键词校验 - 带前后空格的关键词
     */
    public function testValidateKeywordWithSpaces(): void
    {
        $method = $this->getProtectedMethod('validateKeyword');

        $keyword = '  ' . str_repeat('a', 50) . '  ';
        $result = $method->invoke($this->departmentService, $keyword);
        $this->assertTrue($result['valid']);

        $keyword = '  ' . str_repeat('a', 51) . '  ';
        $result = $method->invoke($this->departmentService, $keyword);
        $this->assertFalse($result['valid']);
    }

    /**
     * 测试编码唯一性校验 - 新建场景（无排除ID）
     */
    public function testValidateCodeUniqueWithoutExclude(): void
    {
        $method = $this->getProtectedMethod('validateCodeUnique');

        try {
            $result = $method->invoke($this->departmentService, 'UNIQUE_TEST_CODE_99999');
            $this->assertArrayHasKey('valid', $result);
            $this->assertArrayHasKey('error', $result);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过编码唯一性测试');
            }
            throw $e;
        }
    }

    /**
     * 测试编码唯一性校验 - 更新场景（排除自身ID）
     */
    public function testValidateCodeUniqueWithExclude(): void
    {
        $method = $this->getProtectedMethod('validateCodeUnique');

        try {
            $result = $method->invoke($this->departmentService, 'UNIQUE_TEST_CODE_99999', 1);
            $this->assertArrayHasKey('valid', $result);
            $this->assertArrayHasKey('error', $result);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过编码唯一性排除测试');
            }
            throw $e;
        }
    }

    /**
     * 测试父部门校验 - 顶级部门（parent_id = 0）
     */
    public function testValidateParentDeptTopLevel(): void
    {
        $method = $this->getProtectedMethod('validateParentDept');

        $result = $method->invoke($this->departmentService, 0);
        $this->assertTrue($result['valid']);
        $this->assertEquals('', $result['error']);
    }

    /**
     * 测试父部门校验 - 子部门（parent_id > 0，需数据库）
     */
    public function testValidateParentDeptChildLevel(): void
    {
        $method = $this->getProtectedMethod('validateParentDept');

        try {
            $result = $method->invoke($this->departmentService, 1);
            $this->assertArrayHasKey('valid', $result);
            $this->assertArrayHasKey('error', $result);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过父部门校验测试');
            }
            throw $e;
        }
    }

    /**
     * 测试获取父部门名称 - 顶级部门
     */
    public function testGetParentNameTopLevel(): void
    {
        $method = $this->getProtectedMethod('getParentName');

        $result = $method->invoke($this->departmentService, 0);
        $this->assertEquals('', $result);

        $result = $method->invoke($this->departmentService, -1);
        $this->assertEquals('', $result);
    }

    /**
     * 测试获取父部门名称 - 子部门（需数据库）
     */
    public function testGetParentNameChildLevel(): void
    {
        $method = $this->getProtectedMethod('getParentName');

        try {
            $result = $method->invoke($this->departmentService, 1);
            $this->assertIsString($result);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过父部门名称测试');
            }
            throw $e;
        }
    }

    /**
     * 测试 getDepartmentList 返回结构
     */
    public function testGetDepartmentListReturnStructure(): void
    {
        try {
            $result = $this->departmentService->getDepartmentList('', null);

            $this->assertArrayHasKey('success', $result);
            $this->assertIsBool($result['success']);

            if ($result['success']) {
                $this->assertArrayHasKey('data', $result);
                $this->assertArrayHasKey('list', $result['data']);
            } else {
                $this->assertArrayHasKey('error', $result);
                $this->assertArrayHasKey('code', $result);
            }
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过列表查询测试');
            }
            throw $e;
        }
    }

    /**
     * 测试 getDepartmentList 关键词校验失败时返回结构
     */
    public function testGetDepartmentListInvalidKeyword(): void
    {
        $result = $this->departmentService->getDepartmentList(str_repeat('a', 201), null);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertEquals(422, $result['code']);
    }

    /**
     * 测试 getDepartmentTree 返回结构
     */
    public function testGetDepartmentTreeReturnStructure(): void
    {
        try {
            $result = $this->departmentService->getDepartmentTree(null);

            $this->assertTrue($result['success']);
            $this->assertArrayHasKey('data', $result);
            $this->assertArrayHasKey('tree', $result['data']);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过树形查询测试');
            }
            throw $e;
        }
    }

    /**
     * 测试 getDepartmentDetail 不存在的部门
     */
    public function testGetDepartmentDetailNotFound(): void
    {
        try {
            $result = $this->departmentService->getDepartmentDetail(999999);

            $this->assertFalse($result['success']);
            $this->assertEquals('部门不存在', $result['error']);
            $this->assertEquals(404, $result['code']);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过详情查询测试');
            }
            throw $e;
        }
    }

    /**
     * 测试 deleteDepartment 非法ID
     */
    public function testDeleteDepartmentInvalidId(): void
    {
        $result = $this->departmentService->deleteDepartment(0);

        $this->assertFalse($result['success']);
        $this->assertEquals('参数错误', $result['error']);
        $this->assertEquals(422, $result['code']);

        $result = $this->departmentService->deleteDepartment(-1);
        $this->assertFalse($result['success']);
    }

    /**
     * 测试 changeStatus 不存在的部门
     */
    public function testChangeStatusNotFound(): void
    {
        try {
            $result = $this->departmentService->changeStatus(999999, 0);

            $this->assertFalse($result['success']);
            $this->assertEquals('部门不存在', $result['error']);
            $this->assertEquals(404, $result['code']);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过状态切换测试');
            }
            throw $e;
        }
    }

    /**
     * 测试 changeSort 不存在的部门
     */
    public function testChangeSortNotFound(): void
    {
        try {
            $result = $this->departmentService->changeSort(999999, 1);

            $this->assertFalse($result['success']);
            $this->assertEquals('部门不存在', $result['error']);
            $this->assertEquals(404, $result['code']);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过排序更新测试');
            }
            throw $e;
        }
    }

    /**
     * 测试 getDepartmentUsers 不存在的部门
     */
    public function testGetDepartmentUsersNotFound(): void
    {
        try {
            $result = $this->departmentService->getDepartmentUsers(999999);

            $this->assertFalse($result['success']);
            $this->assertEquals('部门不存在', $result['error']);
            $this->assertEquals(404, $result['code']);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过部门用户查询测试');
            }
            throw $e;
        }
    }

    /**
     * 测试服务初始化性能
     */
    public function testServiceInitializationPerformance(): void
    {
        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $service = DepartmentService::getInstance();
        }
        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, '单例获取100次应在1秒内完成');
    }

    /**
     * 通过反射获取 protected 方法
     */
    private function getProtectedMethod(string $methodName): ReflectionMethod
    {
        $method = new ReflectionMethod(DepartmentService::class, $methodName);
        $method->setAccessible(true);
        return $method;
    }
}
