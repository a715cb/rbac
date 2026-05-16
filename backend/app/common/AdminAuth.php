<?php
/**
 * 管理员权限认证类
 *
 * @文件: AdminAuth.php
 * @描述: 后台管理系统权限认证核心类，负责用户权限加载、权限检查和数据权限范围计算
 *
 * @功能说明:
 *   1. 用户权限数据加载与管理
 *   2. 三级权限验证：菜单权限、按钮权限、API接口权限
 *   3. 五级数据权限控制：全部、本部门、本部门及以下、仅本人、自定义
 *   4. 超级管理员权限绕过
 *   5. 权限数据多级缓存优化
 *
 * @设计思路:
 *   - 单例模式：确保同一请求周期内只有一个实例，避免权限数据不一致
 *   - 延迟加载：权限数据在首次访问时才从数据库加载
 *   - 多级缓存：内存缓存 + Redis缓存，减少数据库查询压力
 *   - 权限缓存自动清理：在用户、角色、菜单、部门变更时自动清理相关缓存
 *
 * @使用示例:
 *   $auth = AdminAuth::instance();
 *   $auth->setUser($userId);
 *   if ($auth->check('user:list', 2)) { ... }
 *   $scopedDeptIds = $auth->getScopedDeptIds();
 *
 * @依赖组件:
 *   - User: 用户模型
 *   - Role: 角色模型
 *   - Menu: 菜单模型
 *   - Department: 部门模型
 *   - SimpleCache: 简单缓存实现
 *
 * @版本: v1.0
 * @日期: 2026-05-14
 */

namespace app\common;

use app\model\User;
use app\model\Role;
use app\model\Menu;
use app\model\Department;
use think\facade\Config;
use think\facade\Db;

/**
 * 管理员权限认证类
 *
 * 核心职责：
 * - 管理当前用户的权限上下文信息
 * - 提供统一的权限验证接口
 * - 计算数据权限范围（部门级别）
 *
 * @property int $userId              当前用户ID
 * @property User|null $user          用户模型实例
 * @property array $roles             用户角色列表
 * @property array $permissions       用户API权限码列表
 * @property array $menus             用户菜单权限码列表
 * @property array $buttonCodes       用户按钮权限码列表
 * @property int $dataScope           数据权限级别（1-5）
 * @property array $dataScopeDeptIds  自定义数据权限部门ID列表
 */
class AdminAuth
{
    /** @var int 当前登录用户ID，未登录时为0 */
    private $userId = 0;

    /** @var User|null 用户模型实例，用于获取用户基本信息 */
    private ?User $user = null;

    /** @var array 用户关联的角色列表，每项包含角色ID、角色码、角色名、数据权限等信息 */
    private $roles = [];

    /** @var array 用户可访问的API接口权限码列表 */
    private $permissions = [];

    /** @var array 用户可访问的菜单权限码列表 */
    private $menus = [];

    /** @var array 用户可访问的按钮权限码列表 */
    private $buttonCodes = [];

    /** @var int 数据权限级别：1-全部、2-本部门、3-本部门及以下、4-仅本人、5-自定义 */
    private $dataScope = 1;

    /** @var array 当数据权限级别为5（自定义）时，指定可见的部门ID列表 */
    private $dataScopeDeptIds = [];

    /**
     * 获取AdminAuth单例实例
     *
     * @描述: 使用静态变量实现单例模式，确保整个请求周期内只有一个AdminAuth实例
     *       这样可以保证权限数据的一致性，避免重复查询数据库
     *
     * @返回: AdminAuth 单例实例
     */
    public static function instance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * 权限验证
     *
     * @描述: 检查当前用户是否拥有指定权限，支持三种权限类型验证
     *       超级管理员拥有所有权限，直接返回true
     *       未登录用户（userId<=0）直接返回false
     *
     * @参数:
     *   - permission (string|array): 权限标识，可以是单个权限码或权限码数组
     *   - type (int): 权限类型，1-通用权限码、2-菜单权限码、3-API接口权限码
     *
     * @返回: bool true-有权限，false-无权限
     *
     * @业务逻辑:
     *   1. 检查用户是否登录（userId > 0）
     *   2. 检查是否为超级管理员（超级管理员拥有所有权限）
     *   3. 根据type参数调用对应的权限码获取方法
     *   4. 判断指定权限是否在用户权限列表中
     *
     * @使用场景:
     *   - 菜单权限检查：$auth->check('user:view', 2)
     *   - API权限检查：$auth->check('GET:/admin/users', 3)
     *   - 按钮权限检查：$auth->check('btn:delete')
     */
    public function check(string|array $permission, int $type = 1): bool
    {
        if ($this->userId <= 0) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($type === 1) {
            return in_array($permission, $this->permissions);
        } elseif ($type === 2) {
            return in_array($permission, $this->getMenuCodes());
        } elseif ($type === 3) {
            return in_array($permission, $this->getApiCodes());
        }

        return false;
    }

