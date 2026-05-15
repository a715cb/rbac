<?php
namespace app\admin\controller;

use app\common\BaseController;
use app\admin\service\ApiService;
use app\admin\validate\ApiValidate;
use think\Request;

/**
 * 接口管理控制器
 *
 * 负责 API 接口管理的请求接收与响应格式化。
 * 所有业务逻辑委托给 ApiService 处理，控制器仅负责：
 *   - 请求参数提取与预处理
 *   - 数据验证（通过 ApiValidate）
 *   - 调用 ApiService 方法
 *   - 格式化统一响应
 *
 * @see \app\common\BaseController 继承的基类，提供 success()/error() 统一响应方法
 * @see \app\admin\service\ApiService 接口管理服务层，处理全部业务逻辑
 * @see \app\admin\validate\ApiValidate 接口数据验证器
 */
class ApiController extends BaseController
{
    private ApiService $apiService;

    public function __construct(\think\App $app)
    {
        parent::__construct($app);
        $this->apiService = ApiService::getInstance();
    }

    /**
     * 获取接口列表（分页）
     *
     * @param Request $request HTTP 请求对象
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        $params = [
            'page'    => $request->get('page', 1),
            'limit'   => $request->get('limit', 15),
            'keyword' => $request->get('keyword', ''),
            'status'  => $request->get('status'),
            'menu_id' => $request->get('menu_id'),
            'method'  => $request->get('method', ''),
            'group'   => $request->get('group', ''),
        ];

        $result = $this->apiService->getApiList($params);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '获取成功');
    }

    /**
     * 获取单个接口详情
     *
     * @param int $id 接口 ID
     * @return \think\response\Json
     */
    public function show(int $id)
    {
        $result = $this->apiService->getApiDetail($id);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '获取成功');
    }

    /**
     * 创建接口
     *
     * @param Request $request HTTP 请求对象
     * @return \think\response\Json
     */
    public function store(Request $request)
    {
        $data = $request->post();

        try {
            $validate = new ApiValidate();
            $validate->scene('store')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $result = $this->apiService->createApi($data, $request->userInfo['id'] ?? null);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '创建成功');
    }

    /**
     * 更新接口
     *
     * @param Request $request HTTP 请求对象
     * @param int     $id      接口 ID
     * @return \think\response\Json
     */
    public function update(Request $request, int $id)
    {
        $data = $request->put();

        try {
            $validate = new ApiValidate();
            $validate->scene('update')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $result = $this->apiService->updateApi($id, $data, $request->userInfo['id'] ?? null);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '更新成功');
    }

    /**
     * 删除接口
     *
     * @param Request $request HTTP 请求对象
     * @param int     $id      接口 ID
     * @return \think\response\Json
     */
    public function destroy(Request $request, int $id)
    {
        $result = $this->apiService->deleteApi($id);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '删除成功');
    }

    /**
     * 切换接口状态（启用/禁用）
     *
     * @param Request $request HTTP 请求对象
     * @param int     $id      接口 ID
     * @return \think\response\Json
     */
    public function setStatus(Request $request, int $id)
    {
        $data = $request->put();

        try {
            $validate = new ApiValidate();
            $validate->scene('setStatus')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $result = $this->apiService->changeStatus($id, (int) $data['status']);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        $message = $result['message'] ?? '操作成功';
        return $this->success($result['data'], $message);
    }

    /**
     * 获取所有接口分组列表
     *
     * @return \think\response\Json
     */
    public function getGroups()
    {
        $result = $this->apiService->getGroups();

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '获取成功');
    }

    /**
     * 根据菜单 ID 获取关联接口列表
     *
     * @param int $menuId 菜单 ID
     * @return \think\response\Json
     */
    public function getByMenu(int $menuId)
    {
        $result = $this->apiService->getApisByMenu($menuId);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '获取成功');
    }
}
