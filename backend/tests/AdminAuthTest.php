<?php
declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use app\common\AdminAuth;
use ReflectionMethod;
use ReflectionProperty;

class AdminAuthTest extends TestCase
{
    private AdminAuth $auth;

    protected function setUp(): void
    {
        $this->auth = AdminAuth::instance();
        $this->resetAuthState();
    }

    protected function tearDown(): void
    {
        $this->resetAuthState();
    }

    private function invokeMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionMethod($object, $methodName);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs($object, $parameters);
    }

    private function setPrivateProperty(object $object, string $propertyName, $value, ?string $declaringClass = null): void
    {
        $class = $declaringClass ?? $object;
        $reflection = new ReflectionProperty($class, $propertyName);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }

    private function getPrivateProperty(object $object, string $propertyName)
    {
        $reflection = new ReflectionProperty($object, $propertyName);
        $reflection->setAccessible(true);
        return $reflection->getValue($object);
    }

    private function resetAuthState(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 0);
        $this->setPrivateProperty($this->auth, 'user', null);
        $this->setPrivateProperty($this->auth, 'roles', []);
        $this->setPrivateProperty($this->auth, 'permissions', []);
        $this->setPrivateProperty($this->auth, 'menus', []);
        $this->setPrivateProperty($this->auth, 'buttonCodes', []);
        $this->setPrivateProperty($this->auth, 'dataScope', 1);
        $this->setPrivateProperty($this->auth, 'dataScopeDeptIds', []);
    }

    public function testSetUserResetsStateWhenUserChanges(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 1);
        $this->setPrivateProperty($this->auth, 'menus', ['menu1', 'menu2']);
        $this->setPrivateProperty($this->auth, 'permissions', ['api1', 'api2']);
        $this->setPrivateProperty($this->auth, 'buttonCodes', ['btn1']);
        $this->setPrivateProperty($this->auth, 'dataScope', 3);
        $this->setPrivateProperty($this->auth, 'dataScopeDeptIds', [10, 20]);

        $this->setPrivateProperty($this->auth, 'roles', [
            ['id' => 2, 'code' => 'admin', 'name' => '普通管理员', 'data_scope' => 2]
        ]);

        $this->auth->setUser(2);

        $menus = $this->getPrivateProperty($this->auth, 'menus');
        $permissions = $this->getPrivateProperty($this->auth, 'permissions');
        $buttonCodes = $this->getPrivateProperty($this->auth, 'buttonCodes');

        $this->assertEmpty($menus, '切换用户后 menus 应被重置');
        $this->assertEmpty($permissions, '切换用户后 permissions 应被重置');
        $this->assertEmpty($buttonCodes, '切换用户后 buttonCodes 应被重置');
    }

    public function testSetUserDoesNotResetStateWhenSameUser(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 1);
        $this->setPrivateProperty($this->auth, 'menus', ['menu1', 'menu2']);

        $this->setPrivateProperty($this->auth, 'roles', [
            ['id' => 1, 'code' => 'admin', 'name' => '管理员', 'data_scope' => 1]
        ]);

        $this->auth->setUser(1);

        $menus = $this->getPrivateProperty($this->auth, 'menus');
        $this->assertEquals(['menu1', 'menu2'], $menus, '同一用户重复 setUser 不应重置 menus');
    }

    public function testIsSuperAdminReturnsFalseForZeroUserId(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 0);
        $this->setPrivateProperty($this->auth, 'roles', []);

        $this->assertFalse($this->auth->isSuperAdmin());
    }

    public function testIsSuperAdminReturnsTrueWhenRoleMatches(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 1);
        $this->setPrivateProperty($this->auth, 'roles', [
            ['id' => 1, 'code' => 'super_admin', 'name' => '超级管理员']
        ]);

        $this->assertTrue($this->auth->isSuperAdmin());
    }

    public function testIsSuperAdminReturnsFalseForNormalUser(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 2);
        $this->setPrivateProperty($this->auth, 'roles', [
            ['id' => 2, 'code' => 'admin', 'name' => '普通管理员']
        ]);

        $this->assertFalse($this->auth->isSuperAdmin());
    }

    public function testCheckReturnsFalseForZeroUserId(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 0);
        $this->setPrivateProperty($this->auth, 'roles', []);

        $this->assertFalse($this->auth->check('any_permission'));
    }

    public function testCheckReturnsTrueForSuperAdmin(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 1);
        $this->setPrivateProperty($this->auth, 'roles', [
            ['id' => 1, 'code' => 'super_admin', 'name' => '超级管理员']
        ]);

        $this->assertTrue($this->auth->check('any_permission'));
        $this->assertTrue($this->auth->check('nonexistent_permission'));
    }

    public function testCheckReturnsFalseForNormalUserWithoutPermission(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 2);
        $this->setPrivateProperty($this->auth, 'roles', [
            ['id' => 2, 'code' => 'admin', 'name' => '普通管理员']
        ]);
        $this->setPrivateProperty($this->auth, 'permissions', ['system_user:list']);

        $this->assertFalse($this->auth->check('system_role:delete'));
    }

    public function testCheckReturnsTrueForNormalUserWithPermission(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 2);
        $this->setPrivateProperty($this->auth, 'roles', [
            ['id' => 2, 'code' => 'admin', 'name' => '普通管理员']
        ]);
        $this->setPrivateProperty($this->auth, 'permissions', ['system_user:list', 'system_role:edit']);

        $this->assertTrue($this->auth->check('system_user:list'));
        $this->assertTrue($this->auth->check('system_role:edit'));
    }

    public function testResolveDataScopeTakesMinimumScope(): void
    {
        $roles = [
            ['id' => 1, 'code' => 'role_a', 'data_scope' => 3],
            ['id' => 2, 'code' => 'role_b', 'data_scope' => 1],
            ['id' => 3, 'code' => 'role_c', 'data_scope' => 5],
        ];

        $result = $this->invokeMethod($this->auth, 'resolveDataScope', [$roles]);

        $this->assertEquals(1, $result, '多角色时应取最小的 dataScope 值（最大权限范围）');
    }

    public function testResolveDataScopeWithSingleRole(): void
    {
        $roles = [
            ['id' => 1, 'code' => 'role_a', 'data_scope' => 4],
        ];

        $result = $this->invokeMethod($this->auth, 'resolveDataScope', [$roles]);

        $this->assertEquals(4, $result);
    }

    public function testResolveDataScopeDefaultsToOne(): void
    {
        $roles = [
            ['id' => 1, 'code' => 'role_a'],
        ];

        $result = $this->invokeMethod($this->auth, 'resolveDataScope', [$roles]);

        $this->assertEquals(1, $result, '缺少 data_scope 字段时默认为 1（全部数据）');
    }

    public function testResolveDataScopeDeptIdsMergesAllRoles(): void
    {
        $roles = [
            ['id' => 1, 'code' => 'role_a', 'data_scope_dept_ids' => '10,20'],
            ['id' => 2, 'code' => 'role_b', 'data_scope_dept_ids' => [30, 40]],
            ['id' => 3, 'code' => 'role_c', 'data_scope_dept_ids' => '20,50'],
        ];

        $result = $this->invokeMethod($this->auth, 'resolveDataScopeDeptIds', [$roles]);

        sort($result);
        $this->assertEquals([10, 20, 30, 40, 50], $result, '多角色的自定义部门ID应合并去重');
    }

    public function testResolveDataScopeDeptIdsHandlesEmptyString(): void
    {
        $roles = [
            ['id' => 1, 'code' => 'role_a', 'data_scope_dept_ids' => ''],
        ];

        $result = $this->invokeMethod($this->auth, 'resolveDataScopeDeptIds', [$roles]);

        $this->assertEmpty($result, '空字符串的自定义部门ID应返回空数组');
    }

    public function testGetScopedDeptIdsReturnsEmptyForSuperAdmin(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 1);
        $this->setPrivateProperty($this->auth, 'roles', [
            ['id' => 1, 'code' => 'super_admin', 'name' => '超级管理员']
        ]);
        $this->setPrivateProperty($this->auth, 'dataScope', 1);

        $result = $this->auth->getScopedDeptIds();

        $this->assertEquals([], $result, '超级管理员应返回空数组（代表全部数据）');
    }

    public function testGetScopedDeptIdsReturnsEmptyForDataScope1(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 2);
        $this->setPrivateProperty($this->auth, 'roles', [
            ['id' => 2, 'code' => 'admin', 'name' => '管理员']
        ]);
        $this->setPrivateProperty($this->auth, 'dataScope', 1);

        $result = $this->auth->getScopedDeptIds();

        $this->assertEquals([], $result, 'dataScope=1（全部数据）应返回空数组');
    }

    public function testGetScopedDeptIdsReturnsEmptyForDataScope4(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 2);
        $this->setPrivateProperty($this->auth, 'roles', [
            ['id' => 2, 'code' => 'admin', 'name' => '管理员']
        ]);
        $this->setPrivateProperty($this->auth, 'dataScope', 4);

        $result = $this->auth->getScopedDeptIds();

        $this->assertEquals([], $result, 'dataScope=4（仅本人）应返回空数组');
    }

    public function testGetScopedDeptIdsReturnsCustomDeptIdsForDataScope5(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 2);
        $this->setPrivateProperty($this->auth, 'roles', [
            ['id' => 2, 'code' => 'admin', 'name' => '管理员']
        ]);
        $this->setPrivateProperty($this->auth, 'dataScope', 5);
        $this->setPrivateProperty($this->auth, 'dataScopeDeptIds', [10, 20, 30]);

        $result = $this->auth->getScopedDeptIds();

        $this->assertEquals([10, 20, 30], $result, 'dataScope=5（自定义）应返回自定义部门ID');
    }

    public function testIsSelfOnlyReturnsTrueForDataScope4(): void
    {
        $this->setPrivateProperty($this->auth, 'dataScope', 4);

        $this->assertTrue($this->auth->isSelfOnly());
    }

    public function testIsSelfOnlyReturnsFalseForOtherDataScope(): void
    {
        $this->setPrivateProperty($this->auth, 'dataScope', 1);

        $this->assertFalse($this->auth->isSelfOnly());
    }

    public function testStatePollutionBetweenDifferentUsers(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 1);
        $this->setPrivateProperty($this->auth, 'roles', [
            ['id' => 1, 'code' => 'super_admin', 'name' => '超级管理员']
        ]);
        $this->setPrivateProperty($this->auth, 'menus', ['system_user', 'system_role', 'system_menu']);
        $this->setPrivateProperty($this->auth, 'permissions', ['api_user_list', 'api_role_list']);
        $this->setPrivateProperty($this->auth, 'buttonCodes', ['system_user:add', 'system_user:edit']);

        $this->setPrivateProperty($this->auth, 'roles', [
            ['id' => 2, 'code' => 'admin', 'name' => '普通管理员', 'data_scope' => 2]
        ]);

        $this->auth->setUser(2);

        $menus = $this->getPrivateProperty($this->auth, 'menus');
        $permissions = $this->getPrivateProperty($this->auth, 'permissions');
        $buttonCodes = $this->getPrivateProperty($this->auth, 'buttonCodes');

        $this->assertEmpty($menus, '用户2不应继承用户1的菜单权限码');
        $this->assertEmpty($permissions, '用户2不应继承用户1的API权限码');
        $this->assertEmpty($buttonCodes, '用户2不应继承用户1的按钮权限码');
    }

    /** 多部门用户 data_scope=2：应返回所有关联部门ID */
    public function testGetScopedDeptIdsReturnsAllUserDeptIdsForDataScope2(): void
    {
        $auth = new class extends AdminAuth {
            public function isSuperAdmin(): bool { return false; }
            public function getUserDeptIds(): array { return [10, 20, 30]; }
        };
        $this->setPrivateProperty($auth, 'dataScope', 2, AdminAuth::class);

        $result = $auth->getScopedDeptIds();

        $this->assertEquals([10, 20, 30], $result, '多部门用户 data_scope=2 应返回所有关联部门ID');
    }

    /** 单部门用户 data_scope=2：返回单个部门ID */
    public function testGetScopedDeptIdsReturnsSingleDeptIdForDataScope2(): void
    {
        $auth = new class extends AdminAuth {
            public function isSuperAdmin(): bool { return false; }
            public function getUserDeptIds(): array { return [5]; }
        };
        $this->setPrivateProperty($auth, 'dataScope', 2, AdminAuth::class);

        $result = $auth->getScopedDeptIds();

        $this->assertEquals([5], $result, '单部门用户 data_scope=2 应返回该部门ID');
    }

    /** 无部门用户 data_scope=2：用户无任何部门关联时返回空数组 */
    public function testGetScopedDeptIdsReturnsEmptyWhenNoDeptsForDataScope2(): void
    {
        $auth = new class extends AdminAuth {
            public function isSuperAdmin(): bool { return false; }
            public function getUserDeptIds(): array { return []; }
        };
        $this->setPrivateProperty($auth, 'dataScope', 2, AdminAuth::class);

        $result = $auth->getScopedDeptIds();

        $this->assertEquals([], $result, '无部门用户 data_scope=2 应返回空数组');
    }

    /** data_scope=3 多部门递归：返回所有关联部门及其子部门（去重） */
    public function testGetScopedDeptIdsReturnsDescendantDeptsForDataScope3(): void
    {
        $auth = new class extends AdminAuth {
            public function isSuperAdmin(): bool { return false; }
            public function getUserDeptIds(): array { return [10, 20]; }
        };
        $this->setPrivateProperty($auth, 'dataScope', 3, AdminAuth::class);

        $result = $auth->getScopedDeptIds();

        $this->assertContains(10, $result, '应包含部门10');
        $this->assertContains(20, $result, '应包含部门20');
        $this->assertNotEmpty($result, '递归查询应返回子部门');
    }

    /** 默认 data_scope 行为：未匹配任何 case 时返回空数组 */
    public function testGetScopedDeptIdsReturnsEmptyForDefaultCase(): void
    {
        $auth = new class extends AdminAuth {
            public function isSuperAdmin(): bool { return false; }
        };
        $this->setPrivateProperty($auth, 'dataScope', 99, AdminAuth::class);

        $result = $auth->getScopedDeptIds();

        $this->assertEquals([], $result, '未知 data_scope 应返回空数组');
    }

    /** getUserDeptIds 主部门冗余回退：sys_user_dept 为空时回退到 user.dept_id */
    public function testGetUserDeptIdsFallsBackToUserDeptIdWhenNoUserDeptRecords(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 100);
        $user = new \app\model\User();
        $user->id = 100;
        $user->dept_id = 5;

        $this->setPrivateProperty($this->auth, 'user', $user);

        $result = $this->auth->getUserDeptIds();

        $this->assertEquals([5], $result, 'sys_user_dept 无记录时应回退到 user.dept_id');
    }

    /** getUserDeptIds 用户不存在时返回空数组 */
    public function testGetUserDeptIdsReturnsEmptyWhenNoUser(): void
    {
        $this->setPrivateProperty($this->auth, 'userId', 0);
        $this->setPrivateProperty($this->auth, 'user', null);

        $result = $this->auth->getUserDeptIds();

        $this->assertEquals([], $result, '无有效用户时应返回空数组');
    }
}