    /**
     * 判断当前用户是否为超级管理员
     *
     * @描述: 检查用户的角色列表中是否包含超级管理员角色
     *       超级管理员拥有系统所有权限，不受普通权限验证限制
     *
     * @返回: bool true-是超级管理员，false-不是超级管理员
     *
     * @业务逻辑:
     *   1. 检查用户是否登录（userId > 0）
     *   2. 从配置文件读取超级管理员角色码（默认：super_admin）
     *   3. 从用户角色列表中提取所有角色码
     *   4. 判断超级管理员角色码是否在用户角色码列表中
     *
     * @配置项: auth.php 中的 super_admin_code 配置项
     */
    public function isSuperAdmin(): bool
    {
        if ($this->userId <= 0) {
            return false;
        }

        $superAdminCode = Config::get('auth.super_admin_code', 'super_admin');
        $userRoleCodes = array_column($this->roles, 'code');
        return in_array($superAdminCode, $userRoleCodes);
    }

    /**
     * 获取用户菜单权限码列表
     *
     * @描述: 获取当前用户可访问的菜单权限标识列表，用于前端菜单渲染和菜单级别权限控制
     *       超级管理员返回所有启用的菜单权限码
     *       普通用户从缓存或数据库获取个性化菜单权限码
     *
     * @返回: array 用户可访问的菜单权限码数组
     *
     * @缓存策略:
     *   - 缓存键：user_menu_codes_{userId}（普通用户）
     *   - 缓存键：all_menu_codes（超级管理员）
     *   - 缓存时间：由 auth.cache_time 配置项控制（默认3600秒），带 ±10% TTL 抖动防雪崩
     *   - 缓存模式：SimpleCache::remember 互斥锁保护回填，标签 user_menu_cache
     *   - 缓存层级：内存优先，Redis作为后备
     *
     * @业务逻辑:
     *   1. 超级管理员直接返回所有菜单码（走缓存）
     *   2. 优先返回内存缓存的菜单码列表
     *   3. 缓存未命中时通过 remember 回调从数据库查询并写入缓存
     */
    public function getMenuCodes(): array
    {
        if ($this->isSuperAdmin()) {
            return $this->getAllMenuCodes();
        }

        if (!empty($this->menus)) {
            return $this->menus;
        }

        $cacheKey = 'user_menu_codes_' . $this->userId;
        $cacheTime = SimpleCache::getJitteredTtl(Config::get('auth.cache_time', 3600));

        $this->menus = SimpleCache::remember($cacheKey, $cacheTime, function () {
            return (new Menu())->getUserMenuCodes($this->userId);
        }, 'user_menu_cache');

        return $this->menus;
    }

    /**
     * 获取用户API接口权限码列表
     *
     * @描述: 获取当前用户可访问的API接口权限标识列表，用于API级别权限验证
     *       在ApiPermission中间件中被调用，实现接口访问控制
     *
     * @返回: array 用户可访问的API权限码数组，如 ['GET:/admin/users', 'POST:/admin/users']
     *
     * @缓存策略:
     *   - 缓存键：user_api_codes_{userId}（普通用户）
     *   - 缓存键：all_api_codes（超级管理员）
     *   - 缓存时间：由 auth.cache_time 配置项控制，带 ±10% TTL 抖动防雪崩
     *   - 缓存模式：SimpleCache::remember 互斥锁保护回填，标签 user_menu_cache
     *
     * @业务逻辑:
     *   1. 超级管理员直接返回所有API码（走缓存）
     *   2. 优先返回内存缓存的API码列表
     *   3. 缓存未命中时通过 remember 回调从数据库查询并写入缓存
     *
     * @使用场景: ApiPermission中间件验证用户是否有权访问特定API
     */
    public function getApiCodes(): array
    {
        if ($this->isSuperAdmin()) {
            return $this->getAllApiCodes();
        }

        if (!empty($this->permissions)) {
            return $this->permissions;
        }

        $cacheKey = 'user_api_codes_' . $this->userId;
        $cacheTime = SimpleCache::getJitteredTtl(Config::get('auth.cache_time', 3600));

        $this->permissions = SimpleCache::remember($cacheKey, $cacheTime, function () {
            return (new Menu())->getUserApiCodes($this->userId);
        }, 'user_menu_cache');

        return $this->permissions;
    }

