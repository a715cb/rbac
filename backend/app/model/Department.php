<?php
namespace app\model;

use app\common\BaseModel;

class Department extends BaseModel
{
    protected $table = 'sys_department';

    protected $pk = 'id';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $deleteTime = 'delete_time';

    protected $defaultSoftDelete = null;

    protected $hidden = [];

    protected $append = [];

    protected $type = [
        'parent_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    public function getDepartmentTree(?int $status = null, string $keyword = ''): array
    {
        $query = $this->order('sort', 'asc');

        if ($status !== null) {
            $query->where('status', $status);
        }

        if (!empty($keyword)) {
            $keyword = trim($keyword);
            $escapedKeyword = str_replace(['%', '_'], ['\\%', '\\_'], $keyword);
            $query->where('name|code|leader', 'like', "%{$escapedKeyword}%");
        }

        $departments = $query->select()->toArray();

        return $this->buildTree($departments);
    }

    public function buildTree(array $departments, int $parentId = 0): array
    {
        $tree = [];
        foreach ($departments as $dept) {
            if ($dept['parent_id'] == $parentId) {
                $children = $this->buildTree($departments, $dept['id']);
                if (!empty($children)) {
                    $dept['children'] = $children;
                }
                $tree[] = $dept;
            }
        }
        return $tree;
    }

    public function getDepartmentPath(int $deptId): array
    {
        $path = [];
        $current = $this->find($deptId);

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

    public function getChildrenIds(int $deptId): array
    {
        $ids = [];
        $children = $this->where('parent_id', $deptId)->select();

        foreach ($children as $child) {
            $ids[] = $child['id'];
            $childIds = $this->getChildrenIds($child['id']);
            $ids = array_merge($ids, $childIds);
        }

        return $ids;
    }

    public function userDepts()
    {
        return $this->hasMany(UserDept::class, 'dept_id', 'id');
    }

    public function getDepartmentUsers(int $deptId): array
    {
        return (new User())->where('dept_id', $deptId)->select()->toArray();
    }

    public function getDescendantDeptIds(int $deptId): array
    {
        $ids = [$deptId];
        $children = $this->where('parent_id', $deptId)->select();

        foreach ($children as $child) {
            $childIds = $this->getDescendantDeptIds($child['id']);
            $ids = array_merge($ids, $childIds);
        }

        return $ids;
    }
}