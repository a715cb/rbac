<?php
namespace app\admin\controller;

use app\model\Department as DepartmentModel;
use app\common\AdminAuth;
use app\admin\validate\DepartmentValidate;
use think\Request;
use think\facade\Db;

class DepartmentController extends BaseController
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword', '');
        if (mb_strlen($keyword) > 200) {
            return $this->error('搜索关键词长度不能超过200个字符', 422);
        }
        $keyword = trim($keyword);
        if (mb_strlen($keyword) > 50) {
            return $this->error('搜索关键词长度不能超过50个字符', 422);
        }
        $status = $request->get('status');

        $tree = (new DepartmentModel())->getDepartmentTree(
            $status !== null && $status !== '' ? (int) $status : null,
            $keyword
        );

        return $this->success([
            'list' => $tree,
        ], '获取成功');
    }

    public function tree(Request $request)
    {
        $status = $request->get('status');

        $tree = (new DepartmentModel())->getDepartmentTree($status !== null && $status !== '' ? (int) $status : null);

        return $this->success([
            'tree' => $tree,
        ], '获取成功');
    }

    public function show(int $id)
    {
        $department = DepartmentModel::find($id);
        if (!$department) {
            return $this->error('部门不存在', 404);
        }

        $deptData = $department->toArray();

        if ($deptData['parent_id'] > 0) {
            $parentDept = DepartmentModel::find($deptData['parent_id']);
            $deptData['parent_name'] = $parentDept ? $parentDept->name : '';
        } else {
            $deptData['parent_name'] = '';
        }

        return $this->success($deptData, '获取成功');
    }

    public function store(Request $request)
    {
        $data = $request->post();

        try {
            $validate = new DepartmentValidate();
            $validate->scene('store')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (DepartmentModel::where('code', $data['code'])->find()) {
            return $this->error('部门编码已存在', 422);
        }

        if ($data['parent_id'] > 0) {
            $parentDept = DepartmentModel::find($data['parent_id']);
            if (!$parentDept) {
                return $this->error('父部门不存在', 422);
            }
        }

        try {
            $department = new DepartmentModel();
            $department->parent_id = $data['parent_id'] ?? 0;
            $department->name = $data['name'];
            $department->code = $data['code'];
            $department->leader = $data['leader'] ?? '';
            $department->phone = $data['phone'] ?? '';
            $department->email = $data['email'] ?? '';
            $department->sort = $data['sort'] ?? 0;
            $department->status = $data['status'] ?? 1;
            $department->created_by = $request->userInfo['id'] ?? null;
            $department->save();

            return $this->success(['id' => $department->id], '创建成功');
        } catch (\Exception $e) {
            return $this->error('创建部门失败：' . $e->getMessage());
        }
    }

    public function update(Request $request, int $id)
    {
        $department = DepartmentModel::find($id);
        if (!$department) {
            return $this->error('部门不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new DepartmentValidate();
            $validate->scene('update')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (DepartmentModel::where('code', $data['code'])->where('id', '<>', $id)->find()) {
            return $this->error('部门编码已存在', 422);
        }

        if ($data['parent_id'] > 0) {
            if ($data['parent_id'] == $id) {
                return $this->error('不能将自己设置为父部门', 422);
            }

            $descendantIds = (new DepartmentModel())->getDescendantDeptIds($id);
            if (in_array($data['parent_id'], $descendantIds)) {
                return $this->error('不能将父部门设置为自己的子部门', 422);
            }

            $parentDept = DepartmentModel::find($data['parent_id']);
            if (!$parentDept) {
                return $this->error('父部门不存在', 422);
            }
        }

        try {
            if (isset($data['parent_id'])) $department->parent_id = $data['parent_id'];
            if (isset($data['name'])) $department->name = $data['name'];
            if (isset($data['code'])) $department->code = $data['code'];
            if (isset($data['leader'])) $department->leader = $data['leader'];
            if (isset($data['phone'])) $department->phone = $data['phone'];
            if (isset($data['email'])) $department->email = $data['email'];
            if (isset($data['sort'])) $department->sort = (int) $data['sort'];
            if (isset($data['status'])) $department->status = (int) $data['status'];

            $department->updated_by = $request->userInfo['id'] ?? null;
            $department->save();

            AdminAuth::instance()->clearCache();
            return $this->success([], '更新成功');
        } catch (\Exception $e) {
            return $this->error('更新部门失败：' . $e->getMessage());
        }
    }

    public function destroy(Request $request, int $id)
    {
        if ($id <= 0) {
            return $this->error('参数错误', 422);
        }

        $department = DepartmentModel::find($id);
        if (!$department) {
            return $this->error('部门不存在', 404);
        }

        $children = DepartmentModel::where('parent_id', $id)->count();
        if ($children > 0) {
            return $this->error('该部门存在子部门，无法删除', 422);
        }

        // 检查是否存在主部门用户
        $primaryUserCount = Db::name('sys_user_dept')
            ->where('dept_id', $id)
            ->where('is_primary', 1)
            ->count();
        if ($primaryUserCount > 0) {
            return $this->error('该部门下存在主部门用户，请先转移', 422);
        }

        Db::startTrans();
        try {
            // 移除兼职用户的关联关系
            Db::name('sys_user_dept')
                ->where('dept_id', $id)
                ->where('is_primary', 0)
                ->delete();

            DepartmentModel::destroy($id);
            Db::commit();
            AdminAuth::instance()->clearCache();
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('删除部门失败：' . $e->getMessage());
        }
    }

    public function setStatus(Request $request, int $id)
    {
        $department = DepartmentModel::find($id);
        if (!$department) {
            return $this->error('部门不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new DepartmentValidate();
            $validate->scene('setStatus')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $department->status = (int) $data['status'];
        $department->save();

        AdminAuth::instance()->clearCache();
        return $this->success([], $data['status'] == 1 ? '部门已启用' : '部门已禁用');
    }

    public function setSort(Request $request, int $id)
    {
        $department = DepartmentModel::find($id);
        if (!$department) {
            return $this->error('部门不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new DepartmentValidate();
            $validate->scene('setSort')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $department->sort = (int) $data['sort'];
        $department->save();

        return $this->success([], '排序更新成功');
    }

    public function getUsers(int $id)
    {
        $department = DepartmentModel::find($id);
        if (!$department) {
            return $this->error('部门不存在', 404);
        }

        // 从 sys_user_dept 查询所有关联用户（含兼职）
        $userDepts = Db::name('sys_user_dept')
            ->where('dept_id', $id)
            ->select()
            ->toArray();

        if (empty($userDepts)) {
            return $this->success(['list' => []], '获取成功');
        }

        $userIds = array_column($userDepts, 'user_id');
        $isPrimaryMap = [];
        foreach ($userDepts as $ud) {
            $isPrimaryMap[$ud['user_id']] = $ud['is_primary'];
        }

        $users = (new \app\model\User())
            ->whereIn('id', $userIds)
            ->where('status', 1)
            ->whereNull('delete_time')
            ->select()
            ->toArray();

        foreach ($users as &$user) {
            $user['is_primary'] = $isPrimaryMap[$user['id']] ?? 0;
        }

        return $this->success([
            'list' => $users,
        ], '获取成功');
    }
}