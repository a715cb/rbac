<?php
namespace app\admin\controller;

use app\model\Api as ApiModel;
use app\model\Menu;
use app\admin\validate\ApiValidate;
use think\Request;

class ApiController extends BaseController
{
    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 15);
        $keyword = $request->get('keyword', '');
        $status = $request->get('status');
        $menuId = $request->get('menu_id');
        $method = $request->get('method', '');
        $group = $request->get('group', '');

        $where = [];

        if (!empty($keyword)) {
            $where[] = ['name|code|path', 'like', "%{$keyword}%"];
        }

        if ($status !== null && $status !== '') {
            $where[] = ['status', '=', (int) $status];
        }

        if ($menuId !== null && $menuId !== '') {
            $where[] = ['menu_id', '=', (int) $menuId];
        }

        if (!empty($method)) {
            $where[] = ['method', '=', strtoupper($method)];
        }

        if (!empty($group)) {
            $where[] = ['group', '=', $group];
        }

        $total = ApiModel::where($where)->count();
        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;

        $list = ApiModel::where($where)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            if (!empty($item['menu_id'])) {
                $menu = Menu::find($item['menu_id']);
                $item['menu_name'] = $menu ? $menu->name : '';
            } else {
                $item['menu_name'] = '';
            }
        }

        $groups = (new ApiModel())->getAllGroups();

        return $this->success([
            'list' => $list,
            'groups' => $groups,
            'pagination' => [
                'page' => $page,
                'page_size' => $limit,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ], '获取成功');
    }

    public function show(int $id)
    {
        $api = ApiModel::find($id);
        if (!$api) {
            return $this->error('接口不存在', 404);
        }

        $apiData = $api->toArray();

        if (!empty($apiData['menu_id'])) {
            $menu = Menu::find($apiData['menu_id']);
            $apiData['menu_name'] = $menu ? $menu->name : '';
        } else {
            $apiData['menu_name'] = '';
        }

        return $this->success($apiData, '获取成功');
    }

    public function store(Request $request)
    {
        $data = $request->post();

        try {
            $validate = new ApiValidate();
            $validate->scene('store')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (ApiModel::where('code', $data['code'])->find()) {
            return $this->error('接口标识已存在', 422);
        }

        if (ApiModel::where('method', strtoupper($data['method']))
            ->where('path', $data['path'])
            ->find()) {
            return $this->error('该接口路径已存在', 422);
        }

        if (!empty($data['menu_id'])) {
            $menu = Menu::find($data['menu_id']);
            if (!$menu) {
                return $this->error('所属菜单不存在', 422);
            }
        }

        try {
            $api = new ApiModel();
            $api->menu_id = $data['menu_id'] ?? null;
            $api->name = $data['name'];
            $api->code = $data['code'];
            $api->method = strtoupper($data['method']);
            $api->path = $data['path'];
            $api->group = $data['group'] ?? '';
            $api->status = $data['status'] ?? 1;
            $api->created_by = $request->userInfo['id'] ?? null;
            $api->save();

            return $this->success(['id' => $api->id], '创建成功');
        } catch (\Exception $e) {
            return $this->error('创建接口失败：' . $e->getMessage());
        }
    }

    public function update(Request $request, int $id)
    {
        $api = ApiModel::find($id);
        if (!$api) {
            return $this->error('接口不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new ApiValidate();
            $validate->scene('update')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (ApiModel::where('code', $data['code'])->where('id', '<>', $id)->find()) {
            return $this->error('接口标识已存在', 422);
        }

        if (ApiModel::where('method', strtoupper($data['method']))
            ->where('path', $data['path'])
            ->where('id', '<>', $id)
            ->find()) {
            return $this->error('该接口路径已存在', 422);
        }

        if (!empty($data['menu_id'])) {
            $menu = Menu::find($data['menu_id']);
            if (!$menu) {
                return $this->error('所属菜单不存在', 422);
            }
        }

        try {
            if (isset($data['menu_id'])) $api->menu_id = $data['menu_id'];
            if (isset($data['name'])) $api->name = $data['name'];
            if (isset($data['code'])) $api->code = $data['code'];
            if (isset($data['method'])) $api->method = strtoupper($data['method']);
            if (isset($data['path'])) $api->path = $data['path'];
            if (isset($data['group'])) $api->group = $data['group'];
            if (isset($data['status'])) $api->status = (int) $data['status'];

            $api->updated_by = $request->userInfo['id'] ?? null;
            $api->save();

            return $this->success([], '更新成功');
        } catch (\Exception $e) {
            return $this->error('更新接口失败：' . $e->getMessage());
        }
    }

    public function destroy(Request $request, int $id)
    {
        if ($id <= 0) {
            return $this->error('参数错误', 422);
        }

        $api = ApiModel::find($id);
        if (!$api) {
            return $this->error('接口不存在', 404);
        }

        try {
            ApiModel::destroy($id);
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->error('删除接口失败：' . $e->getMessage());
        }
    }

    public function setStatus(Request $request, int $id)
    {
        $api = ApiModel::find($id);
        if (!$api) {
            return $this->error('接口不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new ApiValidate();
            $validate->scene('setStatus')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $api->status = (int) $data['status'];
        $api->save();

        return $this->success([], $data['status'] == 1 ? '接口已启用' : '接口已禁用');
    }

    public function getGroups()
    {
        $groups = (new ApiModel())->getAllGroups();

        return $this->success([
            'groups' => $groups,
        ], '获取成功');
    }

    public function getByMenu(int $menuId)
    {
        $menu = Menu::find($menuId);
        if (!$menu) {
            return $this->error('菜单不存在', 404);
        }

        $apis = (new ApiModel())->getApiListByMenu($menuId);

        return $this->success([
            'list' => $apis,
        ], '获取成功');
    }
}