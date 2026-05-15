<?php
namespace app\admin\controller;

use app\common\BaseController;
use app\model\Role as RoleModel;
use app\common\AdminAuth;
use app\admin\validate\RoleValidate;
use think\Request;
use think\facade\Db;

class RoleController extends BaseController
{
    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 15);
        $keyword = $request->get('keyword', '');
        $status = $request->get('status');

        $where = [];

        if (!empty($keyword)) {
            $where[] = ['name|code', 'like', "%{$keyword}%"];
        }

        if ($status !== null && $status !== '') {
            $where[] = ['status', '=', (int) $status];
        }

        $total = RoleModel::where($where)->count();
        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;

        $list = RoleModel::where($where)
            ->order('sort', 'asc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $userCount = Db::name('user_role')->where('role_id', $item['id'])->count();
            $item['user_count'] = $userCount;
        }

        return $this->success([
            'list' => $list,
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
        $role = RoleModel::find($id);
        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        $roleData = $role->toArray();
        $roleData['menu_ids'] = (new RoleModel())->getRoleMenus($id);
        $roleData['button_ids'] = (new RoleModel())->getRoleButtons($id);
        $roleData['api_ids'] = (new RoleModel())->getRoleApis($id);
        $roleData['user_count'] = Db::name('user_role')->where('role_id', $id)->count();

        return $this->success($roleData, '获取成功');
    }

    public function store(Request $request)
    {
        $data = $request->post();

        try {
            $validate = new RoleValidate();
            $validate->scene('store')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (RoleModel::where('code', $data['code'])->whereNull('delete_time')->find()) {
            return $this->error('角色标识已存在', 422);
        }

        Db::startTrans();
        try {
            $role = new RoleModel();
            $role->name = $data['name'];
            $role->code = $data['code'];
            $role->data_scope = (int) ($data['data_scope'] ?? 1);
            $role->data_scope_dept_ids = $data['data_scope_dept_ids'] ?? '';
            $role->sort = (int) ($data['sort'] ?? 0);
            $role->status = (int) ($data['status'] ?? 1);
            $role->remark = $data['remark'] ?? '';
            $role->created_by = $request->userInfo['id'] ?? null;
            $role->save();

            if (!empty($data['menu_ids'])) {
                $role->assignMenus($role->id, $data['menu_ids']);
            }
            if (!empty($data['button_ids'])) {
                $role->assignButtons($role->id, $data['button_ids']);
            }
            if (!empty($data['api_ids'])) {
                $role->assignApis($role->id, $data['api_ids']);
            }

            Db::commit();
            return $this->success(['id' => $role->id], '创建成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('创建角色失败：' . $e->getMessage());
        }
    }

    public function update(Request $request, int $id)
    {
        $role = RoleModel::find($id);
        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new RoleValidate();
            $validate->scene('update')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        Db::startTrans();
        try {
            if (isset($data['name'])) $role->name = $data['name'];
            if (isset($data['sort'])) $role->sort = (int) $data['sort'];
            if (isset($data['status'])) $role->status = (int) $data['status'];
            if (isset($data['data_scope'])) $role->data_scope = (int) $data['data_scope'];
            if (isset($data['data_scope_dept_ids'])) $role->data_scope_dept_ids = $data['data_scope_dept_ids'];
            if (isset($data['remark'])) $role->remark = $data['remark'];

            $role->updated_by = $request->userInfo['id'] ?? null;
            $role->save();

            if (isset($data['menu_ids'])) {
                $role->assignMenus($id, $data['menu_ids']);
            }
            if (isset($data['button_ids'])) {
                $role->assignButtons($id, $data['button_ids']);
            }
            if (isset($data['api_ids'])) {
                $role->assignApis($id, $data['api_ids']);
            }

            Db::commit();
            $this->clearRoleCache($id);
            return $this->success([], '更新成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('更新角色失败：' . $e->getMessage());
        }
    }

    public function destroy(int $id)
    {
        if ($id <= 0) {
            return $this->error('参数错误', 422);
        }

        $role = RoleModel::find($id);
        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        $userCount = Db::name('user_role')->where('role_id', $id)->count();
        if ($userCount > 0) {
            return $this->error('该角色仍有 ' . $userCount . ' 个用户关联，无法删除，请先解除用户关联', 422);
        }

        Db::startTrans();
        try {
            RoleModel::destroy($id);
            Db::name('role_menu')->where('role_id', $id)->delete();
            Db::name('role_menu_button')->where('role_id', $id)->delete();
            Db::name('role_api')->where('role_id', $id)->delete();

            Db::commit();
            $this->clearRoleCache($id);
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('删除角色失败：' . $e->getMessage());
        }
    }

    public function assignMenus(Request $request, int $id)
    {
        $role = RoleModel::find($id);
        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        $data = $request->post();

        try {
            $validate = new RoleValidate();
            $validate->scene('assign_menus')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $menuIds = $data['menu_ids'] ?? [];

        if (!empty($menuIds)) {
            $existMenuIds = Db::name('menu')
                ->whereIn('id', $menuIds)
                ->where('status', 1)
                ->whereNull('delete_time')
                ->column('id');

            $invalidIds = array_diff($menuIds, $existMenuIds);
            if (!empty($invalidIds)) {
                return $this->error('以下菜单ID不存在或已禁用：' . implode(',', $invalidIds), 422);
            }
        }

        if ($role->assignMenus($id, $menuIds)) {
            $this->clearRoleCache($id);
            return $this->success([], '菜单权限分配成功');
        }

        return $this->error('菜单权限分配失败');
    }

    public function assignButtons(Request $request, int $id)
    {
        $role = RoleModel::find($id);
        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        $data = $request->post();

        try {
            $validate = new RoleValidate();
            $validate->scene('assign_buttons')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $buttonIds = $data['button_ids'] ?? [];

        if (!empty($buttonIds)) {
            $existButtonIds = Db::name('menu_button')
                ->whereIn('id', $buttonIds)
                ->where('status', 1)
                ->whereNull('delete_time')
                ->column('id');

            $invalidIds = array_diff($buttonIds, $existButtonIds);
            if (!empty($invalidIds)) {
                return $this->error('以下按钮ID不存在或已禁用：' . implode(',', $invalidIds), 422);
            }
        }

        if ($role->assignButtons($id, $buttonIds)) {
            $this->clearRoleCache($id);
            return $this->success([], '按钮权限分配成功');
        }

        return $this->error('按钮权限分配失败');
    }

    public function assignApis(Request $request, int $id)
    {
        $role = RoleModel::find($id);
        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        $data = $request->post();

        try {
            $validate = new RoleValidate();
            $validate->scene('assign_apis')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $apiIds = $data['api_ids'] ?? [];

        if (!empty($apiIds)) {
            $existApiIds = Db::name('api')
                ->whereIn('id', $apiIds)
                ->where('status', 1)
                ->whereNull('delete_time')
                ->column('id');

            $invalidIds = array_diff($apiIds, $existApiIds);
            if (!empty($invalidIds)) {
                return $this->error('以下接口ID不存在或已禁用：' . implode(',', $invalidIds), 422);
            }
        }

        if ($role->assignApis($id, $apiIds)) {
            $this->clearRoleCache($id);
            return $this->success([], '接口权限分配成功');
        }

        return $this->error('接口权限分配失败');
    }

    public function setDataScope(Request $request, int $id)
    {
        $role = RoleModel::find($id);
        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new RoleValidate();
            $validate->scene('data_scope')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $dataScope = (int) ($data['data_scope'] ?? 0);
        $deptIds = $data['data_scope_dept_ids'] ?? '';

        if ($dataScope === 5 && empty($deptIds)) {
            return $this->error('自定义数据权限必须指定部门', 422);
        }

        if ($dataScope !== 5) {
            $deptIds = '';
        }

        $role->data_scope = $dataScope;
        $role->data_scope_dept_ids = $deptIds;
        $role->updated_by = $request->userInfo['id'] ?? null;
        $role->save();

        $this->clearRoleCache($id);
        return $this->success([], '数据权限配置成功');
    }

    public function changeStatus(Request $request, int $id)
    {
        $role = RoleModel::find($id);
        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new RoleValidate();
            $validate->scene('change_status')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $role->status = (int) $data['status'];
        $role->updated_by = $request->userInfo['id'] ?? null;
        $role->save();

        $this->clearRoleCache($id);
        return $this->success([], $data['status'] == 1 ? '角色已启用' : '角色已禁用');
    }

    protected function clearRoleCache(int $roleId): void
    {
        $userIds = Db::name('user_role')->where('role_id', $roleId)->column('user_id');

        foreach ($userIds as $userId) {
            $auth = AdminAuth::instance();
            $auth->setUser($userId);
            $auth->clearCache();
        }
    }
}