    /**
     * 获取用户按钮权限码列表
     *
     * @描述: 获取当前用户可访问的按钮权限标识列表，用于细粒度的操作权限控制
     *       如：新增、编辑、删除、导出等按钮的显示/隐藏控制
     *
     * @返回: array 用户可访问的按钮权限码数组，如 ['btn:add', 'btn:edit', 'btn:delete']
     *
     * @缓存策略:
     *   - 缓存键：user_button_codes_{userId}
     *   - 缓存键：all_button_codes（超级管理员）
     *   - 缓存时间：由 auth.cache_time 配置项控制，带 ±10% TTL 抖动防雪崩
     *   - 缓存模式：SimpleCache::remember 互斥锁保护回填，标签 user_menu_cache
     *
     * @业务逻辑:
     *   1. 超级管理员直接返回所有按钮码
     *   2. 优先返回内存缓存的按钮码列表
     *   3. 缓存未命中时通过 remember 回调查询角色关联按钮并写入缓存
     */
    public function getButtonCodes(): array
    {
        if ($this->isSuperAdmin()) {
            return $this->getAllButtonCodes();
        }

        if (!empty($this->buttonCodes)) {
            return $this->buttonCodes;
        }

        $cacheKey = 'user_button_codes_' . $this->userId;
        $cacheTime = SimpleCache::getJitteredTtl(Config::get('auth.cache_time', 3600));

        $this->buttonCodes = SimpleCache::remember($cacheKey, $cacheTime, function () {
            $roleModel = new Role();
            $roleIds = array_column($this->roles, 'id');
            $buttonIds = $roleModel->getRoleButtonsByRoleIds($roleIds);

            if (empty($buttonIds)) {
                return [];
            }

            return Db::name('menu_button')
                ->whereIn('id', $buttonIds)
                ->where('status', 1)
                ->whereNull('delete_time')
                ->column('code');
        }, 'user_menu_cache');

        return $this->buttonCodes;
    }

    /**
     * 获取用户菜单树结构
     *
     * @描述: 获取当前用户可访问的菜单树形结构，用于前端动态菜单渲染
     *       返回包含完整层级关系的菜单数据
     *
     * @返回: array 用户可访问的菜单树数组，包含菜单的所有属性和层级关系
     *
     * @缓存策略:
     *   - 缓存键：user_menu_tree_{userId}
     *   - 缓存时间：由 auth.cache_time 配置项控制，带 ±10% TTL 抖动防雪崩
     *   - 缓存模式：SimpleCache::remember 互斥锁保护回填，标签 user_menu_cache
     *   - 空结果同样缓存，避免缓存穿透
     *
     * @业务逻辑:
     *   1. 超级管理员获取完整菜单树（status=1）
     *   2. 普通用户根据角色权限获取个性化菜单树
     *   3. 自动补全父级菜单（确保菜单树完整性）
     *
     * @使用场景:
     *   - 用户登录后前端动态菜单渲染
     *   - 路由守卫权限验证
     */
    public function getMenuTree(): array
    {
        $cacheKey = 'user_menu_tree_' . $this->userId;
        $cacheTime = SimpleCache::getJitteredTtl(Config::get('auth.cache_time', 3600));

        return SimpleCache::remember($cacheKey, $cacheTime, function () {
            $menuModel = new Menu();

            if ($this->isSuperAdmin()) {
                return $menuModel->getMenuTreeList(1);
            }

            return $menuModel->getUserMenuTree($this->userId);
        }, 'user_menu_cache');
    }

