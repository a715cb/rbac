<?php
// +----------------------------------------------------------------------
// | 接口模型
// +----------------------------------------------------------------------
namespace app\model;

use app\common\BaseModel;
use think\facade\Db;

class Api extends BaseModel
{
    protected $name = 'api';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $deleteTime = 'delete_time';

    protected $defaultSoftDelete = null;

    protected $hidden = [];

    protected $append = [];

    protected $type = [
        'menu_id' => 'integer',
        'status' => 'integer',
    ];

    public function getApiListByMenu(int $menuId): array
    {
        return $this->where('menu_id', $menuId)
            ->where('status', 1)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    public function getApisByGroup(string $group): array
    {
        return $this->where('group', $group)
            ->where('status', 1)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    public function getAllGroups(): array
    {
        return $this->whereNotNull('group')
            ->where('status', 1)
            ->distinct(true)
            ->column('group');
    }

    public function checkApiAccess(int $userId, string $code): bool
    {
        $userApiCodes = (new Menu())->getUserApiCodes($userId);
        return in_array($code, $userApiCodes);
    }

    public function matchApiByPath(string $method, string $path): ?array
    {
        $method = strtoupper($method);

        $api = $this->where('method', $method)
            ->where('path', $path)
            ->where('status', 1)
            ->find();

        if ($api) {
            return $api->toArray();
        }

        $apis = $this->where('method', $method)
            ->where('status', 1)
            ->select();

        $pathsToMatch = [$path];
        if (strpos($path, '/api') !== 0) {
            $pathsToMatch[] = '/api' . $path;
        }

        foreach ($apis as $api) {
            $regex = '#^' . preg_replace('#:[^/]+#', '[^/]+', $api->path) . '$#';

            foreach ($pathsToMatch as $tryPath) {
                if (preg_match($regex, $tryPath)) {
                    return $api->toArray();
                }
            }
        }

        return null;
    }

    public function getRoleApis(int $roleId): array
    {
        return Db::name('role_api')
            ->alias('role_api')
            ->join('api api', 'api.id = role_api.api_id', 'INNER')
            ->where('role_api.role_id', $roleId)
            ->where('api.status', 1)
            ->whereNull('api.delete_time')
            ->select()
            ->toArray();
    }
}