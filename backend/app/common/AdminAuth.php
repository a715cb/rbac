<?php
namespace app\common;

use app\model\User;
use app\model\Role;
use app\model\Menu;
use app\model\Department;
use think\facade\Config;
use think\facade\Db;

class AdminAuth
{
    private $userId = 0;
    private ?User $user = null;
    private $roles = [];
    private $permissions = [];
    private $menus = [];
    private $buttonCodes = [];
    private $dataScope = 1;
    private $dataScopeDeptIds = [];

    public static function instance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

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

    public function isSuperAdmin(): bool
    {
        if ($this->userId <= 0) {
            return false;
        }

        $superAdminCode = Config::get('auth.super_admin_code', 'super_admin');
        $userRoleCodes = array_column($this->roles, 'code');
        return in_array($superAdminCode, $userRoleCodes);
    }

    public function getMenuCodes(): array
    {
        if ($this->isSuperAdmin()) {
            return $this->getAllMenuCodes();
        }

        if (!empty($this->menus)) {
            return $this->menus;
        }

        $cacheKey = 'user_menu_codes_' . $this->userId;
        $cacheTime = Config::get('auth.cache_time', 3600);

        $cached = SimpleCache::get($cacheKey);
        if ($cached !== null) {
            $this->menus = $cached;
            return $this->menus;
        }

        $menuModel = new Menu();
        $this->menus = $menuModel->getUserMenuCodes($this->userId);

        SimpleCache::set($cacheKey, $this->menus, $cacheTime);

        return $this->menus;
    }

    public function getApiCodes(): array
    {
        if ($this->isSuperAdmin()) {
            return $this->getAllApiCodes();
        }

        if (!empty($this->permissions)) {
            return $this->permissions;
        }

        $cacheKey = 'user_api_codes_' . $this->userId;
        $cacheTime = Config::get('auth.cache_time', 3600);

        $cached = SimpleCache::get($cacheKey);
        if ($cached !== null) {
            $this->permissions = $cached;
            return $this->permissions;
        }

        $menuModel = new Menu();
        $this->permissions = $menuModel->getUserApiCodes($this->userId);

        SimpleCache::set($cacheKey, $this->permissions, $cacheTime);

        return $this->permissions;
    }

    public function getButtonCodes(): array
    {
        if ($this->isSuperAdmin()) {
            return $this->getAllButtonCodes();
        }

        if (!empty($this->buttonCodes)) {
            return $this->buttonCodes;
        }

        $cacheKey = 'user_button_codes_' . $this->userId;
        $cacheTime = Config::get('auth.cache_time', 3600);

        $cached = SimpleCache::get($cacheKey);
        if ($cached !== null) {
            $this->buttonCodes = $cached;
            return $this->buttonCodes;
        }

        $roleModel = new Role();
        $roleIds = array_column($this->roles, 'id');
        $buttonIds = $roleModel->getRoleButtonsByRoleIds($roleIds);

        if (empty($buttonIds)) {
            $this->buttonCodes = [];
            SimpleCache::set($cacheKey, $this->buttonCodes, $cacheTime);
            return $this->buttonCodes;
        }

        $this->buttonCodes = Db::name('sys_menu_button')
            ->whereIn('id', $buttonIds)
            ->where('status', 1)
            ->whereNull('delete_time')
            ->column('code');

        SimpleCache::set($cacheKey, $this->buttonCodes, $cacheTime);

        return $this->buttonCodes;
    }

    public function getMenuTree(): array
    {
        $cacheKey = 'user_menu_tree_' . $this->userId;
        $cacheTime = Config::get('auth.cache_time', 3600);

        $cached = SimpleCache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $menuModel = new Menu();

        if ($this->isSuperAdmin()) {
            $tree = $menuModel->getMenuTreeList(1);
        } else {
            $tree = $menuModel->getUserMenuTree($this->userId);
        }

        SimpleCache::set($cacheKey, $tree, $cacheTime);

        return $tree;
    }

    public function getDataScope(): int
    {
        return $this->dataScope;
    }

    public function getDataScopeDeptIds(): array
    {
        return $this->dataScopeDeptIds;
    }

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

    public function getUserDeptIds(): array
    {
        $user = $this->getUser();
        if (!$user) {
            return [];
        }

        $deptIds = Db::name('sys_user_dept')
            ->where('user_id', $user->id)
            ->column('dept_id');

        if (empty($deptIds) && $user->dept_id) {
            $deptIds = [$user->dept_id];
        }

        return $deptIds;
    }

    public function isSelfOnly(): bool
    {
        return $this->dataScope === 4;
    }

    public function setUser(int $userId): void
    {
        if ($this->userId !== $userId) {
            $this->resetState();
        }

        $this->userId = $userId;
        $this->loadUserRoles();
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUser(): ?User
    {
        if ($this->user === null && $this->userId > 0) {
            $this->user = User::find($this->userId);
        }
        return $this->user;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

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

    private function getAllMenuCodes(): array
    {
        $cacheKey = 'all_menu_codes';
        $cacheTime = Config::get('auth.cache_time', 3600);

        $cached = SimpleCache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $codes = (new Menu())->where('status', 1)->column('code');

        SimpleCache::set($cacheKey, $codes, $cacheTime);

        return $codes;
    }

    private function getAllApiCodes(): array
    {
        $cacheKey = 'all_api_codes';
        $cacheTime = Config::get('auth.cache_time', 3600);

        $cached = SimpleCache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $codes = Db::name('sys_api')
            ->where('status', 1)
            ->whereNull('delete_time')
            ->column('code');

        SimpleCache::set($cacheKey, $codes, $cacheTime);

        return $codes;
    }

    private function getAllButtonCodes(): array
    {
        $cacheKey = 'all_button_codes';
        $cacheTime = Config::get('auth.cache_time', 3600);

        $cached = SimpleCache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $codes = Db::name('sys_menu_button')
            ->where('status', 1)
            ->whereNull('delete_time')
            ->column('code');

        SimpleCache::set($cacheKey, $codes, $cacheTime);

        return $codes;
    }

    public function clearCache(): void
    {
        if ($this->userId > 0) {
            SimpleCache::delete('user_menu_codes_' . $this->userId);
            SimpleCache::delete('user_api_codes_' . $this->userId);
            SimpleCache::delete('user_button_codes_' . $this->userId);
            SimpleCache::delete('user_menu_tree_' . $this->userId);
        }

        SimpleCache::delete('all_menu_codes');
        SimpleCache::delete('all_api_codes');
        SimpleCache::delete('all_button_codes');
    }

    public function clearAllCache(): void
    {
        if ($this->userId > 0) {
            $this->clearCache();
        }
        $this->userId = 0;
        $this->resetState();
    }
}
