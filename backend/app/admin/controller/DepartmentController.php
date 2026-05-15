<?php
namespace app\admin\controller;

use app\common\BaseController;
use app\admin\service\DepartmentService;
use app\admin\validate\DepartmentValidate;
use think\Request;

/**
 * 部门管理控制器
 *
 * 负责部门（组织架构）管理的请求接收与响应格式化。
 * 所有业务逻辑委托给 DepartmentService 处理，控制器仅负责：
 *   - 请求参数提取与预处理
 *   - 数据验证（通过 DepartmentValidate）
 *   - 调用 DepartmentService 方法
 *   - 格式化统一响应
 *
 * @see \app\common\BaseController 继承的基类，提供 success()/error() 统一响应方法
 * @see \app\admin\service\DepartmentService 部门管理服务层，处理全部业务逻辑
 * @see \app\admin\validate\DepartmentValidate 部门数据验证器
 */
class DepartmentController extends BaseController
{
    private DepartmentService $departmentService;

    public function __construct(\think\App $app)
    {
        parent::__construct($app);
        $this->departmentService = DepartmentService::getInstance();
    }

    /**
     * 获取部门列表（树形结构，支持搜索和状态筛选）
     *
     * @param Request $request HTTP 请求对象
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $keyword = $request->get('keyword', '');
        $status = $request->get('status');

        $result = $this->departmentService->getDepartmentList($keyword, $status);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '获取成功');
    }

    /**
     * 获取部门树形数据（轻量接口，仅支持状态筛选）
     *
     * @param Request $request HTTP 请求对象
     * @return \think\response\Json
     */
    public function tree(Request $request)
    {
        $status = $request->get('status');

        $result = $this->departmentService->getDepartmentTree($status);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '获取成功');
    }

    /**
     * 获取单个部门详情
     *
     * @param int $id 部门ID
     * @return \think\response\Json
     */
    public function show(int $id)
    {
        $result = $this->departmentService->getDepartmentDetail($id);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '获取成功');
    }

    /**
     * 创建部门
     *
     * @param Request $request HTTP 请求对象
     * @return \think\response\Json
     */
    public function store(Request $request)
    {
        $data = $request->post();

        try {
            $validate = new DepartmentValidate();
            $validate->scene('store')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $result = $this->departmentService->createDepartment($data, $request->userInfo['id'] ?? null);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '创建成功');
    }

    /**
     * 更新部门信息
     *
     * @param Request $request HTTP 请求对象
     * @param int $id 部门ID
     * @return \think\response\Json
     */
    public function update(Request $request, int $id)
    {
        $data = $request->put();

        try {
            $validate = new DepartmentValidate();
            $validate->scene('update')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $result = $this->departmentService->updateDepartment($id, $data, $request->userInfo['id'] ?? null);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '更新成功');
    }

    /**
     * 删除部门
     *
     * @param Request $request HTTP 请求对象
     * @param int $id 部门ID
     * @return \think\response\Json
     */
    public function destroy(Request $request, int $id)
    {
        $result = $this->departmentService->deleteDepartment($id);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '删除成功');
    }

    /**
     * 切换部门状态（启用/禁用）
     *
     * @param Request $request HTTP 请求对象
     * @param int $id 部门ID
     * @return \think\response\Json
     */
    public function setStatus(Request $request, int $id)
    {
        $data = $request->put();

        try {
            $validate = new DepartmentValidate();
            $validate->scene('setStatus')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $result = $this->departmentService->changeStatus($id, (int) $data['status']);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        $message = $result['message'] ?? '操作成功';
        return $this->success($result['data'], $message);
    }

    /**
     * 设置部门排序值
     *
     * @param Request $request HTTP 请求对象
     * @param int $id 部门ID
     * @return \think\response\Json
     */
    public function setSort(Request $request, int $id)
    {
        $data = $request->put();

        try {
            $validate = new DepartmentValidate();
            $validate->scene('setSort')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $result = $this->departmentService->changeSort($id, (int) $data['sort']);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '排序更新成功');
    }

    /**
     * 获取部门下的用户列表（含主部门与兼职用户）
     *
     * @param int $id 部门ID
     * @return \think\response\Json
     */
    public function getUsers(int $id)
    {
        $result = $this->departmentService->getDepartmentUsers($id);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '获取成功');
    }
}