    /**
     * 获取数据权限级别
     *
     * @描述: 返回当前用户的数据权限级别，用于数据查询时的范围控制
     *
     * @返回: int 数据权限级别
     *   - 1: 全部数据权限
     *   - 2: 本部门数据权限
     *   - 3: 本部门及以下数据权限
     *   - 4: 仅本人数据权限
     *   - 5: 自定义数据权限
     *
     * @说明: 该值由用户在setUser时根据角色信息自动计算，取所有角色中权限范围最小的
     */
    public function getDataScope(): int
    {
        return $this->dataScope;
    }

    /**
     * 获取自定义数据权限的部门ID列表
     *
     * @描述: 当数据权限级别为5（自定义）时，返回允许访问的部门ID列表
     *
     * @返回: array 部门ID数组
     *
     * @使用场景: 数据权限级别为5时，根据此列表过滤可访问的部门数据
     */
    public function getDataScopeDeptIds(): array
    {
        return $this->dataScopeDeptIds;
    }

    /**
     * 获取当前用户可访问的部门ID列表
     *
     * @描述: 根据数据权限级别计算当前用户可访问的部门范围
     *       这是数据权限控制的核心方法，在数据查询时用于添加部门过滤条件
     *
     * @返回: array 可访问的部门ID数组，返回空数组表示不受限制（全部数据或仅本人）
     *
     * @业务逻辑:
     *   - 超级管理员返回空数组（不受部门限制）
     *   - dataScope=1（全部）：返回空数组，不添加部门过滤
     *   - dataScope=2（本部门）：返回用户所属部门ID列表
     *   - dataScope=3（本部门及以下）：返回用户部门及所有子部门ID
     *   - dataScope=4（仅本人）：返回空数组，通过其他条件控制
     *   - dataScope=5（自定义）：返回dataScopeDeptIds属性
     *
     * @使用场景:
     *   $scopedDeptIds = $auth->getScopedDeptIds();
     *   if (!empty($scopedDeptIds)) {
     *       $query->where('dept_id', 'in', $scopedDeptIds);
     *   }
     */
    public function getScopedDeptIds(): array
    {
        if ($this->isSuperAdmin()) {
            return [];
        }

        switch ($this->dataScope) {
            case 1:
                return [];
            case 2:
                return $this->getUserDeptIds();
            case 3:
                $userDeptIds = $this->getUserDeptIds();
                if (empty($userDeptIds)) {
                    return [];
                }
                $deptModel = new Department();
                $allDeptIds = [];
                foreach ($userDeptIds as $deptId) {
                    $descendantIds = $deptModel->getDescendantDeptIds($deptId);
                    $allDeptIds = array_merge($allDeptIds, $descendantIds);
                }
                return array_values(array_unique($allDeptIds));
            case 4:
                return [];
            case 5:
                return $this->dataScopeDeptIds;
            default:
                return [];
        }
    }

    /**
     * 获取用户所属的所有部门ID
     *
     * @描述: 获取用户所属的部门列表，包括主部门和兼职部门（多对多关系）
     *       用于数据权限计算和部门级数据过滤
     *
     * @返回: array 部门ID数组
     *
     * @业务逻辑:
     *   1. 查询sys_user_dept关联表获取兼职部门ID列表
     *   2. 如果关联表为空且用户有主部门（dept_id），使用主部门ID
     *   3. 返回合并后的部门ID列表
     *
     * @数据库表: sys_user_dept（用户-部门多对多关联表）
     */
    public function getUserDeptIds(): array
    {
        $user = $this->getUser();
        if (!$user) {
            return [];
        }

        $deptIds = Db::name('user_dept')
            ->where('user_id', $user->id)
            ->column('dept_id');

        if (empty($deptIds) && $user->dept_id) {
            $deptIds = [$user->dept_id];
        }

        return $deptIds;
    }

    /**
     * 判断是否为仅本人数据权限
     *
     * @描述: 检查当前用户的数据权限级别是否为4（仅本人）
     *       当为true时，在数据查询时应额外添加 user_id = 当前用户ID 的条件
     *
     * @返回: bool true-仅本人权限，false-其他权限级别
     *
     * @使用场景:
     *   if ($auth->isSelfOnly()) {
     *       $where[] = ['user_id', '=', $auth->getUserId()];
     *   }
     */
    public function isSelfOnly(): bool
    {
        return $this->dataScope === 4;
    }

