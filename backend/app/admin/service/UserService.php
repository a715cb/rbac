<?php

namespace app\admin\service;

use app\model\User as UserModel;
use app\model\Role;
use app\model\Department;
use app\common\AdminAuth;
use think\facade\Db;

/**
 * 用户管理服务
 *
 * 负责处理用户全生命周期的核心业务逻辑，包括增删改查、角色分配、
 * 多部门关联管理、密码重置、状态切换、数据导入导出等。
 * 从 UserController 中提取，遵循单一职责原则。
 *
 * 设计思路：
 *   - 采用单例模式，与项目现有 Service 层保持一致
 *   - 返回统一结果结构 ['success' => bool, 'data' => ..., 'error' => ..., 'code' => int]
 *   - 批量预加载策略避免 N+1 查询问题
 *   - 数据权限过滤支持超管/仅本人/部门级三种粒度
 *   - 所有写操作在数据库事务中执行，保证数据一致性
 *
 * @see \app\admin\controller\UserController 控制器层，负责请求/响应处理
 */
class UserService
{
    private static ?UserService $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取用户列表（分页）
     *
     * 根据筛选条件和数据权限查询用户列表，批量预加载角色和部门信息。
     * 执行流程：构建查询条件 → 应用数据权限过滤 → 分页查询 → 批量预加载关联数据 → 组装结果
     *
     * @param array $params 查询参数，包含 page, limit, keyword, status, dept_id, gender
     * @param int $currentUserId 当前登录用户ID，用于数据权限过滤
     * @return array ['success' => bool, 'data' => ['list' => [], 'pagination' => []], 'error' => string, 'code' => int]
     */
    public function getUserList(array $params, int $currentUserId): array
    {
        $where = $this->buildListConditions($params);

        $auth = AdminAuth::instance();
        $auth->setUser($currentUserId);
        $this->applyDataScopeFilter($where, $auth);

        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 15);

        $total = UserModel::where($where)->count();
        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;

        $list = UserModel::where($where)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $list = $this->enrichUserListWithRelations($list);

