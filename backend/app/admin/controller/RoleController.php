<?php
namespace app\admin\controller;

use app\common\BaseController;
use app\model\Role as RoleModel;
use app\common\AdminAuth;
use app\common\SimpleCache;
use app\admin\validate\RoleValidate;
use think\Request;
use think\facade\Db;

/**
 * 角色管理控制器
 *
 * @描述 提供角色的完整生命周期管理，包括增删改查、三级权限分配（菜单/按钮/接口）、
 *       数据权限配置及状态切换，是 RBAC 权限体系的核心入口之一
 *
 * @设计理念
 *   - 角色是权限的载体：用户通过关联角色间接获得菜单、按钮、接口三类权限
 *   - 权限变更即时生效：每次权限操作后主动清除相关缓存，确保下次请求拿到最新数据
 *   - 安全删除：存在用户关联时禁止删除角色，避免产生悬空权限
 *
 * @缓存策略
 *   权限数据通过 SimpleCache 缓存，键名格式：
 *   - user_menu_codes_{userId}   用户菜单权限码
 *   - user_api_codes_{userId}    用户接口权限码
 *   - user_button_codes_{userId} 用户按钮权限码
 *   - user_menu_tree_{userId}    用户菜单树
 *   角色权限变更后，遍历该角色下所有用户逐个清除上述缓存，
 *   并调用 AdminAuth::clearGlobalCache() 清除全局权限缓存
 *
 * @数据权限范围值（data_scope）
 *   1 = 全部数据
 *   2 = 本部门数据
 *   3 = 本部门及下级部门数据
 *   4 = 仅本人数据
 *   5 = 自定义部门数据（需配合 data_scope_dept_ids 字段）
 */
class RoleController extends BaseController
{
    /**
     * 获取角色列表（分页）
     *
     * @param Request $request HTTP 请求对象
     *
     * @return \think\response\Json 角色分页列表
     *
     * @业务逻辑
     *   1. 解析分页参数和筛选条件（关键词、状态）
     *   2. 关键词模糊匹配角色名称和编码
     *   3. 按排序字段升序查询角色列表
     *   4. 逐条统计每个角色关联的用户数量
     *
     * @查询参数
     *   - page (int, 可选): 页码，默认 1
     *   - limit (int, 可选): 每页条数，默认 15
     *   - keyword (string, 可选): 搜索关键词，匹配 name 或 code
     *   - status (int, 可选): 状态筛选，0=禁用 1=启用
     */
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

    /**
     * 获取角色详情
     *
     * @param int $id 角色 ID
     *
     * @return \think\response\Json 角色详细信息，含关联的菜单/按钮/接口 ID 列表
     *
     * @业务逻辑
     *   1. 查询角色基础信息
     *   2. 附加三级权限 ID 列表：menu_ids、button_ids、api_ids
     *   3. 统计角色关联的用户数量
     */
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

    /**
     * 创建角色
     *
     * @param Request $request HTTP 请求对象，需包含 userInfo 认证信息
     *
     * @return \think\response\Json 创建结果，成功时返回新角色 ID
     *
     * @throws \think\exception\ValidateException 数据验证失败时抛出
     *
     * @业务逻辑
     *   1. 参数校验：通过 RoleValidate 的 store 场景验证必填字段
     *   2. 唯一性检查：角色编码（code）不能与已有角色重复
     *   3. 事务创建角色基础信息
     *   4. 若提交了权限 ID 列表，同步分配菜单/按钮/接口权限
     *   5. 提交事务，返回新角色 ID
     *
     * @请求参数
     *   - name (string, 必填): 角色名称
     *   - code (string, 必填): 角色标识，全局唯一
     *   - data_scope (int, 可选): 数据权限范围，默认 1
     *   - data_scope_dept_ids (string, 可选): 自定义数据权限部门 ID 串
     *   - sort (int, 可选): 排序值，默认 0
     *   - status (int, 可选): 状态，默认 1
     *   - remark (string, 可选): 备注
     *   - menu_ids (array, 可选): 菜单权限 ID 列表
     *   - button_ids (array, 可选): 按钮权限 ID 列表
     *   - api_ids (array, 可选): 接口权限 ID 列表
     */
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

    /**
     * 更新角色
     *
     * @param Request $request HTTP 请求对象
     * @param int     $id      角色 ID
     *
     * @return \think\response\Json 更新结果
     *
     * @throws \think\exception\ValidateException 数据验证失败时抛出
     *
     * @业务逻辑
     *   1. 验证角色存在性
     *   2. 参数校验：通过 RoleValidate 的 update 场景验证
     *   3. 事务更新角色基础字段（仅更新提交的字段）
     *   4. 若提交了权限 ID 列表，重新分配对应权限（全量替换策略）
     *   5. 清除该角色关联用户的权限缓存
     *
     * @注意事项
     *   - 权限分配为全量替换：提交 menu_ids 会覆盖原有菜单权限
     *   - code 字段不可修改，防止破坏权限关联关系
     */
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

    /**
     * 删除角色
     *
     * @param int $id 角色 ID
     *
     * @return \think\response\Json 删除结果
     *
     * @业务逻辑
     *   1. 参数合法性校验
     *   2. 验证角色存在性
     *   3. 安全检查：存在用户关联时拒绝删除，防止产生无角色用户
     *   4. 事务删除角色及所有关联数据（role_menu、role_menu_button、role_api）
     *   5. 清除该角色关联用户的权限缓存
     *
     * @注意事项
     *   删除前必须先解除该角色与所有用户的关联，否则会返回 422 错误
     */
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