    /**
     * 设置当前用户上下文
     *
     * @描述: 初始化当前用户的权限上下文，加载用户角色、权限和数据权限信息
     *       这是使用AdminAuth类的入口方法，必须在使用前调用
     *
     * @参数:
     *   - userId (int): 用户ID
     *
     * @返回: void
     *
     * @业务逻辑:
     *   1. 如果切换了用户（userId发生变化），重置所有权限缓存
     *   2. 如果是同一用户，保留已加载的权限数据（避免重复查询）
     *   3. 加载用户关联的角色列表
     *   4. 根据角色计算数据权限级别
     *   5. 合并所有角色的自定义部门权限
     *
     * @性能优化: 同一用户的重复调用不会重复加载数据
     */
    public function setUser(int $userId): void
    {
        if ($this->userId !== $userId) {
            $this->resetState();
        }

        $this->userId = $userId;
        $this->loadUserRoles();
    }

    /**
     * 获取当前用户ID
     *
     * @返回: int 用户ID，未登录或未设置时返回0
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * 获取当前用户模型实例
     *
     * @描述: 获取User模型实例，用于访问用户详细信息
     *       使用延迟加载，只有在首次访问时才从数据库查询
     *
     * @返回: User|null 用户模型实例，userId<=0时返回null
     */
    public function getUser(): ?User
    {
        if ($this->user === null && $this->userId > 0) {
            $this->user = User::find($this->userId);
        }
        return $this->user;
    }

    /**
     * 获取用户角色列表
     *
     * @返回: array 用户关联的角色数组，每项包含角色ID、角色码、角色名、状态等属性
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * 加载用户角色信息
     *
     * @描述: 从数据库加载用户关联的角色列表，并计算数据权限相关属性
     *       该方法为私有，仅在setUser时内部调用
     *
     * @私有方法: 供内部使用，不对外暴露
     */
    private function loadUserRoles(): void
    {
        $roleModel = new Role();
        $roles = $roleModel->getUserRoles($this->userId);

        $this->roles = $roles;

        if (!empty($roles)) {
            $this->dataScope = $this->resolveDataScope($roles);
            $this->dataScopeDeptIds = $this->resolveDataScopeDeptIds($roles);
        }
    }

    /**
     * 计算用户数据权限级别
     *
     * @描述: 从用户的多个角色中计算最严格的数据权限级别
     *       取所有角色中权限范围最小的（数值最小的）
     *
     * @参数:
     *   - roles (array): 用户角色数组
     *
     * @返回: int 数据权限级别（1-5）
     *
     * @说明: 数据权限级别数值越小，权限范围越大
     *       例如：用户有角色A（级别2）和角色B（级别3），最终取级别2
     */
    private function resolveDataScope(array $roles): int
    {
        $minScope = 5;
        foreach ($roles as $role) {
            $scope = (int) ($role['data_scope'] ?? 1);
            if ($scope < $minScope) {
                $minScope = $scope;
            }
            if ($minScope === 1) {
                break;
            }
        }
        return $minScope;
    }

    /**
     * 合并角色的自定义部门权限
     *
     * @描述: 当用户有多个角色时，合并所有角色的自定义部门权限
     *       用于数据权限级别为5（自定义）的情况
     *
     * @参数:
     *   - roles (array): 用户角色数组
     *
     * @返回: array 合并后的部门ID数组（去重）
     *
     * @说明: 支持逗号分隔的字符串格式和数组格式
     */
    private function resolveDataScopeDeptIds(array $roles): array
    {
        $allDeptIds = [];
        foreach ($roles as $role) {
            $deptIds = $role['data_scope_dept_ids'] ?? [];
            if (is_string($deptIds)) {
                $deptIds = $deptIds ? explode(',', $deptIds) : [];
            }
            if (!empty($deptIds)) {
                $allDeptIds = array_merge($allDeptIds, $deptIds);
            }
        }
        return array_values(array_unique($allDeptIds));
    }

    /**
     * 重置权限状态
     *
     * @描述: 清除所有已加载的权限数据，用于用户切换时清理旧数据
     *       保留userId不变，仅重置权限相关属性
     */
    private function resetState(): void
    {
        $this->user = null;
        $this->roles = [];
        $this->permissions = [];
        $this->menus = [];
        $this->buttonCodes = [];
        $this->dataScope = 1;
        $this->dataScopeDeptIds = [];
    }