        return [
            'success' => true,
            'data' => [
                'list' => $list,
                'pagination' => [
                    'page' => $page,
                    'page_size' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages,
                ],
            ],
        ];
    }

    /**
     * 获取用户详情
     *
     * 返回指定用户的基本信息、角色列表、主部门名称及全部部门关联信息。
     *
     * @param int $id 用户ID
     * @return array ['success' => bool, 'data' => userData, 'error' => string, 'code' => int]
     */
    public function getUserDetail(int $id): array
    {
        $user = UserModel::find($id);
        if (!$user) {
            return ['success' => false, 'error' => '用户不存在', 'code' => 404];
        }

        $userData = $user->toArray();
        $userData['roles'] = (new Role())->getUserRoles($id);

        if (!empty($userData['dept_id'])) {
            $dept = Department::find($userData['dept_id']);
            $userData['dept_name'] = $dept ? $dept->name : '';
        }

        $userDepts = Db::name('user_dept')
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

        return ['success' => true, 'data' => $userData];
    }

    /**
     * 创建用户
     *
     * 执行用户名/邮箱/手机号唯一性校验、密码强度校验后创建用户，
     * 并同步写入角色关联和部门关联。整个操作在数据库事务中执行。
     * 用户名唯一性校验使用悲观锁防止并发冲突。
     *
     * @param array $data 用户数据，包含 username, password, nickname, email, mobile, gender, dept_id, status, role_ids, depts
     * @param int $currentUserId 当前登录用户ID
     * @return array ['success' => bool, 'data' => ['id' => int], 'error' => string, 'code' => int]
     */
    public function createUser(array $data, int $currentUserId): array
    {
        if (!$this->validatePasswordStrength($data['password'] ?? '')) {
            return ['success' => false, 'error' => '密码必须包含大小写字母、数字和特殊字符，且长度至少8位', 'code' => 422];
        }

        Db::startTrans();
        try {
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
            $user->created_by = $currentUserId;
            $user->save();

            if (!empty($data['role_ids'])) {
                $this->assignRolesInternal($user->id, $data['role_ids']);
            }

            if (!empty($data['depts'])) {
                $this->syncUserDepts($user->id, $data['depts']);
            } elseif (!empty($data['dept_id'])) {
                Db::name('user_dept')->insert([
                    'user_id' => $user->id,
                    'dept_id' => $data['dept_id'],
                    'is_primary' => 1,
                    'sort' => 0,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
            }

            Db::commit();
            return ['success' => true, 'data' => ['id' => $user->id]];
        } catch (\Exception $e) {
            Db::rollback();
            $message = $e instanceof \think\db\exception\PDOException ? '用户名已存在' : $e->getMessage();
            return ['success' => false, 'error' => $message, 'code' => 400];
        }
    }

    /**
     * 更新用户信息
     *
     * 支持字段级局部更新：仅更新请求中明确传入的字段。
     * 邮箱和手机号校验唯一性时排除当前用户自身。
     * 更新成功后清除权限缓存，确保权限变更即时生效。
     *
     * @param int $id 用户ID
     * @param array $data 需要更新的字段
     * @param int $currentUserId 当前登录用户ID
     * @return array ['success' => bool, 'data' => [], 'error' => string, 'code' => int]
     */
    public function updateUser(int $id, array $data, int $currentUserId): array
    {
        $user = UserModel::find($id);
        if (!$user) {
            return ['success' => false, 'error' => '用户不存在', 'code' => 404];
        }

        if (!empty($data['email']) && UserModel::where('email', $data['email'])->where('id', '<>', $id)->whereNull('delete_time')->find()) {
            return ['success' => false, 'error' => '邮箱已存在', 'code' => 422];
        }

        if (!empty($data['mobile']) && UserModel::where('mobile', $data['mobile'])->where('id', '<>', $id)->whereNull('delete_time')->find()) {
            return ['success' => false, 'error' => '手机号已存在', 'code' => 422];
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
                Db::name('user_dept')->where('user_id', $id)->where('is_primary', 1)->update(['dept_id' => $data['dept_id']]);
                $primaryExists = Db::name('user_dept')->where('user_id', $id)->where('is_primary', 1)->find();
                if (!$primaryExists) {
                    Db::name('user_dept')->insert([
                        'user_id' => $id,
                        'dept_id' => $data['dept_id'],
                        'is_primary' => 1,
                        'sort' => 0,
                        'create_time' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
            if (isset($data['status'])) $user->status = (int) $data['status'];

            $user->updated_by = $currentUserId;
            $user->save();

            if (isset($data['role_ids'])) {
                $this->assignRolesInternal($id, $data['role_ids']);
            }

            Db::commit();
            AdminAuth::instance()->clearCache();
            return ['success' => true, 'data' => []];
        } catch (\Exception $e) {
            Db::rollback();
            return ['success' => false, 'error' => '更新用户失败：' . $e->getMessage(), 'code' => 400];
        }
    }

    /**
     * 删除用户（软删除）
     *
     * 禁止删除当前登录用户。删除操作在事务中执行，级联清理
     * sys_user_role 和 sys_user_dept 关联记录，并清除权限缓存。
     *
     * @param int $id 待删除的用户ID
     * @param int $currentUserId 当前登录用户ID
     * @return array ['success' => bool, 'data' => [], 'error' => string, 'code' => int]
     */
    public function deleteUser(int $id, int $currentUserId): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => '参数错误', 'code' => 422];
        }

        if ($id == $currentUserId) {
            return ['success' => false, 'error' => '不能删除当前登录用户', 'code' => 422];
        }

        $user = UserModel::find($id);
        if (!$user) {
            return ['success' => false, 'error' => '用户不存在', 'code' => 404];
        }

        Db::startTrans();
        try {
            UserModel::destroy($id);
            Db::name('user_role')->where('user_id', $id)->delete();
            Db::name('user_dept')->where('user_id', $id)->delete();
            Db::commit();

            AdminAuth::instance()->clearCache();
            return ['success' => true, 'data' => []];
        } catch (\Exception $e) {
            Db::rollback();
            return ['success' => false, 'error' => '删除用户失败：' . $e->getMessage(), 'code' => 400];
        }
    }

    /**
     * 分配用户角色
     *
     * 采用全量替换模式：先删除用户的所有角色关联，再重新写入。
     * 分配前校验角色ID的有效性（存在、启用、未软删除）。
     * 分配成功后清除权限缓存。
     *
     * @param int $userId 用户ID
     * @param array $roleIds 角色 ID 列表
     * @return array ['success' => bool, 'data' => [], 'error' => string, 'code' => int]
     */
    public function assignRoles(int $userId, array $roleIds): array
    {
        $user = UserModel::find($userId);
        if (!$user) {
            return ['success' => false, 'error' => '用户不存在', 'code' => 404];
        }

        if (!empty($roleIds)) {
            $existRoleIds = Db::name('role')
                ->whereIn('id', $roleIds)
                ->where('status', 1)
                ->whereNull('delete_time')
                ->column('id');

            $invalidIds = array_diff($roleIds, $existRoleIds);
            if (!empty($invalidIds)) {
                return ['success' => false, 'error' => '以下角色ID不存在或已禁用：' . implode(',', $invalidIds), 'code' => 422];
            }
        }

        if ($this->assignRolesInternal($userId, $roleIds)) {
            AdminAuth::instance()->clearCache();
            return ['success' => true, 'data' => []];
        }

        return ['success' => false, 'error' => '角色分配失败', 'code' => 400];
    }

    /**
     * 重置用户密码
     *
     * 管理员强制重置指定用户的密码，新密码需满足强度策略。
     * 密码使用 PASSWORD_DEFAULT 算法哈希存储。
     *
     * @param int $userId 用户ID
     * @param string $password 新密码
     * @return array ['success' => bool, 'data' => [], 'error' => string, 'code' => int]
     */
    public function resetPassword(int $userId, string $password): array
    {
        $user = UserModel::find($userId);
        if (!$user) {
            return ['success' => false, 'error' => '用户不存在', 'code' => 404];
        }

        if (!$this->validatePasswordStrength($password)) {
            return ['success' => false, 'error' => '密码必须包含大小写字母、数字和特殊字符，且长度至少8位', 'code' => 422];
        }

        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->save();

        return ['success' => true, 'data' => []];
    }

    /**
     * 切换用户状态（启用/禁用）
     *
     * 禁止禁用当前登录用户，防止管理员锁定自身账号。
     * 状态变更后清除权限缓存。
     *
     * @param int $userId 用户ID
     * @param int $status 目标状态（1启用/0禁用）
     * @param int $currentUserId 当前登录用户ID
     * @return array ['success' => bool, 'data' => [], 'error' => string, 'code' => int]
     */
    public function changeStatus(int $userId, int $status, int $currentUserId): array
    {
        $user = UserModel::find($userId);
        if (!$user) {
            return ['success' => false, 'error' => '用户不存在', 'code' => 404];
        }

        if ($userId == $currentUserId && $status === 0) {
            return ['success' => false, 'error' => '不能禁用当前登录用户', 'code' => 422];
        }

        $user->status = $status;
        $user->save();

        AdminAuth::instance()->clearCache();
        return ['success' => true, 'data' => [], 'message' => $status == 1 ? '用户已启用' : '用户已禁用'];
    }

    /**
     * 导出用户数据
     *
     * 根据筛选条件查询用户列表，批量预加载角色和部门信息后
     * 组装为扁平化的导出数据结构。多部门名称以逗号分隔拼接，
     * 主部门标注"(主)"后缀。
     *
     * @param array $params 查询参数，包含 keyword, status, dept_id
     * @param int $currentUserId 当前登录用户ID
     * @return array ['success' => bool, 'data' => exportData, 'error' => string, 'code' => int]
     */
    public function exportUsers(array $params, int $currentUserId): array
    {
        $where = $this->buildListConditions($params);

        $auth = AdminAuth::instance();
        $auth->setUser($currentUserId);
        $this->applyDataScopeFilter($where, $auth);

        $list = UserModel::where($where)
            ->order('id', 'desc')
            ->select()
            ->toArray();

        $userIds = array_column($list, 'id');
        $deptIds = array_filter(array_column($list, 'dept_id'));

        $rolesByUser = $this->batchLoadUserRoles($userIds);
        $deptMap = $this->batchLoadDeptNames($deptIds);
        $deptsByUser = $this->batchLoadUserDepts($userIds);

        $exportData = [];
        foreach ($list as $item) {
            $roles = $rolesByUser[$item['id']] ?? [];
            $roleNames = array_column($roles, 'name');
            $deptName = !empty($item['dept_id']) ? ($deptMap[$item['dept_id']] ?? '') : '';

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

        return ['success' => true, 'data' => $exportData];
    }

    /**
     * 批量导入用户
     *
     * 接收前端解析后的用户数据数组，逐条创建用户。
     * 跳过用户名为空或用户名已存在的记录，记录失败原因。
     * 导入时默认密码为 '123456'（若未指定），用户默认状态为启用。
     *
     * 注意：导入操作不强制密码强度策略，使用默认密码时需后续引导用户修改。
     *
     * @param array $data 用户数据数组，每项包含 username, password, nickname, email, mobile, gender
     * @param int $currentUserId 当前登录用户ID
     * @return array ['success' => bool, 'data' => ['success_count' => int, 'fail_count' => int, 'errors' => []], 'error' => string, 'code' => int]
     */
    public function importUsers(array $data, int $currentUserId): array
    {
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
                $user->created_by = $currentUserId;
                $user->save();

                $successCount++;
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ['success' => false, 'error' => '导入失败：' . $e->getMessage(), 'code' => 400];
        }

        return [
            'success' => true,
            'data' => [
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'errors' => $errors,
            ],
        ];
    }

    /**
     * 全量更新用户部门关联
     *
     * 先删除用户的所有部门关联记录，再根据传入的部门列表重新写入。
     * 校验必须且仅指定一个主部门，所有部门ID必须存在且启用。
     * 主部门ID同步更新到用户表的 dept_id 字段。
     *
     * @param int $userId 用户ID
     * @param array $depts 部门关联列表，每项含 dept_id, is_primary, sort
     * @return array ['success' => bool, 'data' => [], 'error' => string, 'code' => int]
     */
    public function updateUserDepts(int $userId, array $depts): array
    {
        $user = UserModel::find($userId);
        if (!$user) {
            return ['success' => false, 'error' => '用户不存在', 'code' => 404];
        }

        if (empty($depts)) {
            return ['success' => false, 'error' => '必须指定至少一个部门', 'code' => 422];
        }

        $primaryValidation = $this->validatePrimaryDept($depts);
        if (!$primaryValidation['valid']) {
            return ['success' => false, 'error' => $primaryValidation['error'], 'code' => 422];
        }

        $deptValidation = $this->validateDeptIds(array_column($depts, 'dept_id'));
        if (!$deptValidation['valid']) {
            return ['success' => false, 'error' => $deptValidation['error'], 'code' => 422];
        }

        Db::startTrans();
        try {
            Db::name('user_dept')->where('user_id', $userId)->delete();

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
            Db::name('user_dept')->insertAll($insertData);

            $user->dept_id = $primaryValidation['primary_dept_id'];
            $user->save();

            Db::commit();
            return ['success' => true, 'data' => []];
        } catch (\Exception $e) {
            Db::rollback();
            return ['success' => false, 'error' => '更新部门关联失败：' . $e->getMessage(), 'code' => 400];
        }
    }

    /**
     * 追加用户部门关联
     *
     * 在用户现有部门关联基础上追加新的部门，不影响已有关联。
     * 不重复添加已关联的部门；若用户已有主部门，不能再设置新的主部门。
     * 若新增的关联中包含主部门，同步更新用户表的 dept_id 字段。
     *
     * @param int $userId 用户ID
     * @param array $depts 部门关联列表，每项含 dept_id, is_primary, sort
     * @return array ['success' => bool, 'data' => [], 'error' => string, 'code' => int]
     */
    public function addUserDepts(int $userId, array $depts): array
    {
        $user = UserModel::find($userId);
        if (!$user) {
            return ['success' => false, 'error' => '用户不存在', 'code' => 404];
        }

        if (empty($depts)) {
            return ['success' => false, 'error' => '部门列表不能为空', 'code' => 422];
        }

        $existingDepts = Db::name('user_dept')->where('user_id', $userId)->column('dept_id');
        $hasPrimary = Db::name('user_dept')->where('user_id', $userId)->where('is_primary', 1)->count() > 0;

        $insertData = [];
        foreach ($depts as $index => $dept) {
            if (in_array($dept['dept_id'], $existingDepts)) {
                continue;
            }

            $isPrimary = !empty($dept['is_primary']) ? 1 : 0;
            if ($isPrimary && $hasPrimary) {
                return ['success' => false, 'error' => '用户已有主部门，不能重复设置', 'code' => 422];
            }

            $insertData[] = [
                'user_id' => $userId,
                'dept_id' => $dept['dept_id'],
                'is_primary' => $isPrimary,
                'sort' => $dept['sort'] ?? $index,
                'create_time' => date('Y-m-d H:i:s'),
            ];
        }

        if (empty($insertData)) {
            return ['success' => false, 'error' => '所有部门关联已存在', 'code' => 422];
        }

        $deptValidation = $this->validateDeptIds(array_column($insertData, 'dept_id'));
        if (!$deptValidation['valid']) {
            return ['success' => false, 'error' => $deptValidation['error'], 'code' => 422];
        }

        Db::startTrans();
        try {
            Db::name('user_dept')->insertAll($insertData);

            $primaryRecord = array_filter($insertData, fn($r) => $r['is_primary'] == 1);
            if (!empty($primaryRecord)) {
                $user->dept_id = reset($primaryRecord)['dept_id'];
                $user->save();
            }

            Db::commit();
            return ['success' => true, 'data' => []];
        } catch (\Exception $e) {
            Db::rollback();
            return ['success' => false, 'error' => '添加部门关联失败：' . $e->getMessage(), 'code' => 400];
        }
    }

    /**
     * 移除用户部门关联
     *
     * 移除用户的某个兼职部门关联。禁止移除主部门，需先通过 updateUserDepts
     * 将其他部门设为主部门后才能移除原主部门。
     *
     * @param int $userId 用户ID
     * @param int $deptId 待移除的部门ID
     * @return array ['success' => bool, 'data' => [], 'error' => string, 'code' => int]
     */
    public function removeUserDept(int $userId, int $deptId): array
    {
        $user = UserModel::find($userId);
        if (!$user) {
            return ['success' => false, 'error' => '用户不存在', 'code' => 404];
        }

        $relation = Db::name('user_dept')->where('user_id', $userId)->where('dept_id', $deptId)->find();
        if (!$relation) {
            return ['success' => false, 'error' => '该用户不在此部门中', 'code' => 404];
        }

        if ($relation['is_primary'] == 1) {
            return ['success' => false, 'error' => '不能移除主部门，请先设置其他部门为主部门', 'code' => 422];
        }

        Db::startTrans();
        try {
            Db::name('user_dept')->where('user_id', $userId)->where('dept_id', $deptId)->delete();
            Db::commit();
            return ['success' => true, 'data' => []];
        } catch (\Exception $e) {
            Db::rollback();
            return ['success' => false, 'error' => '移除部门关联失败：' . $e->getMessage(), 'code' => 400];
        }
    }

    /**
     * 校验密码强度
     *
     * 密码策略：至少8位，必须包含大写字母、小写字母、数字和特殊字符。
     *
     * @param string $password 待校验的密码
     * @return bool 是否满足强度要求
     */
    protected function validatePasswordStrength(string $password): bool
    {
        return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
    }

    /**
     * 校验主部门唯一性
     *
     * @param array $depts 部门关联列表
     * @return array ['valid' => bool, 'error' => string, 'primary_dept_id' => int|null]
     */
    protected function validatePrimaryDept(array $depts): array
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
            return ['valid' => false, 'error' => '必须指定一个主部门', 'primary_dept_id' => null];
        }
        if ($primaryCount > 1) {
            return ['valid' => false, 'error' => '只能有一个主部门', 'primary_dept_id' => null];
        }

        return ['valid' => true, 'error' => '', 'primary_dept_id' => $primaryDeptId];
    }

    /**
     * 校验部门ID有效性
     *
     * 所有部门ID必须存在、启用且未软删除。
     *
     * @param array $deptIds 部门ID列表
     * @return array ['valid' => bool, 'error' => string]
     */
    protected function validateDeptIds(array $deptIds): array
    {
        $existDeptIds = Department::whereIn('id', $deptIds)->where('status', 1)->whereNull('delete_time')->column('id');
        $invalidIds = array_diff($deptIds, $existDeptIds);
        if (!empty($invalidIds)) {
            return ['valid' => false, 'error' => '以下部门不存在或已禁用：' . implode(',', $invalidIds)];
        }
        return ['valid' => true, 'error' => ''];
    }

    /**
     * 同步用户部门关联（全量替换）
     *
     * 删除用户的所有部门关联记录，根据传入的部门列表重新写入。
     * 该方法在事务上下文中被调用，不自行管理事务。
     *
     * @param int $userId 用户ID
     * @param array $depts 部门关联列表，每项含 dept_id, is_primary, sort
     * @return void
     * @throws \Exception 主部门数量不合法时抛出异常
     */
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

        Db::name('user_dept')->where('user_id', $userId)->delete();

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
        Db::name('user_dept')->insertAll($insertData);

        Db::name('user')->where('id', $userId)->update(['dept_id' => $primaryDeptId]);
    }

    /**
     * 分配用户角色（内部方法，全量替换模式）
     *
     * 先删除用户的所有角色关联，再根据传入的角色ID列表重新写入。
     * 该方法自行管理事务。
     *
     * @param int $userId 用户ID
     * @param array $roleIds 角色 ID 列表
     * @return bool 分配成功返回 true，失败返回 false
     */
    protected function assignRolesInternal(int $userId, array $roleIds): bool
    {
        Db::startTrans();
        try {
            Db::name('user_role')->where('user_id', $userId)->delete();

            if (!empty($roleIds)) {
                $insertData = array_map(function ($roleId) use ($userId) {
                    return [
                        'user_id' => $userId,
                        'role_id' => $roleId,
                        'create_time' => date('Y-m-d H:i:s'),
                    ];
                }, $roleIds);
                Db::name('user_role')->insertAll($insertData);
            }

            Db::commit();
            return true;
        } catch (\Exception) {
            Db::rollback();
            return false;
        }
    }

    /**
     * 构建列表查询条件
     *
     * 支持关键词模糊搜索、状态/性别精确筛选、部门筛选（含兼职部门）。
     *
     * @param array $params 查询参数
     * @return array 查询条件数组
     */
    protected function buildListConditions(array $params): array
    {
        $where = [];

        if (!empty($params['keyword'])) {
            $where[] = ['username|nickname|email|mobile', 'like', "%{$params['keyword']}%"];
        }

        if ($params['status'] !== null && $params['status'] !== '') {
            $where[] = ['status', '=', (int) $params['status']];
        }

        if ($params['dept_id'] !== null && $params['dept_id'] !== '') {
            $deptUserIds = Db::name('user_dept')
                ->where('dept_id', (int) $params['dept_id'])
                ->column('user_id');

            $deptUserIds = array_unique($deptUserIds);

            $where[] = function ($query) use ($params, $deptUserIds) {
                $query->where('dept_id', '=', (int) $params['dept_id']);
                if (!empty($deptUserIds)) {
                    $query->whereOr('id', 'in', $deptUserIds);
                }
            };
        }

        if ($params['gender'] !== null && $params['gender'] !== '') {
            $where[] = ['gender', '=', (int) $params['gender']];
        }

        return $where;
    }

    /**
     * 批量预加载用户关联数据（角色、部门）
     *
     * 为用户列表批量加载角色和部门关联信息，避免 N+1 查询问题。
     * 所有关联数据通过3次批量查询获取，而非逐条查询。
     *
     * @param array $list 用户列表数组
     * @return array 增强后的用户列表，每项额外包含 roles, dept_name, depts
     */
    protected function enrichUserListWithRelations(array $list): array
    {
        if (empty($list)) {
            return $list;
        }

        $userIds = array_column($list, 'id');
        $deptIds = array_filter(array_column($list, 'dept_id'));

        $rolesByUser = $this->batchLoadUserRoles($userIds);
        $deptMap = $this->batchLoadDeptNames($deptIds);
        $deptsByUser = $this->batchLoadUserDepts($userIds);

        foreach ($list as &$item) {
            $item['roles'] = $rolesByUser[$item['id']] ?? [];
            $item['dept_name'] = !empty($item['dept_id']) ? ($deptMap[$item['dept_id']] ?? '') : '';
            $item['depts'] = $deptsByUser[$item['id']] ?? [];
        }

        return $list;
    }

    /**
     * 批量加载用户角色映射
     *
     * @param array $userIds 用户ID列表
     * @return array 按用户ID分组的角色列表，格式为 [userId => [role, ...]]
     */
    protected function batchLoadUserRoles(array $userIds): array
    {
        $rolesByUser = [];

        if (empty($userIds)) {
            return $rolesByUser;
        }

        $userRoleMap = Db::name('user_role')
            ->whereIn('user_id', $userIds)
            ->select()
            ->toArray();

        if (empty($userRoleMap)) {
            return $rolesByUser;
        }

        $allRoleIds = array_unique(array_column($userRoleMap, 'role_id'));
        $rolesData = Db::name('role')
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

        return $rolesByUser;
    }

    /**
     * 批量加载部门名称映射
     *
     * @param array $deptIds 部门ID列表
     * @return array 部门ID到名称的映射，格式为 [deptId => deptName]
     */
    protected function batchLoadDeptNames(array $deptIds): array
    {
        $deptMap = [];

        if (empty($deptIds)) {
            return $deptMap;
        }

        $depts = Department::whereIn('id', $deptIds)->select()->toArray();
        foreach ($depts as $dept) {
            $deptMap[$dept['id']] = $dept['name'];
        }

        return $deptMap;
    }

    /**
     * 批量加载用户-部门关联
     *
     * @param array $userIds 用户ID列表
     * @return array 按用户ID分组的部门关联列表，格式为 [userId => [{dept_id, dept_name, is_primary, sort}, ...]]
     */
    protected function batchLoadUserDepts(array $userIds): array
    {
        $deptsByUser = [];

        if (empty($userIds)) {
            return $deptsByUser;
        }

        $userDeptList = Db::name('user_dept')
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

        return $deptsByUser;
    }

    /**
     * 应用数据权限过滤
     *
     * 根据当前用户的权限级别，向查询条件中追加数据范围限制：
     *   - 超级管理员：不追加任何过滤条件
     *   - 仅本人权限：仅可查看自身记录
     *   - 部门级权限：可查看管辖部门下的用户（含主部门和兼职部门）
     *
     * @param array &$where 查询条件数组（引用传递，直接修改）
     * @param AdminAuth $auth 管理员权限认证实例
     * @return void
     */
    public function applyDataScopeFilter(array &$where, AdminAuth $auth): void
    {
        if ($auth->isSuperAdmin()) {
            return;
        }

        if ($auth->isSelfOnly()) {
            $where[] = ['id', '=', $auth->getUserId()];
            return;
        }

        $scopedDeptIds = $auth->getScopedDeptIds();
        if (empty($scopedDeptIds)) {
            return;
        }

        $scopedUserIds = Db::name('user_dept')
            ->whereIn('dept_id', $scopedDeptIds)
            ->column('user_id');
        $scopedUserIds = array_unique($scopedUserIds);

        $where[] = function ($query) use ($scopedDeptIds, $scopedUserIds) {
            $query->where('dept_id', 'in', $scopedDeptIds);
            if (!empty($scopedUserIds)) {
                $query->whereOr('id', 'in', $scopedUserIds);
            }
        };
    }
}
