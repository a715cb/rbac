<?php
declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use app\admin\service\ApiService;
use ReflectionMethod;

/**
 * 接口管理服务测试
 *
 * @测试目标: ApiService
 * @测试内容:
 *   - 单例模式验证
 *   - 查询条件构建逻辑
 *   - 接口标识唯一性校验逻辑
 *   - HTTP方法+路径组合唯一性校验逻辑
 *   - 菜单存在性校验逻辑
 *   - 菜单名称获取逻辑
 *   - 返回值结构一致性
 *
 * @覆盖场景:
 *   - 服务单例获取
 *   - 查询条件构建（关键词/状态/菜单/方法/分组/组合筛选）
 *   - 标识唯一性校验（新建/更新排除自身）
 *   - 方法+路径组合唯一性校验（新建/更新排除自身）
 *   - 菜单校验（存在/不存在）
 *   - 菜单名称获取（空ID/有效ID/不存在ID）
 *   - CRUD 返回结构验证
 *   - 服务初始化性能
 */
class ApiServiceTest extends TestCase
{
    private ApiService $apiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiService = ApiService::getInstance();
    }

    /**
     * 测试单例模式 - 多次获取返回同一实例
     */
    public function testGetInstanceReturnsSameInstance(): void
    {
        $instance1 = ApiService::getInstance();
        $instance2 = ApiService::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    /**
     * 测试查询条件构建 - 空参数
     */
    public function testBuildListWhereEmptyParams(): void
    {
        $method = $this->getProtectedMethod('buildListWhere');

        $result = $method->invoke($this->apiService, '', null, null, '', '');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试查询条件构建 - 关键词筛选
     */
    public function testBuildListWhereWithKeyword(): void
    {
        $method = $this->getProtectedMethod('buildListWhere');

        $result = $method->invoke($this->apiService, '用户', null, null, '', '');

        $this->assertCount(1, $result);
        $this->assertEquals(['name|code|path', 'like', '%用户%'], $result[0]);
    }

    /**
     * 测试查询条件构建 - 状态筛选
     */
    public function testBuildListWhereWithStatus(): void
    {
        $method = $this->getProtectedMethod('buildListWhere');

        $result = $method->invoke($this->apiService, '', 1, null, '', '');

        $this->assertCount(1, $result);
        $this->assertEquals(['status', '=', 1], $result[0]);
    }

    /**
     * 测试查询条件构建 - 状态为空字符串时不筛选
     */
    public function testBuildListWhereWithEmptyStatus(): void
    {
        $method = $this->getProtectedMethod('buildListWhere');

        $result = $method->invoke($this->apiService, '', '', null, '', '');

        $this->assertEmpty($result);
    }

    /**
     * 测试查询条件构建 - 菜单 ID 筛选
     */
    public function testBuildListWhereWithMenuId(): void
    {
        $method = $this->getProtectedMethod('buildListWhere');

        $result = $method->invoke($this->apiService, '', null, 5, '', '');

        $this->assertCount(1, $result);
        $this->assertEquals(['menu_id', '=', 5], $result[0]);
    }

    /**
     * 测试查询条件构建 - HTTP 方法筛选（自动转大写）
     */
    public function testBuildListWhereWithMethod(): void
    {
        $method = $this->getProtectedMethod('buildListWhere');

        $result = $method->invoke($this->apiService, '', null, null, 'get', '');

        $this->assertCount(1, $result);
        $this->assertEquals(['method', '=', 'GET'], $result[0]);
    }

    /**
     * 测试查询条件构建 - 分组筛选
     */
    public function testBuildListWhereWithGroup(): void
    {
        $method = $this->getProtectedMethod('buildListWhere');

        $result = $method->invoke($this->apiService, '', null, null, '', '用户管理');

        $this->assertCount(1, $result);
        $this->assertEquals(['group', '=', '用户管理'], $result[0]);
    }

    /**
     * 测试查询条件构建 - 多条件组合
     */
    public function testBuildListWhereWithMultipleParams(): void
    {
        $method = $this->getProtectedMethod('buildListWhere');

        $result = $method->invoke($this->apiService, '用户', 1, 5, 'GET', '用户管理');

        $this->assertCount(5, $result);
        $this->assertEquals(['name|code|path', 'like', '%用户%'], $result[0]);
        $this->assertEquals(['status', '=', 1], $result[1]);
        $this->assertEquals(['menu_id', '=', 5], $result[2]);
        $this->assertEquals(['method', '=', 'GET'], $result[3]);
        $this->assertEquals(['group', '=', '用户管理'], $result[4]);
    }

    /**
     * 测试菜单名称获取 - 空 menu_id
     */
    public function testGetMenuNameWithNullMenuId(): void
    {
        $method = $this->getProtectedMethod('getMenuName');

        $result = $method->invoke($this->apiService, null);
        $this->assertEquals('', $result);
    }

    /**
     * 测试菜单名称获取 - menu_id 为 0
     */
    public function testGetMenuNameWithZeroMenuId(): void
    {
        $method = $this->getProtectedMethod('getMenuName');

        $result = $method->invoke($this->apiService, 0);
        $this->assertEquals('', $result);
    }

    /**
     * 测试菜单名称获取 - 有效 menu_id（需数据库）
     */
    public function testGetMenuNameWithValidMenuId(): void
    {
        $method = $this->getProtectedMethod('getMenuName');

        try {
            $result = $method->invoke($this->apiService, 1);
            $this->assertIsString($result);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过菜单名称获取测试');
            }
            throw $e;
        }
    }

    /**
     * 测试接口标识唯一性校验 - 新建场景（无排除ID）
     */
    public function testValidateCodeUniqueWithoutExclude(): void
    {
        $method = $this->getProtectedMethod('validateCodeUnique');

        try {
            $result = $method->invoke($this->apiService, 'UNIQUE_TEST_API_CODE_99999');

            $this->assertArrayHasKey('valid', $result);
            $this->assertArrayHasKey('error', $result);
            $this->assertIsBool($result['valid']);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过标识唯一性测试');
            }
            throw $e;
        }
    }

    /**
     * 测试接口标识唯一性校验 - 更新场景（排除自身ID）
     */
    public function testValidateCodeUniqueWithExclude(): void
    {
        $method = $this->getProtectedMethod('validateCodeUnique');

        try {
            $result = $method->invoke($this->apiService, 'UNIQUE_TEST_API_CODE_99999', 1);

            $this->assertArrayHasKey('valid', $result);
            $this->assertArrayHasKey('error', $result);
            $this->assertIsBool($result['valid']);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过标识唯一性排除测试');
            }
            throw $e;
        }
    }

    /**
     * 测试方法+路径组合唯一性校验 - 新建场景
     */
    public function testValidateMethodPathUniqueWithoutExclude(): void
    {
        $method = $this->getProtectedMethod('validateMethodPathUnique');

        try {
            $result = $method->invoke($this->apiService, 'GET', '/api/test/unique/check');

            $this->assertArrayHasKey('valid', $result);
            $this->assertArrayHasKey('error', $result);
            $this->assertIsBool($result['valid']);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过方法路径唯一性测试');
            }
            throw $e;
        }
    }

    /**
     * 测试方法+路径组合唯一性校验 - 更新场景（排除自身ID）
     */
    public function testValidateMethodPathUniqueWithExclude(): void
    {
        $method = $this->getProtectedMethod('validateMethodPathUnique');

        try {
            $result = $method->invoke($this->apiService, 'GET', '/api/test/unique/check', 1);

            $this->assertArrayHasKey('valid', $result);
            $this->assertArrayHasKey('error', $result);
            $this->assertIsBool($result['valid']);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过方法路径唯一性排除测试');
            }
            throw $e;
        }
    }

    /**
     * 测试菜单存在性校验（需数据库）
     */
    public function testValidateMenuExists(): void
    {
        $method = $this->getProtectedMethod('validateMenuExists');

        try {
            $result = $method->invoke($this->apiService, 999999);

            $this->assertArrayHasKey('valid', $result);
            $this->assertArrayHasKey('error', $result);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过菜单存在性校验测试');
            }
            throw $e;
        }
    }

    /**
     * 测试 getApiList 返回结构
     */
    public function testGetApiListReturnStructure(): void
    {
        try {
            $result = $this->apiService->getApiList([
                'page' => 1,
                'limit' => 15,
                'keyword' => '',
                'status' => null,
                'menu_id' => null,
                'method' => '',
                'group' => '',
            ]);

            $this->assertArrayHasKey('success', $result);
            $this->assertIsBool($result['success']);

            if ($result['success']) {
                $this->assertArrayHasKey('data', $result);
                $this->assertArrayHasKey('list', $result['data']);
                $this->assertArrayHasKey('groups', $result['data']);
                $this->assertArrayHasKey('pagination', $result['data']);
                $this->assertArrayHasKey('page', $result['data']['pagination']);
                $this->assertArrayHasKey('page_size', $result['data']['pagination']);
                $this->assertArrayHasKey('total', $result['data']['pagination']);
                $this->assertArrayHasKey('total_pages', $result['data']['pagination']);
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
     * 测试 getApiDetail 不存在的接口
     */
    public function testGetApiDetailNotFound(): void
    {
        try {
            $result = $this->apiService->getApiDetail(999999);

            $this->assertFalse($result['success']);
            $this->assertEquals('接口不存在', $result['error']);
            $this->assertEquals(404, $result['code']);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过详情查询测试');
            }
            throw $e;
        }
    }

    /**
     * 测试 deleteApi 非法 ID
     */
    public function testDeleteApiInvalidId(): void
    {
        $result = $this->apiService->deleteApi(0);

        $this->assertFalse($result['success']);
        $this->assertEquals('参数错误', $result['error']);
        $this->assertEquals(422, $result['code']);

        $result = $this->apiService->deleteApi(-1);
        $this->assertFalse($result['success']);
        $this->assertEquals('参数错误', $result['error']);
    }

    /**
     * 测试 deleteApi 不存在的接口
     */
    public function testDeleteApiNotFound(): void
    {
        try {
            $result = $this->apiService->deleteApi(999999);

            $this->assertFalse($result['success']);
            $this->assertEquals('接口不存在', $result['error']);
            $this->assertEquals(404, $result['code']);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过删除接口测试');
            }
            throw $e;
        }
    }

    /**
     * 测试 changeStatus 不存在的接口
     */
    public function testChangeStatusNotFound(): void
    {
        try {
            $result = $this->apiService->changeStatus(999999, 0);

            $this->assertFalse($result['success']);
            $this->assertEquals('接口不存在', $result['error']);
            $this->assertEquals(404, $result['code']);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过状态切换测试');
            }
            throw $e;
        }
    }

    /**
     * 测试 getApisByMenu 不存在的菜单
     */
    public function testGetApisByMenuNotFound(): void
    {
        try {
            $result = $this->apiService->getApisByMenu(999999);

            $this->assertFalse($result['success']);
            $this->assertEquals('菜单不存在', $result['error']);
            $this->assertEquals(404, $result['code']);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过菜单关联接口查询测试');
            }
            throw $e;
        }
    }

    /**
     * 测试 getGroups 返回结构
     */
    public function testGetGroupsReturnStructure(): void
    {
        try {
            $result = $this->apiService->getGroups();

            $this->assertTrue($result['success']);
            $this->assertArrayHasKey('data', $result);
            $this->assertArrayHasKey('groups', $result['data']);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'Undefined db config')) {
                $this->markTestSkipped('数据库配置不可用，跳过分组查询测试');
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
            $service = ApiService::getInstance();
        }
        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, '单例获取100次应在1秒内完成');
    }

    /**
     * 通过反射获取 protected 方法
     */
    private function getProtectedMethod(string $methodName): ReflectionMethod
    {
        $method = new ReflectionMethod(ApiService::class, $methodName);
        $method->setAccessible(true);
        return $method;
    }
}
