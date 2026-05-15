<?php
namespace app\model;

use app\common\BaseModel;
use think\facade\Db;

class Role extends BaseModel
{
    protected $name = 'role';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $deleteTime = 'delete_time';

    protected $defaultSoftDelete = null;

    protected $hidden = [];

    protected $append = [];

    protected $type = [
        'data_scope' => 'integer',
        'status' => 'integer',
        'sort' => 'integer',
    ];

    public function getUserRoles(int $userId): array
    {
        return Db::name('role')
            ->alias('role')
            ->join('user_role user_role', 'role.id = user_role.role_id', 'INNER')
            ->where('user_role.user_id', $userId)
            ->where('role.status', 1)
            ->whereNull('role.delete_time')
            ->select()
            ->toArray();
    }

    public function getUserRoleIds(int $userId): array
    {
        return Db::name('user_role')
            ->where('user_id', $userId)
            ->column('role_id');
    }

    public function getRoleMenus(int $roleId): array
    {
        return Db::name('role_menu')
            ->where('role_id', $roleId)
            ->column('menu_id');
    }

    public function getRoleMenusByRoleIds(array $roleIds): array
    {
        if (empty($roleIds)) {
            return [];
        }

        return Db::name('role_menu')
            ->whereIn('role_id', $roleIds)
            ->distinct(true)
            ->column('menu_id');
    }

    public function getRoleMenusTree(int $roleId): array
    {
        $menuModel = new Menu();
        $menuIds = $this->getRoleMenus($roleId);

        if (empty($menuIds)) {
            return [];
        }

        $menus = $menuModel->whereIn('id', $menuIds)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        return $menuModel->buildTree($menus);
    }

    public function getRoleButtons(int $roleId): array
    {
        return Db::name('role_menu_button')
            ->where('role_id', $roleId)
            ->column('menu_button_id');
    }

    public function getRoleButtonsByRoleIds(array $roleIds): array
    {
        if (empty($roleIds)) {
            return [];
        }

        return Db::name('role_menu_button')
            ->whereIn('role_id', $roleIds)
            ->distinct(true)
            ->column('menu_button_id');
    }

    public function getRoleMenusWithButtons(int $roleId): array
    {
        $menuIds = $this->getRoleMenus($roleId);
        $buttonIds = $this->getRoleButtons($roleId);

        $menuModel = new Menu();
        $menus = $menuModel->whereIn('id', $menuIds)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        foreach ($menus as &$menu) {
            $menu['buttons'] = Db::name('menu_button')
                ->where('menu_id', $menu['id'])
                ->whereIn('id', $buttonIds)
                ->where('status', 1)
                ->order('sort', 'asc')
                ->select()
                ->toArray();
        }

        return $menus;
    }

    public function getRoleApis(int $roleId): array
    {
        return Db::name('role_api')
            ->where('role_id', $roleId)
            ->column('api_id');
    }

    public function getRoleApisByRoleIds(array $roleIds): array
    {
        if (empty($roleIds)) {
            return [];
        }

        return Db::name('role_api')
            ->whereIn('role_id', $roleIds)
            ->distinct(true)
            ->column('api_id');
    }

    public function assignMenus(int $roleId, array $menuIds): bool
    {
        Db::startTrans();
        try {
            Db::name('role_menu')
                ->where('role_id', $roleId)
                ->delete();

            if (!empty($menuIds)) {
                $insertData = array_map(function ($menuId) use ($roleId) {
                    return [
                        'role_id' => $roleId,
                        'menu_id' => $menuId,
                        'create_time' => date('Y-m-d H:i:s'),
                    ];
                }, $menuIds);

                Db::name('role_menu')->insertAll($insertData);
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public function assignButtons(int $roleId, array $buttonIds): bool
    {
        Db::startTrans();
        try {
            Db::name('role_menu_button')
                ->where('role_id', $roleId)
                ->delete();

            if (!empty($buttonIds)) {
                $insertData = array_map(function ($buttonId) use ($roleId) {
                    return [
                        'role_id' => $roleId,
                        'menu_button_id' => $buttonId,
                        'create_time' => date('Y-m-d H:i:s'),
                    ];
                }, $buttonIds);

                Db::name('role_menu_button')->insertAll($insertData);
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public function assignApis(int $roleId, array $apiIds): bool
    {
        Db::startTrans();
        try {
            Db::name('role_api')
                ->where('role_id', $roleId)
                ->delete();

            if (!empty($apiIds)) {
                $insertData = array_map(function ($apiId) use ($roleId) {
                    return [
                        'role_id' => $roleId,
                        'api_id' => $apiId,
                        'create_time' => date('Y-m-d H:i:s'),
                    ];
                }, $apiIds);

                Db::name('role_api')->insertAll($insertData);
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }
}