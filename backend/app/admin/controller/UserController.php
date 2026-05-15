<?php
namespace app\admin\controller;

use app\common\BaseController;
use app\admin\service\UserService;
use app\admin\validate\UserValidate;
use think\Request;

/**
 * 用户管理控制器
 *
 * 负责后台用户全生命周期管理的请求接收与响应格式化。
 * 所有业务逻辑委托给 UserService 处理，控制器仅负责：
 *   - 请求参数提取与预处理
 *   - 数据验证（通过 UserValidate）
 *   - 调用 UserService 方法
 *   - 格式化统一响应
 *
 * @see \app\common\BaseController 继承的基类，提供 success()/error() 统一响应方法
 * @see \app\admin\service\UserService 用户管理服务层，处理全部业务逻辑
 * @see \app\admin\validate\UserValidate 用户数据验证器
 */
class UserController extends BaseController
{
    private UserService $userService;

    public function __construct(\think\App $app)
    {
        parent::__construct($app);
        $this->userService = UserService::getInstance();
    }

    /**
     * 获取用户列表（分页）
     *
     * @param Request $request HTTP 请求对象
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $params = [
            'page'    => (int) $request->get('page', 1),
            'limit'   => (int) $request->get('limit', 15),
            'keyword' => $request->get('keyword', ''),
            'status'  => $request->get('status'),
            'dept_id' => $request->get('dept_id'),
            'gender'  => $request->get('gender'),
        ];

        $result = $this->userService->getUserList($params, $request->userInfo['id'] ?? 0);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '获取成功');
    }

    /**
     * 获取用户详情
     *
     * @param int $id 用户ID
     * @return \think\response\Json
     */
    public function show(int $id)
    {
        $result = $this->userService->getUserDetail($id);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '获取成功');
    }

    /**
     * 创建用户
     *
     * @param Request $request HTTP 请求对象
     * @return \think\response\Json
     */
    public function store(Request $request)
    {
        $data = $request->post();

        try {
            $validate = new UserValidate();
            $validate->scene('store')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $result = $this->userService->createUser($data, $request->userInfo['id'] ?? 0);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '创建成功');
    }

    /**
     * 更新用户信息
     *
     * @param Request $request HTTP 请求对象
     * @param int $id 用户ID
     * @return \think\response\Json
     */
    public function update(Request $request, int $id)
    {
        $data = $request->put();

        try {
            $validate = new UserValidate();
            $validate->scene('update')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $result = $this->userService->updateUser($id, $data, $request->userInfo['id'] ?? 0);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '更新成功');
    }

    /**
     * 删除用户（软删除）
     *
     * @param Request $request HTTP 请求对象
     * @param int $id 用户ID
     * @return \think\response\Json
     */
    public function destroy(Request $request, int $id)
    {
        $result = $this->userService->deleteUser($id, $request->userInfo['id'] ?? 0);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '删除成功');
    }

    /**
     * 分配用户角色
     *
     * @param Request $request HTTP 请求对象
     * @param int $id 用户ID
     * @return \think\response\Json
     */
    public function assignRoles(Request $request, int $id)
    {
        $data = $request->post();

        try {
            $validate = new UserValidate();
            $validate->scene('assign_roles')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $result = $this->userService->assignRoles($id, $data['role_ids'] ?? []);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '角色分配成功');
    }

    /**
     * 重置用户密码
     *
     * @param Request $request HTTP 请求对象
     * @param int $id 用户ID
     * @return \think\response\Json
     */
    public function resetPassword(Request $request, int $id)
    {
        $data = $request->post();

        $result = $this->userService->resetPassword($id, $data['password'] ?? '');

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '密码重置成功');
    }

    /**
     * 切换用户状态（启用/禁用）
     *
     * @param Request $request HTTP 请求对象
     * @param int $id 用户ID
     * @return \think\response\Json
     */
    public function changeStatus(Request $request, int $id)
    {
        $data = $request->put();

        try {
            $validate = new UserValidate();
            $validate->scene('change_status')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $result = $this->userService->changeStatus($id, (int) $data['status'], $request->userInfo['id'] ?? 0);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        $message = $result['message'] ?? '操作成功';
        return $this->success($result['data'], $message);
    }

    /**
     * 导出用户数据
     *
     * @param Request $request HTTP 请求对象
     * @return \think\response\Json
     */
    public function export(Request $request)
    {
        $params = [
            'keyword' => $request->get('keyword', ''),
            'status'  => $request->get('status'),
            'dept_id' => $request->get('dept_id'),
        ];

        $result = $this->userService->exportUsers($params, $request->userInfo['id'] ?? 0);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '导出成功');
    }

    /**
     * 批量导入用户
     *
     * @param Request $request HTTP 请求对象
     * @return \think\response\Json
     */
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

        $result = $this->userService->importUsers($data, $request->userInfo['id'] ?? 0);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '导入完成');
    }

    /**
     * 全量更新用户部门关联
     *
     * @param Request $request HTTP 请求对象
     * @param int $id 用户ID
     * @return \think\response\Json
     */
    public function updateDepts(Request $request, int $id)
    {
        $data = $request->put();

        $result = $this->userService->updateUserDepts($id, $data['depts'] ?? []);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '更新成功');
    }

    /**
     * 追加用户部门关联
     *
     * @param Request $request HTTP 请求对象
     * @param int $id 用户ID
     * @return \think\response\Json
     */
    public function addDepts(Request $request, int $id)
    {
        $data = $request->post();

        $result = $this->userService->addUserDepts($id, $data['depts'] ?? []);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '添加成功');
    }

    /**
     * 移除用户部门关联
     *
     * @param Request $request HTTP 请求对象
     * @param int $id 用户ID
     * @param int $deptId 待移除的部门ID
     * @return \think\response\Json
     */
    public function removeDept(Request $request, int $id, int $deptId)
    {
        $result = $this->userService->removeUserDept($id, $deptId);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '移除成功');
    }
}
