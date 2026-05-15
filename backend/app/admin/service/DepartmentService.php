<?php

namespace app\admin\service;

use app\model\Department as DepartmentModel;
use app\common\AdminAuth;
use think\facade\Db;

/**
 * 部门管理服务
 *
 * 负责处理部门（组织架构）全生命周期的核心业务逻辑，包括增删改查、
 * 树形结构构建、循环引用防护、状态切换、排序调整以及部门成员查询。
 * 从 DepartmentController 中提取，遵循单一职责原则。
 *
 * 设计思路：
 *   - 采用单例模式，与项目现有 Service 层保持一致
 *   - 返回统一结果结构 ['success' => bool, 'data' => ..., 'error' => ..., 'code' => int]
 *   - 循环引用防护：更新父部门时递归检查后代，防止形成环形结构
 *   - 删除策略分层：子部门/主部门用户阻断，兼职用户关联自动清理
 *   - 涉及结构变更时主动清除 AdminAuth 缓存，确保权限数据一致性
 *
 * @see \app\admin\controller\DepartmentController 控制器层，负责请求/响应处理
 */
class DepartmentService
{
    private static ?DepartmentService $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取部门列表（树形结构，支持搜索和状态筛选）
     *
     * 执行流程：关键词校验 → 构建树形查询条件 → 调用 Model 层获取树形数据
     *
     * @param string $keyword 搜索关键词，trim 后不超过50字符
     * @param mixed $status 状态筛选，null 或空字符串表示不过滤
     * @return array ['success' => bool, 'data' => ['list' => array], 'error' => string, 'code' => int]
     */
    public function getDepartmentList(string $keyword, $status): array
    {
        $validation = $this->validateKeyword($keyword);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error'], 'code' => 422];
        }

        $statusFilter = $status !== null && $status !== '' ? (int) $status : null;

        $tree = (new DepartmentModel())->getDepartmentTree($statusFilter, trim($keyword));