    /**
     * 分配菜单权限
     *
     * @param Request $request HTTP 请求对象
     * @param int     $id      角色 ID
     *
     * @return \think\response\Json 分配结果
     *
     * @throws \think\exception\ValidateException 数据验证失败时抛出
     *
     * @业务逻辑
     *   1. 验证角色存在性
     *   2. 参数校验：通过 RoleValidate 的 assign_menus 场景验证
     *   3. 有效性检查：验证提交的菜单 ID 是否存在且已启用
     *   4. 全量替换该角色的菜单权限
     *   5. 清除该角色关联用户的权限缓存
     *
     * @请求参数
     *   - menu_ids (array, 必填): 菜单 ID 列表，空数组表示清空菜单权限
     */
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

    /**
     * 分配按钮权限
     *
     * @param Request $request HTTP 请求对象
     * @param int     $id      角色 ID
     *
     * @return \think\response\Json 分配结果
     *
     * @throws \think\exception\ValidateException 数据验证失败时抛出
     *
     * @业务逻辑
     *   1. 验证角色存在性
     *   2. 参数校验：通过 RoleValidate 的 assign_buttons 场景验证
     *   3. 有效性检查：验证提交的按钮 ID 是否存在且已启用
     *   4. 全量替换该角色的按钮权限
     *   5. 清除该角色关联用户的权限缓存
     *
     * @请求参数
     *   - button_ids (array, 必填): 按钮 ID 列表，空数组表示清空按钮权限
     */
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

    /**
     * 分配接口权限
     *
     * @param Request $request HTTP 请求对象
     * @param int     $id      角色 ID
     *
     * @return \think\response\Json 分配结果
     *
     * @throws \think\exception\ValidateException 数据验证失败时抛出
     *
     * @业务逻辑
     *   1. 验证角色存在性
     *   2. 参数校验：通过 RoleValidate 的 assign_apis 场景验证
     *   3. 有效性检查：验证提交的接口 ID 是否存在且已启用
     *   4. 全量替换该角色的接口权限
     *   5. 清除该角色关联用户的权限缓存
     *
     * @请求参数
     *   - api_ids (array, 必填): 接口 ID 列表，空数组表示清空接口权限
     */
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

    /**
     * 配置数据权限范围
     *
     * @param Request $request HTTP 请求对象
     * @param int     $id      角色 ID
     *
     * @return \think\response\Json 配置结果
     *
     * @throws \think\exception\ValidateException 数据验证失败时抛出
     *
     * @业务逻辑
     *   1. 验证角色存在性
     *   2. 参数校验：通过 RoleValidate 的 data_scope 场景验证
     *   3. 业务约束：data_scope=5（自定义）时必须指定部门 ID
     *   4. 非 data_scope=5 时强制清空部门 ID，防止残留脏数据
     *   5. 更新角色数据权限字段
     *   6. 清除该角色关联用户的权限缓存
     *
     * @请求参数
     *   - data_scope (int, 必填): 数据权限范围值（1-5）
     *   - data_scope_dept_ids (string, 可选): 自定义部门 ID，逗号分隔
     *
     * @数据权限范围值说明
     *   1=全部数据, 2=本部门, 3=本部门及下级, 4=仅本人, 5=自定义
     */
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

    /**
     * 切换角色启用/禁用状态
     *
     * @param Request $request HTTP 请求对象
     * @param int     $id      角色 ID
     *
     * @return \think\response\Json 状态切换结果
     *
     * @throws \think\exception\ValidateException 数据验证失败时抛出
     *
     * @业务逻辑
     *   1. 验证角色存在性
     *   2. 参数校验：通过 RoleValidate 的 change_status 场景验证
     *   3. 更新角色状态字段
     *   4. 清除该角色关联用户的权限缓存
     *
     * @请求参数
     *   - status (int, 必填): 目标状态，0=禁用 1=启用
     *
     * @注意事项
     *   禁用角色后，关联用户仍可登录但无法访问该角色授权的菜单和接口
     */
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

    /**
     * 清除角色关联用户的权限缓存
     *
     * @param int $roleId 角色 ID
     *
     * @描述 角色权限变更后，遍历该角色下所有用户，逐个清除其权限缓存，
     *       并清除 AdminAuth 全局缓存，确保下次请求获取最新权限数据
     *
     * @缓存清除范围
     *   - user_menu_codes_{userId}   用户菜单权限码
     *   - user_api_codes_{userId}    用户接口权限码
     *   - user_button_codes_{userId} 用户按钮权限码
     *   - user_menu_tree_{userId}    用户菜单树
     *   - AdminAuth 全局缓存
     */
    protected function clearRoleCache(int $roleId): void
    {
        $userIds = Db::name('user_role')->where('role_id', $roleId)->column('user_id');

        foreach ($userIds as $userId) {
            SimpleCache::delete('user_menu_codes_' . $userId);
            SimpleCache::delete('user_api_codes_' . $userId);
            SimpleCache::delete('user_button_codes_' . $userId);
            SimpleCache::delete('user_menu_tree_' . $userId);
        }

        AdminAuth::clearGlobalCache();
    }
}
