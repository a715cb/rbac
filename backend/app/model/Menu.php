<?php
namespace app\model;

use app\common\BaseModel;
use think\facade\Db;

class Menu extends BaseModel
{
    protected $name = 'menu';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $deleteTime = 'delete_time';

    protected $defaultSoftDelete = null;

    protected $hidden = [];

    protected $append = [];

    protected $type = [
        'menu_type' => 'integer',
        'parent_id' => 'integer',
        'sort' => 'integer',
        'visible' => 'integer',
        'status' => 'integer',
        'keep_alive' => 'integer',
        'always_show' => 'integer',
        'breadcrumb' => 'integer',
        'is_external' => 'integer',
        'is_frame' => 'integer',
    ];

    public function getUserMenuCodes(int $userId): array
    {
        $menuIds = $this->getUserMenuIds($userId);
        if (empty($menuIds)) {
            return [];
        }

        return $this->whereIn('id', $menuIds)
            ->where('status', 1)
            ->column('code');
    }

    public function getUserApiCodes(int $userId): array
    {
        $roleModel = new Role();
        $roleIds = $roleModel->getUserRoleIds($userId);

        if (empty($roleIds)) {
            return [];
        }

        return Db::name('api')
            ->alias('api')
            ->join('role_api role_api', 'api.id = role_api.api_id', 'LEFT')
            ->whereIn('role_api.role_id', $roleIds)
            ->where('api.status', 1)
            ->whereNull('api.delete_time')
            ->distinct(true)
            ->column('api.code');
    }

    public function getUserMenuTree(int $userId): array
    {
        $menuIds = $this->getUserMenuIds($userId);

        if (empty($menuIds)) {
            return [];
        }

        $menuIds = $this->completeParentMenuIds($menuIds);

        $menus = $this->whereIn('id', $menuIds)
            ->where('status', 1)
            ->where('visible', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        return $this->buildTree($menus);
    }

    protected function completeParentMenuIds(array $menuIds): array
    {
        $allIds = array_flip($menuIds);

        $menus = $this->whereIn('id', $menuIds)
            ->where('status', 1)
            ->column('id, parent_id');

        foreach ($menus as $menu) {
            $parentId = (int) $menu['parent_id'];
            while ($parentId > 0 && !isset($allIds[$parentId])) {
                $allIds[$parentId] = true;
                $parent = $this->where('id', $parentId)
                    ->where('status', 1)
                    ->field('id, parent_id')
                    ->find();
                if (!$parent) {
                    break;
                }
                $parentId = (int) $parent['parent_id'];
            }
        }

        return array_keys($allIds);
    }

    public function getUserMenuIds(int $userId): array
    {
        $roleModel = new Role();
        $roleIds = $roleModel->getUserRoleIds($userId);

        if (empty($roleIds)) {
            return [];
        }

        return Db::name('role_menu')
            ->whereIn('role_id', $roleIds)
            ->distinct(true)
            ->column('menu_id');
    }

    public function buildTree(array $menus, int $parentId = 0): array
    {
        $tree = [];
        foreach ($menus as $menu) {
            if ($menu['parent_id'] == $parentId) {
                $children = $this->buildTree($menus, $menu['id']);
                if (!empty($children)) {
                    $menu['children'] = $children;
                }
                $tree[] = $menu;
            }
        }
        return $tree;
    }

    public function getMenuTreeList(?int $status = null): array
    {
        $query = $this->order('sort', 'asc');

        if ($status !== null) {
            $query->where('status', $status);
        }

        $menus = $query->select()->toArray();

        return $this->buildTree($menus);
    }

    public function getMenuPath(int $menuId): array
    {
        $path = [];
        $current = $this->find($menuId);

        while ($current) {
            array_unshift($path, $current);
            if ($current['parent_id'] > 0) {
                $current = $this->find($current['parent_id']);
            } else {
                break;
            }
        }

        return $path;
    }

    public function getChildrenIds(int $menuId): array
    {
        $ids = [];
        $children = $this->where('parent_id', $menuId)->select();

        foreach ($children as $child) {
            $ids[] = $child['id'];
            $childIds = $this->getChildrenIds($child['id']);
            $ids = array_merge($ids, $childIds);
        }

        return $ids;
    }

    public function getMenuButtons(int $menuId): array
    {
        return Db::name('menu_button')
            ->where('menu_id', $menuId)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }
}