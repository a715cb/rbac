<?php
namespace app\admin\controller;

use app\model\User as UserModel;
use app\model\Role;
use app\model\Department;
use app\common\AdminAuth;
use app\admin\validate\UserValidate;
use think\Request;
use think\facade\Db;

class UserController extends BaseController
{
    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 15);
        $keyword = $request->get('keyword', '');
        $status = $request->get('status');
        $deptId = $request->get('dept_id');
        $gender = $request->get('gender');

        $where = [];

        if (!empty($keyword)) {
            $where[] = ['username|nickname|email|mobile', 'like', "%{$keyword}%"];
        }

        if ($status !== null && $status !== '') {
            $where[] = ['status', '=', (int) $status];
        }

        if ($deptId !== null && $deptId !== '') {
            $where[] = ['dept_id', '=', (int) $deptId];
        }

        if ($gender !== null && $gender !== '') {
            $where[] = ['gender', '=', (int) $gender];
        }

        $auth = AdminAuth::instance();
        $auth->setUser($request->userInfo['id'] ?? 0);

        if (!$auth->isSuperAdmin()) {
            if ($auth->isSelfOnly()) {
                $where[] = ['id', '=', $auth->getUserId()];
            } else {
                $scopedDeptIds = $auth->getScopedDeptIds();
                if (!empty($scopedDeptIds)) {
                    $where[] = ['dept_id', 'in', $scopedDeptIds];
                }
            }
        }

        $total = UserModel::where($where)->count();
        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;

        $list = UserModel::where($where)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        // 批量查询角色和部门信息，避免N+1查询问题
        $userIds = array_column($list, 'id');
        $deptIds = array_filter(array_column($list, 'dept_id'));

        // 批量获取所有用户的角色
        $rolesByUser = [];
        if (!empty($userIds)) {
            $userRoleMap = Db::name('sys_user_role')
                ->whereIn('user_id', $userIds)
                ->select()
                ->toArray();
            
            if (!empty($userRoleMap)) {
                $allRoleIds = array_unique(array_column($userRoleMap, 'role_id'));
                $rolesData = Db::name('sys_role')
                    ->whereIn('id', $allRoleIds)
                    ->where('status', 1)
                    ->whereNull('delete_time')
                    ->select()
                    ->toArray();
                
                $roleMap = [];
                foreach ($rolesData as $role) {
                    $roleMap[$role['id']] = $role;
                }
                
                foreach ($userRoleMap as $userRole) {
                    $role = $roleMap[$userRole['role_id']] ?? null;
                    if ($role) {
                        $rolesByUser[$userRole['user_id']][] = $role;
                    }
                }
            }
        }

        // 批量获取所有部门
        $deptMap = [];
        if (!empty($deptIds)) {
            $depts = Department::whereIn('id', $deptIds)->select()->toArray();
            foreach ($depts as $dept) {
                $deptMap[$dept['id']] = $dept['name'];
            }
        }

        // 批量获取用户-部门关联
        $deptsByUser = [];
        if (!empty($userIds)) {
            $userDeptList = Db::name('sys_user_dept')
                ->whereIn('user_id', $userIds)
                ->order('sort', 'asc')
                ->select()
                ->toArray();
            
            $allUserDeptIds = array_unique(array_column($userDeptList, 'dept_id'));
            $deptNameMap = [];
            if (!empty($allUserDeptIds)) {
                $deptNameMap = Department::whereIn('id', $allUserDeptIds)->column('name', 'id');
            }
            
            foreach ($userDeptList as $ud) {
                $deptsByUser[$ud['user_id']][] = [
                    'dept_id' => $ud['dept_id'],
                    'dept_name' => $deptNameMap[$ud['dept_id']] ?? '',
                    'is_primary' => $ud['is_primary'],
                    'sort' => $ud['sort'],
                ];
            }
        }

        foreach ($list as &$item) {
            $item['roles'] = $rolesByUser[$item['id']] ?? [];
            $item['dept_name'] = !empty($item['dept_id']) ? ($deptMap[$item['dept_id']] ?? '') : '';
            $item['depts'] = $deptsByUser[$item['id']] ?? [];
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
        $user = UserModel::find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $userData = $user->toArray();
        $roles = (new Role())->getUserRoles($id);
        $userData['roles'] = $roles;

        if (!empty($userData['dept_id'])) {
            $dept = Department::find($userData['dept_id']);
            $userData['dept_name'] = $dept ? $dept->name : '';
        }

        // 查询用户所有关联部门
        $userDepts = Db::name('sys_user_dept')
            ->where('user_id', $id)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
        
        $deptIds = array_column($userDepts, 'dept_id');
        $deptNameMap = [];
        if (!empty($deptIds)) {
            $deptNameMap = Department::whereIn('id', $deptIds)->column('name', 'id');
        }
        
        $userData['depts'] = array_map(function ($ud) use ($deptNameMap) {
            return [
                'dept_id' => $ud['dept_id'],
                'dept_name' => $deptNameMap[$ud['dept_id']] ?? '',
                'is_primary' => $ud['is_primary'],
                'sort' => $ud['sort'],
            ];
        }, $userDepts);

        return $this->success($userData, '获取成功');
    }

    public function store(Request $request)
    {
        $data = $request->post();

        try {
            $validate = new UserValidate();
            $validate->scene('store')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        Db::startTrans();
        try {
            // 密码强度验证：至少8位，包含大小写字母、数字和特殊字符
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $data['password'])) {
                throw new \Exception('密码必须包含大小写字母、数字和特殊字符，且长度至少8位');
            }

            // 使用事务和悲观锁确保并发安全
            if (UserModel::where('username', $data['username'])->lock(true)->find()) {
                throw new \Exception('用户名已存在');
            }

            if (!empty($data['email']) && UserModel::where('email', $data['email'])->whereNull('delete_time')->find()) {
                throw new \Exception('邮箱已存在');
            }

            if (!empty($data['mobile']) && UserModel::where('mobile', $data['mobile'])->whereNull('delete_time')->find()) {
                throw new \Exception('手机号已存在');
            }

            $user = new UserModel();
            $user->username = $data['username'];
            $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
            $user->nickname = $data['nickname'] ?? '';
            $user->email = $data['email'] ?? null;
            $user->mobile = $data['mobile'] ?? null;
            $user->gender = $data['gender'] ?? 0;
            $user->dept_id = $data['dept_id'] ?? null;
            $user->status = $data['status'] ?? 1;
            $user->created_by = $request->userInfo['id'] ?? null;
            $user->save();

            if (!empty($data['role_ids'])) {
                $this->assignRolesInternal($user->id, $data['role_ids']);
            }

            // 同步写入用户-部门关联表
            if (!empty($data['depts'])) {
                $this->syncUserDepts($user->id, $data['depts']);
            } elseif (!empty($data['dept_id'])) {
                // 向后兼容：如果前端仍传 dept_id 单字段，自动创建关联记录
                Db::name('sys_user_dept')->insert([
                    'user_id' => $user->id,
                    'dept_id' => $data['dept_id'],
                    'is_primary' => 1,
                    'sort' => 0,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
            }

            Db::commit();
            return $this->success(['id' => $user->id], '创建成功');
        } catch (\Exception $e) {
            Db::rollback();
            // 如果是自定义异常则返回错误信息，否则返回通用错误
            $message = $e instanceof \think\db\exception\PDOException ? '用户名已存在' : $e->getMessage();
            return $this->error($message);
        }
    }

    public function update(Request $request, int $id)
    {
        $user = UserModel::find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new UserValidate();
            $validate->scene('update')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        // 检查邮箱和手机号唯一性（不包括软删除用户）
        if (!empty($data['email']) && UserModel::where('email', $data['email'])->where('id', '<>', $id)->whereNull('delete_time')->find()) {
            return $this->error('邮箱已存在', 422);
        }

        if (!empty($data['mobile']) && UserModel::where('mobile', $data['mobile'])->where('id', '<>', $id)->whereNull('delete_time')->find()) {
            return $this->error('手机号已存在', 422);
        }

        Db::startTrans();
        try {
            if (isset($data['nickname'])) $user->nickname = $data['nickname'];
            if (isset($data['email'])) $user->email = $data['email'];
            if (isset($data['mobile'])) $user->mobile = $data['mobile'];
            if (isset($data['gender'])) $user->gender = (int) $data['gender'];
            if (isset($data['depts'])) {
                $this->syncUserDepts($id, $data['depts']);
            } elseif (isset($data['dept_id'])) {
                $user->dept_id = $data['dept_id'];
                // 同步更新关联表中的主部门
                Db::name('sys_user_dept')->where('user_id', $id)->where('is_primary', 1)->update(['dept_id' => $data['dept_id']]);
                // 如果没有主部门记录则创建
                $primaryExists = Db::name('sys_user_dept')->where('user_id', $id)->where('is_primary', 1)->find();
                if (!$primaryExists) {
                    Db::name('sys_user_dept')->insert([
                        'user_id' => $id,
                        'dept_id' => $data['dept_id'],
                        'is_primary' => 1,
                        'sort' => 0,
                        'create_time' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
            if (isset($data['status'])) $user->status = (int) $data['status'];

            $user->updated_by = $request->userInfo['id'] ?? null;
            $user->save();

            if (isset($data['role_ids'])) {
                $this->assignRolesInternal($id, $data['role_ids']);
            }

            Db::commit();
            AdminAuth::instance()->clearCache();
            return $this->success([], '更新成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('更新用户失败：' . $e->getMessage());
        }
    }

    public function destroy(Request $request, int $id)
    {
        if ($id <= 0) {
            return $this->error('参数错误', 422);
        }

        $currentUserId = $request->userInfo['id'] ?? 0;
        if ($id == $currentUserId) {
            return $this->error('不能删除当前登录用户', 422);
        }

        $user = UserModel::find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        Db::startTrans();
        try {
            UserModel::destroy($id);
            Db::name('sys_user_role')->where('user_id', $id)->delete();
            Db::name('sys_user_dept')->where('user_id', $id)->delete();
            Db::commit();

            AdminAuth::instance()->clearCache();
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('删除用户失败：' . $e->getMessage());
        }
    }

    public function assignRoles(Request $request, int $id)
    {
        $user = UserModel::find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $data = $request->post();

        try {
            $validate = new UserValidate();
            $validate->scene('assign_roles')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $roleIds = $data['role_ids'] ?? [];

        if (!empty($roleIds)) {
            $existRoleIds = Db::name('sys_role')
                ->whereIn('id', $roleIds)
                ->where('status', 1)
                ->whereNull('delete_time')
                ->column('id');

            $invalidIds = array_diff($roleIds, $existRoleIds);
            if (!empty($invalidIds)) {
                return $this->error('以下角色ID不存在或已禁用：' . implode(',', $invalidIds), 422);
            }
        }

        if ($this->assignRolesInternal($id, $roleIds)) {
            AdminAuth::instance()->clearCache();
            return $this->success([], '角色分配成功');
        }

        return $this->error('角色分配失败');
    }

    public function resetPassword(Request $request, int $id)
    {
        $user = UserModel::find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $data = $request->post();
        $password = $data['password'] ?? '';

        // 密码强度验证：至少8位，包含大小写字母、数字和特殊字符
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
            return $this->error('密码必须包含大小写字母、数字和特殊字符，且长度至少8位', 422);
        }

        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->save();

        return $this->success([], '密码重置成功');
    }

    public function changeStatus(Request $request, int $id)
    {
        $user = UserModel::find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new UserValidate();
            $validate->scene('change_status')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $currentUserId = $request->userInfo['id'] ?? 0;
        if ($id == $currentUserId && (int) $data['status'] === 0) {
            return $this->error('不能禁用当前登录用户', 422);
        }

        $user->status = (int) $data['status'];
        $user->save();

        AdminAuth::instance()->clearCache();
        return $this->success([], $data['status'] == 1 ? '用户已启用' : '用户已禁用');
    }

    public function export(Request $request)
    {
        $keyword = $request->get('keyword', '');
        $status = $request->get('status');
        $deptId = $request->get('dept_id');

        $where = [];

        if (!empty($keyword)) {
            $where[] = ['username|nickname|email|mobile', 'like', "%{$keyword}%"];
        }

        if ($status !== null && $status !== '') {
            $where[] = ['status', '=', (int) $status];
        }

        if ($deptId !== null && $deptId !== '') {
            $where[] = ['dept_id', '=', (int) $deptId];
        }

        $list = UserModel::where($where)
            ->order('id', 'desc')
            ->select()
            ->toArray();

        // 批量查询角色和部门信息，避免N+1查询问题
        $userIds = array_column($list, 'id');
        $deptIds = array_filter(array_column($list, 'dept_id'));

        $rolesByUser = [];
        if (!empty($userIds)) {
            $userRoleMap = Db::name('sys_user_role')
                ->whereIn('user_id', $userIds)
                ->select()
                ->toArray();
            
            if (!empty($userRoleMap)) {
                $allRoleIds = array_unique(array_column($userRoleMap, 'role_id'));
                $rolesData = Db::name('sys_role')
                    ->whereIn('id', $allRoleIds)
                    ->where('status', 1)
                    ->whereNull('delete_time')
                    ->select()
                    ->toArray();
                
                $roleMap = [];
                foreach ($rolesData as $role) {
                    $roleMap[$role['id']] = $role;
                }
                
                foreach ($userRoleMap as $userRole) {
                    $role = $roleMap[$userRole['role_id']] ?? null;
                    if ($role) {
                        $rolesByUser[$userRole['user_id']][] = $role;
                    }
                }
            }
        }

        $deptMap = [];
        if (!empty($deptIds)) {
            $depts = Department::whereIn('id', $deptIds)->select()->toArray();
            foreach ($depts as $dept) {
                $deptMap[$dept['id']] = $dept['name'];
            }
        }

        // 批量获取用户-部门关联
        $deptsByUser = [];
        if (!empty($userIds)) {
            $userDeptList = Db::name('sys_user_dept')
                ->whereIn('user_id', $userIds)
                ->order('sort', 'asc')
                ->select()
                ->toArray();
            
            $allUserDeptIds = array_unique(array_column($userDeptList, 'dept_id'));
            $deptNameMap = [];
            if (!empty($allUserDeptIds)) {
                $deptNameMap = Department::whereIn('id', $allUserDeptIds)->column('name', 'id');
            }
            
            foreach ($userDeptList as $ud) {
                $deptsByUser[$ud['user_id']][] = [
                    'dept_id' => $ud['dept_id'],
                    'dept_name' => $deptNameMap[$ud['dept_id']] ?? '',
                    'is_primary' => $ud['is_primary'],
                    'sort' => $ud['sort'],
                ];
            }
        }

        $exportData = [];
        foreach ($list as $item) {
            $roles = $rolesByUser[$item['id']] ?? [];
            $roleNames = array_column($roles, 'name');
            $deptName = !empty($item['dept_id']) ? ($deptMap[$item['dept_id']] ?? '') : '';
            
            // 多部门名称拼接
            $userDepts = $deptsByUser[$item['id']] ?? [];
            $allDeptNames = array_map(function ($d) {
                return $d['dept_name'] . ($d['is_primary'] ? '(主)' : '');
            }, $userDepts);
            $allDeptName = implode(',', $allDeptNames);

            $exportData[] = [
                'id' => $item['id'],
                'username' => $item['username'],
                'nickname' => $item['nickname'] ?? '',
                'email' => $item['email'] ?? '',
                'mobile' => $item['mobile'] ?? '',
                'gender' => $item['gender'] == 1 ? '男' : ($item['gender'] == 2 ? '女' : '未知'),
                'dept_name' => $deptName,
                'all_dept_names' => $allDeptName,
                'roles' => implode(',', $roleNames),
                'status' => $item['status'] == 1 ? '正常' : '禁用',
                'last_login_time' => $item['last_login_time'] ?? '',
                'create_time' => $item['create_time'] ?? '',
            ];
        }

        return $this->success($exportData, '导出成功');
    }

    public function import(Request $request)
    {
        $file = $request->file('file');
        if (!$file) {
            return $this->error('请上传文件', 422);
        }

        $ext = strtolower($file->getOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            return $this->error('仅支持 Excel 或 CSV 文件', 422);
        }

        $data = $request->post('data');
        if (empty($data)) {
            return $this->error('导入数据不能为空', 422);
        }

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (!is_array($data)) {
            return $this->error('导入数据格式错误', 422);
        }

        $successCount = 0;
        $failCount = 0;
        $errors = [];

        Db::startTrans();
        try {
            foreach ($data as $index => $row) {
                $username = $row['username'] ?? '';
                $password = $row['password'] ?? '123456';

                if (empty($username)) {
                    $failCount++;
                    $errors[] = "第" . ($index + 1) . "行：用户名不能为空";
                    continue;
                }

                if (UserModel::where('username', $username)->whereNull('delete_time')->find()) {
                    $failCount++;
                    $errors[] = "第" . ($index + 1) . "行：用户名 {$username} 已存在";
                    continue;
                }

                $user = new UserModel();
                $user->username = $username;
                $user->password = password_hash($password, PASSWORD_DEFAULT);
                $user->nickname = $row['nickname'] ?? '';
                $user->email = $row['email'] ?? null;
                $user->mobile = $row['mobile'] ?? null;
                $user->gender = $row['gender'] ?? 0;
                $user->status = 1;
                $user->created_by = $request->userInfo['id'] ?? null;
                $user->save();

                $successCount++;
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('导入失败：' . $e->getMessage());
        }

        return $this->success([
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'errors' => $errors,
        ], '导入完成');
    }

    public function updateDepts(Request $request, int $id)
    {
        $user = UserModel::find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $data = $request->put();
        $depts = $data['depts'] ?? [];

        if (empty($depts)) {
            return $this->error('必须指定至少一个部门', 422);
        }

        $primaryCount = 0;
        $primaryDeptId = null;
        foreach ($depts as $dept) {
            if (!empty($dept['is_primary'])) {
                $primaryCount++;
                $primaryDeptId = $dept['dept_id'];
            }
        }

        if ($primaryCount === 0) {
            return $this->error('必须指定一个主部门', 422);
        }
        if ($primaryCount > 1) {
            return $this->error('只能有一个主部门', 422);
        }

        $deptIds = array_column($depts, 'dept_id');
        $existDeptIds = Department::whereIn('id', $deptIds)->where('status', 1)->whereNull('delete_time')->column('id');
        $invalidIds = array_diff($deptIds, $existDeptIds);
        if (!empty($invalidIds)) {
            return $this->error('以下部门不存在或已禁用：' . implode(',', $invalidIds), 422);
        }

        Db::startTrans();
        try {
            Db::name('sys_user_dept')->where('user_id', $id)->delete();

            $insertData = [];
            foreach ($depts as $index => $dept) {
                $insertData[] = [
                    'user_id' => $id,
                    'dept_id' => $dept['dept_id'],
                    'is_primary' => !empty($dept['is_primary']) ? 1 : 0,
                    'sort' => $dept['sort'] ?? $index,
                    'create_time' => date('Y-m-d H:i:s'),
                ];
            }
            Db::name('sys_user_dept')->insertAll($insertData);

            $user->dept_id = $primaryDeptId;
            $user->save();

            Db::commit();
            return $this->success([], '更新成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('更新部门关联失败：' . $e->getMessage());
        }
    }

    public function addDepts(Request $request, int $id)
    {
        $user = UserModel::find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $data = $request->post();
        $depts = $data['depts'] ?? [];

        if (empty($depts)) {
            return $this->error('部门列表不能为空', 422);
        }

        $existingDepts = Db::name('sys_user_dept')->where('user_id', $id)->column('dept_id');
        $hasPrimary = Db::name('sys_user_dept')->where('user_id', $id)->where('is_primary', 1)->count() > 0;

        $insertData = [];
        foreach ($depts as $index => $dept) {
            if (in_array($dept['dept_id'], $existingDepts)) {
                continue;
            }

            $isPrimary = !empty($dept['is_primary']) ? 1 : 0;
            if ($isPrimary && $hasPrimary) {
                return $this->error('用户已有主部门，不能重复设置', 422);
            }

            $insertData[] = [
                'user_id' => $id,
                'dept_id' => $dept['dept_id'],
                'is_primary' => $isPrimary,
                'sort' => $dept['sort'] ?? $index,
                'create_time' => date('Y-m-d H:i:s'),
            ];
        }

        if (empty($insertData)) {
            return $this->error('所有部门关联已存在', 422);
        }

        $newDeptIds = array_column($insertData, 'dept_id');
        $existDeptIds = Department::whereIn('id', $newDeptIds)->where('status', 1)->whereNull('delete_time')->column('id');
        $invalidIds = array_diff($newDeptIds, $existDeptIds);
        if (!empty($invalidIds)) {
            return $this->error('以下部门不存在或已禁用：' . implode(',', $invalidIds), 422);
        }

        Db::startTrans();
        try {
            Db::name('sys_user_dept')->insertAll($insertData);

            $primaryRecord = array_filter($insertData, fn($r) => $r['is_primary'] == 1);
            if (!empty($primaryRecord)) {
                $user->dept_id = reset($primaryRecord)['dept_id'];
                $user->save();
            }

            Db::commit();
            return $this->success([], '添加成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('添加部门关联失败：' . $e->getMessage());
        }
    }

    public function removeDept(Request $request, int $id, int $deptId)
    {
        $user = UserModel::find($id);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $relation = Db::name('sys_user_dept')->where('user_id', $id)->where('dept_id', $deptId)->find();
        if (!$relation) {
            return $this->error('该用户不在此部门中', 404);
        }

        if ($relation['is_primary'] == 1) {
            return $this->error('不能移除主部门，请先设置其他部门为主部门', 422);
        }

        Db::startTrans();
        try {
            Db::name('sys_user_dept')->where('user_id', $id)->where('dept_id', $deptId)->delete();
            Db::commit();
            return $this->success([], '移除成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('移除部门关联失败：' . $e->getMessage());
        }
    }

    protected function syncUserDepts(int $userId, array $depts): void
    {
        $primaryCount = 0;
        $primaryDeptId = null;
        foreach ($depts as $dept) {
            if (!empty($dept['is_primary'])) {
                $primaryCount++;
                $primaryDeptId = $dept['dept_id'];
            }
        }

        if ($primaryCount === 0) {
            throw new \Exception('必须指定一个主部门');
        }
        if ($primaryCount > 1) {
            throw new \Exception('只能有一个主部门');
        }

        Db::name('sys_user_dept')->where('user_id', $userId)->delete();

        $insertData = [];
        foreach ($depts as $index => $dept) {
            $insertData[] = [
                'user_id' => $userId,
                'dept_id' => $dept['dept_id'],
                'is_primary' => !empty($dept['is_primary']) ? 1 : 0,
                'sort' => $dept['sort'] ?? $index,
                'create_time' => date('Y-m-d H:i:s'),
            ];
        }
        Db::name('sys_user_dept')->insertAll($insertData);

        Db::name('sys_user')->where('id', $userId)->update(['dept_id' => $primaryDeptId]);
    }

    protected function assignRolesInternal(int $userId, array $roleIds): bool
    {
        Db::startTrans();
        try {
            Db::name('sys_user_role')->where('user_id', $userId)->delete();

            if (!empty($roleIds)) {
                $insertData = array_map(function ($roleId) use ($userId) {
                    return [
                        'user_id' => $userId,
                        'role_id' => $roleId,
                        'create_time' => date('Y-m-d H:i:s'),
                    ];
                }, $roleIds);
                Db::name('sys_user_role')->insertAll($insertData);
            }

            Db::commit();
            return true;
        } catch (\Exception) {
            Db::rollback();
            return false;
        }
    }
}