        return [
            'success' => true,
            'data' => ['list' => $tree],
        ];
    }

    /**
     * 获取部门树形数据（轻量接口，仅支持状态筛选）
     *
     * 与 getDepartmentList 的区别：不支持关键词搜索，
     * 适用于下拉选择器等仅需树形结构的场景。
     *
     * @param mixed $status 状态筛选，null 或空字符串表示不过滤
     * @return array ['success' => bool, 'data' => ['tree' => array], 'error' => string, 'code' => int]
     */
    public function getDepartmentTree($status): array
    {
        $statusFilter = $status !== null && $status !== '' ? (int) $status : null;

        $tree = (new DepartmentModel())->getDepartmentTree($statusFilter);

        return [
            'success' => true,
            'data' => ['tree' => $tree],
        ];
    }

    /**
     * 获取部门详情
     *
     * 查询部门基本信息，当 parent_id > 0 时附加 parent_name 字段，
     * 便于前端直接展示所属上级部门名称。
     *
     * @param int $id 部门ID
     * @return array ['success' => bool, 'data' => deptData, 'error' => string, 'code' => int]
     */
    public function getDepartmentDetail(int $id): array
    {
        $department = DepartmentModel::find($id);
        if (!$department) {
            return ['success' => false, 'error' => '部门不存在', 'code' => 404];
        }

        $deptData = $department->toArray();

        $deptData['parent_name'] = $this->getParentName($deptData['parent_id']);

        return ['success' => true, 'data' => $deptData];
    }

    /**
     * 创建部门
     *
     * 执行流程：编码唯一性校验 → 父部门存在性校验 → 创建部门记录 → 记录创建人
     *
     * @param array $data 部门数据，包含 name(必填), code(必填), parent_id, leader, phone, email, sort, status
     * @param int|null $currentUserId 当前登录用户ID
     * @return array ['success' => bool, 'data' => ['id' => int], 'error' => string, 'code' => int]
     */
    public function createDepartment(array $data, ?int $currentUserId): array
    {
        $codeValidation = $this->validateCodeUnique($data['code']);
        if (!$codeValidation['valid']) {
            return ['success' => false, 'error' => $codeValidation['error'], 'code' => 422];
        }

        $parentId = (int) ($data['parent_id'] ?? 0);
        $parentValidation = $this->validateParentDept($parentId);
        if (!$parentValidation['valid']) {
            return ['success' => false, 'error' => $parentValidation['error'], 'code' => 422];
        }

        try {
            $department = new DepartmentModel();
            $department->parent_id = $parentId;
            $department->name = $data['name'];
            $department->code = $data['code'];
            $department->leader = $data['leader'] ?? '';
            $department->phone = $data['phone'] ?? '';
            $department->email = $data['email'] ?? '';
            $department->sort = $data['sort'] ?? 0;
            $department->status = $data['status'] ?? 1;
            $department->created_by = $currentUserId;
            $department->save();

            return ['success' => true, 'data' => ['id' => $department->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => '创建部门失败：' . $e->getMessage(), 'code' => 400];
        }
    }

    /**
     * 更新部门信息
     *
     * 执行流程：部门存在性校验 → 编码唯一性校验（排除自身）→
     * 循环引用防护 → 部分更新字段 → 清除权限缓存
     *
     * 循环引用防护策略：
     *   1. 禁止将自己设为父部门
     *   2. 递归查询所有后代部门ID，禁止将后代设为父部门
     *
     * @param int $id 部门ID
     * @param array $data 需要更新的字段
     * @param int|null $currentUserId 当前登录用户ID
     * @return array ['success' => bool, 'data' => [], 'error' => string, 'code' => int]
     *
     * 风险提示：
     *   - getDescendantDeptIds() 递归查询所有后代ID，部门层级过深时可能存在性能问题
     *   - 修改 parent_id 可能导致整棵子树的位置变更
     */
    public function updateDepartment(int $id, array $data, ?int $currentUserId): array
    {
        $department = DepartmentModel::find($id);
        if (!$department) {
            return ['success' => false, 'error' => '部门不存在', 'code' => 404];
        }

        if (isset($data['code'])) {
            $codeValidation = $this->validateCodeUnique($data['code'], $id);
            if (!$codeValidation['valid']) {
                return ['success' => false, 'error' => $codeValidation['error'], 'code' => 422];
            }
        }

        if (isset($data['parent_id']) && (int) $data['parent_id'] > 0) {
            $parentId = (int) $data['parent_id'];

            if ($parentId === $id) {
                return ['success' => false, 'error' => '不能将自己设置为父部门', 'code' => 422];
            }

            $descendantIds = (new DepartmentModel())->getDescendantDeptIds($id);
            if (in_array($parentId, $descendantIds)) {
                return ['success' => false, 'error' => '不能将父部门设置为自己的子部门', 'code' => 422];
            }

            $parentDept = DepartmentModel::find($parentId);
            if (!$parentDept) {
                return ['success' => false, 'error' => '父部门不存在', 'code' => 422];
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

            $department->updated_by = $currentUserId;
            $department->save();

            AdminAuth::instance()->clearCache();
            return ['success' => true, 'data' => []];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => '更新部门失败：' . $e->getMessage(), 'code' => 400];
        }
    }

    /**
     * 删除部门
     *
     * 删除策略（分层处理）：
     *   1. 存在子部门 → 拒绝删除，需先处理子部门
     *   2. 存在主部门用户（is_primary=1）→ 拒绝删除，需先转移用户
     *   3. 存在兼职用户（is_primary=0）→ 自动解除关联关系
     *   4. 使用数据库事务保证关联清理与部门删除的原子性
     *
     * @param int $id 部门ID
     * @return array ['success' => bool, 'data' => [], 'error' => string, 'code' => int]
     *
     * 风险提示：
     *   - 兼职用户关联会被静默删除，不会通知用户
     *   - 删除后清除权限缓存，避免已删除部门仍被权限系统引用
     */
    public function deleteDepartment(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => '参数错误', 'code' => 422];
        }

        $department = DepartmentModel::find($id);
        if (!$department) {
            return ['success' => false, 'error' => '部门不存在', 'code' => 404];
        }

        $children = DepartmentModel::where('parent_id', $id)->count();
        if ($children > 0) {
            return ['success' => false, 'error' => '该部门存在子部门，无法删除', 'code' => 422];
        }

        $primaryUserCount = Db::name('user_dept')
            ->where('dept_id', $id)
            ->where('is_primary', 1)
            ->count();
        if ($primaryUserCount > 0) {
            return ['success' => false, 'error' => '该部门下存在主部门用户，请先转移', 'code' => 422];
        }

        Db::startTrans();
        try {
            Db::name('user_dept')
            ->where('dept_id', $id)
            ->where('is_primary', 0)
            ->delete();

            DepartmentModel::destroy($id);
            Db::commit();
            AdminAuth::instance()->clearCache();
            return ['success' => true, 'data' => []];
        } catch (\Exception $e) {
            Db::rollback();
            return ['success' => false, 'error' => '删除部门失败：' . $e->getMessage(), 'code' => 400];
        }
    }

    /**
     * 切换部门状态（启用/禁用）
     *
     * 禁用部门后，该部门下的用户仍可登录系统，但部门相关的权限分配可能受影响。
     * 更新后清除权限缓存以确保权限判断使用最新状态。
     *
     * @param int $id 部门ID
     * @param int $status 目标状态（1=启用 0=禁用）
     * @return array ['success' => bool, 'data' => [], 'error' => string, 'code' => int, 'message' => string]
     */
    public function changeStatus(int $id, int $status): array
    {
        $department = DepartmentModel::find($id);
        if (!$department) {
            return ['success' => false, 'error' => '部门不存在', 'code' => 404];
        }

        $department->status = $status;
        $department->save();

        AdminAuth::instance()->clearCache();
        return [
            'success' => true,
            'data' => [],
            'message' => $status == 1 ? '部门已启用' : '部门已禁用',
        ];
    }

    /**
     * 设置部门排序值
     *
     * @param int $id 部门ID
     * @param int $sort 排序值，数值越小越靠前
     * @return array ['success' => bool, 'data' => [], 'error' => string, 'code' => int]
     */
    public function changeSort(int $id, int $sort): array
    {
        $department = DepartmentModel::find($id);
        if (!$department) {
            return ['success' => false, 'error' => '部门不存在', 'code' => 404];
        }

        $department->sort = $sort;
        $department->save();

        return ['success' => true, 'data' => []];
    }

    /**
     * 获取部门下的用户列表（含主部门与兼职用户）
     *
     * 查询逻辑：
     *   1. 从中间表 sys_user_dept 查询该部门关联的所有用户ID及 is_primary 标识
     *   2. 构建 isPrimaryMap 映射表，用于批量附加主部门标识
     *   3. 查询用户表，过滤已禁用和已软删除的用户
     *   4. 将 is_primary 字段合并到用户数据中返回
     *
     * @param int $id 部门ID
     * @return array ['success' => bool, 'data' => ['list' => array], 'error' => string, 'code' => int]
     */
    public function getDepartmentUsers(int $id): array
    {
        $department = DepartmentModel::find($id);
        if (!$department) {
            return ['success' => false, 'error' => '部门不存在', 'code' => 404];
        }

        $userDepts = Db::name('user_dept')
            ->where('dept_id', $id)
            ->select()
            ->toArray();

        if (empty($userDepts)) {
            return ['success' => true, 'data' => ['list' => []]];
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

        return ['success' => true, 'data' => ['list' => $users]];
    }

    /**
     * 校验搜索关键词
     *
     * 双重长度校验：原始输入不超过200字符，trim 后不超过50字符。
     * 防止超长输入导致的性能问题或潜在安全风险。
     *
     * @param string $keyword 原始搜索关键词
     * @return array ['valid' => bool, 'error' => string]
     */
    protected function validateKeyword(string $keyword): array
    {
        if (mb_strlen($keyword) > 200) {
            return ['valid' => false, 'error' => '搜索关键词长度不能超过200个字符'];
        }

        if (mb_strlen(trim($keyword)) > 50) {
            return ['valid' => false, 'error' => '搜索关键词长度不能超过50个字符'];
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * 校验部门编码唯一性
     *
     * 部门编码（code）在系统中全局唯一。更新场景下通过 $excludeId 排除自身。
     *
     * @param string $code 部门编码
     * @param int|null $excludeId 需要排除的部门ID（更新场景传入当前部门ID）
     * @return array ['valid' => bool, 'error' => string]
     */
    protected function validateCodeUnique(string $code, ?int $excludeId = null): array
    {
        $query = DepartmentModel::where('code', $code);
        if ($excludeId !== null) {
            $query->where('id', '<>', $excludeId);
        }

        if ($query->find()) {
            return ['valid' => false, 'error' => '部门编码已存在'];
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * 校验父部门有效性
     *
     * 当 parent_id > 0 时，验证父部门必须存在。
     * 循环引用防护在 updateDepartment 方法中单独处理，
     * 因为创建时不存在循环引用的可能（新部门没有子部门）。
     *
     * @param int $parentId 父部门ID
     * @return array ['valid' => bool, 'error' => string]
     */
    protected function validateParentDept(int $parentId): array
    {
        if ($parentId > 0) {
            $parentDept = DepartmentModel::find($parentId);
            if (!$parentDept) {
                return ['valid' => false, 'error' => '父部门不存在'];
            }
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * 获取父部门名称
     *
     * @param int $parentId 父部门ID
     * @return string 父部门名称，不存在或为顶级部门时返回空字符串
     */
    protected function getParentName(int $parentId): string
    {
        if ($parentId <= 0) {
            return '';
        }

        $parentDept = DepartmentModel::find($parentId);
        return $parentDept ? $parentDept->name : '';
    }
}