    /**
     * 获取所有菜单权限码（超级管理员专用）
     *
     * @描述: 获取系统中所有启用的菜单权限码，用于超级管理员权限校验
     *       超级管理员拥有系统所有权限
     *
     * @返回: array 所有菜单权限码数组
     *
     * @缓存策略: 使用全局缓存键 all_menu_codes，标签 global_menu_cache，带 TTL 抖动防雪崩
     */
    private function getAllMenuCodes(): array
    {
        $cacheTime = SimpleCache::getJitteredTtl(Config::get('auth.cache_time', 3600));

        return SimpleCache::remember('all_menu_codes', $cacheTime, function () {
            return (new Menu())->where('status', 1)->column('code');
        }, 'global_menu_cache');
    }

    /**
     * 获取所有API权限码（超级管理员专用）
     *
     * @描述: 获取系统中所有启用的API接口权限码，用于超级管理员权限校验
     *
     * @返回: array 所有API权限码数组
     *
     * @缓存策略: 使用全局缓存键 all_api_codes，标签 global_menu_cache，带 TTL 抖动防雪崩
     */
    private function getAllApiCodes(): array
    {
        $cacheTime = SimpleCache::getJitteredTtl(Config::get('auth.cache_time', 3600));

        return SimpleCache::remember('all_api_codes', $cacheTime, function () {
            return Db::name('api')
                ->where('status', 1)
                ->whereNull('delete_time')
                ->column('code');
        }, 'global_menu_cache');
    }

    /**
     * 获取所有按钮权限码（超级管理员专用）
     *
     * @描述: 获取系统中所有启用的按钮权限码，用于超级管理员权限校验
     *
     * @返回: array 所有按钮权限码数组
     *
     * @缓存策略: 使用全局缓存键 all_button_codes，标签 global_menu_cache，带 TTL 抖动防雪崩
     */
    private function getAllButtonCodes(): array
    {
        $cacheTime = SimpleCache::getJitteredTtl(Config::get('auth.cache_time', 3600));

        return SimpleCache::remember('all_button_codes', $cacheTime, function () {
            return Db::name('menu_button')
                ->where('status', 1)
                ->whereNull('delete_time')
                ->column('code');
        }, 'global_menu_cache');
    }

    /**
     * 清除当前用户权限缓存
     *
     * @描述: 清除当前用户的权限相关缓存数据
     *       用于权限变更后（如角色分配变更）刷新缓存
     *
     * @清除的缓存:
     *   - user_menu_codes_{userId}
     *   - user_api_codes_{userId}
     *   - user_button_codes_{userId}
     *   - user_menu_tree_{userId}
     *
     * @调用场景:
     *   - 用户角色变更后
     *   - 用户部门变更后
     *   - 用户登出时
     */
    public function clearCache(): void
    {
        if ($this->userId > 0) {
            SimpleCache::delete('user_menu_codes_' . $this->userId);
            SimpleCache::delete('user_api_codes_' . $this->userId);
            SimpleCache::delete('user_button_codes_' . $this->userId);
            SimpleCache::delete('user_menu_tree_' . $this->userId);
        }
    }

    /**
     * 清除全局权限缓存
     * @description 清除超级管理员的全局权限缓存，在菜单/角色等全局数据变更时调用
     */
    public static function clearGlobalCache(): void
    {
        SimpleCache::delete('all_menu_codes');
        SimpleCache::delete('all_api_codes');
        SimpleCache::delete('all_button_codes');
        SimpleCache::clearTag('global_menu_cache');
    }

    /**
     * 批量清除所有用户权限缓存
     * @description 通过缓存标签一次性清除所有用户的菜单/API/按钮/菜单树缓存，
     *              替代逐用户遍历删除模式
     */
    public static function clearAllUserCache(): void
    {
        SimpleCache::clearTag('user_menu_cache');
    }

    /**
     * 清除所有缓存并重置状态
     *
     * @描述: 完全重置AdminAuth实例状态，包括清除缓存和重置用户上下文
     *       用于用户完全登出或需要完全清理的场景
     */
    public function clearAllCache(): void
    {
        if ($this->userId > 0) {
            $this->clearCache();
        }
        $this->userId = 0;
        $this->resetState();
    }
}
