<?php

namespace app\admin\service;

use app\model\Api as ApiModel;
use app\model\Menu;

/**
 * 接口管理服务
 *
 * 负责处理 API 接口全生命周期的核心业务逻辑，包括接口的增删改查、
 * 状态切换、分组查询及按菜单关联查询等功能。从 ApiController 中提取，
 * 遵循单一职责原则。
 *
 * 设计思路：
 *   - 采用单例模式，与项目现有 Service 层保持一致
 *   - 返回统一结果结构 ['success' => bool, 'data' => ..., 'error' => ..., 'code' => int]
 *   - 唯一性约束：接口标识（code）全局唯一，HTTP方法+路径（method+path）组合唯一
 *   - 列表查询支持多维度筛选：关键词、状态、菜单、方法、分组
 *   - 查询结果自动附加 menu_name 字段，避免前端额外请求
 *
 * @see \app\admin\controller\ApiController 控制器层，负责请求/响应处理
 */
class ApiService
{
    private static ?ApiService $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取接口列表（分页）
     *
     * 支持多条件组合筛选，返回分页数据、接口分组列表及分页元信息。
     * 查询结果中会附加每条接口记录对应的菜单名称（menu_name）。
     *
     * @param array $params 查询参数
     *   - page    int    当前页码，默认 1
     *   - limit   int    每页条数，默认 15
     *   - keyword string 搜索关键词，模糊匹配接口名称、标识、路径
     *   - status  mixed  接口状态筛选（0=禁用，1=启用），null 或空字符串表示不过滤
     *   - menu_id mixed  所属菜单 ID 筛选，null 或空字符串表示不过滤
     *   - method  string HTTP 方法筛选（GET/POST/PUT/DELETE 等）
     *   - group   string 接口分组筛选
     *
     * @return array ['success' => bool, 'data' => [...], 'error' => string, 'code' => int]
     */
    public function getApiList(array $params): array
    {
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 15);
        $keyword = $params['keyword'] ?? '';
        $status = $params['status'] ?? null;
        $menuId = $params['menu_id'] ?? null;
        $method = $params['method'] ?? '';
        $group = $params['group'] ?? '';

        $where = $this->buildListWhere($keyword, $status, $menuId, $method, $group);

        $total = ApiModel::where($where)->count();
        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;

        $list = ApiModel::where($where)
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $this->attachMenuNames($list);

        $groups = (new ApiModel())->getAllGroups();

        return [
            'success' => true,
            'data' => [
                'list' => $list,
                'groups' => $groups,
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
     * 获取单个接口详情
     *
     * 根据接口 ID 查询详情，并附加所属菜单名称。
     *
     * @param int $id 接口 ID
     *
     * @return array ['success' => bool, 'data' => apiData, 'error' => string, 'code' => int]
     */
    public function getApiDetail(int $id): array
    {
        $api = ApiModel::find($id);
        if (!$api) {
            return ['success' => false, 'error' => '接口不存在', 'code' => 404];
        }

        $apiData = $api->toArray();
        $apiData['menu_name'] = $this->getMenuName($apiData['menu_id'] ?? null);

        return ['success' => true, 'data' => $apiData];
    }

    /**
     * 创建接口
     *
     * 执行流程：
     *   1. 校验接口标识（code）的全局唯一性
     *   2. 校验 HTTP方法+路径（method+path）组合的唯一性
     *   3. 校验关联菜单是否存在（若指定了 menu_id）
     *   4. 创建接口记录，method 字段统一转为大写存储
     *
     * @param array     $data          接口数据
     *   - name     string 接口名称（必填）
     *   - code     string 接口标识，全局唯一（必填）
     *   - method   string HTTP 方法（必填），自动转为大写
     *   - path     string 接口路径（必填），与 method 组合唯一
     *   - menu_id  int    所属菜单 ID（可选）
     *   - group    string 接口分组（可选）
     *   - status   int    状态（可选），默认 1 启用
     * @param int|null  $currentUserId 当前登录用户 ID
     *
     * @return array ['success' => bool, 'data' => ['id' => int], 'error' => string, 'code' => int]
     */
    public function createApi(array $data, ?int $currentUserId): array
    {
        $codeValidation = $this->validateCodeUnique($data['code']);
        if (!$codeValidation['valid']) {
            return ['success' => false, 'error' => $codeValidation['error'], 'code' => 422];
        }

        $methodPathValidation = $this->validateMethodPathUnique(
            strtoupper($data['method']),
            $data['path']
        );
        if (!$methodPathValidation['valid']) {
            return ['success' => false, 'error' => $methodPathValidation['error'], 'code' => 422];
        }

        if (!empty($data['menu_id'])) {
            $menuValidation = $this->validateMenuExists($data['menu_id']);
            if (!$menuValidation['valid']) {
                return ['success' => false, 'error' => $menuValidation['error'], 'code' => 422];
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
            $api->created_by = $currentUserId;
            $api->save();

            return ['success' => true, 'data' => ['id' => $api->id]];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => '创建接口失败：' . $e->getMessage(), 'code' => 400];
        }
    }

    /**
     * 更新接口
     *
     * 采用部分更新策略：仅更新请求中明确传入的字段（isset 判断），
     * 未传入的字段保持原值不变。唯一性校验时排除当前记录自身。
     *
     * @param int       $id            接口 ID
     * @param array     $data          需要更新的字段
     *   - name     string 接口名称
     *   - code     string 接口标识（全局唯一，排除自身）
     *   - method   string HTTP 方法，自动转为大写
     *   - path     string 接口路径（与 method 组合唯一，排除自身）
     *   - menu_id  int    所属菜单 ID
     *   - group    string 接口分组
     *   - status   int    状态
     * @param int|null  $currentUserId 当前登录用户 ID
     *
     * @return array ['success' => bool, 'data' => [], 'error' => string, 'code' => int]
     */
    public function updateApi(int $id, array $data, ?int $currentUserId): array
    {
        $api = ApiModel::find($id);
        if (!$api) {
            return ['success' => false, 'error' => '接口不存在', 'code' => 404];
        }

        if (isset($data['code'])) {
            $codeValidation = $this->validateCodeUnique($data['code'], $id);
            if (!$codeValidation['valid']) {
                return ['success' => false, 'error' => $codeValidation['error'], 'code' => 422];
            }
        }

        if (isset($data['method']) || isset($data['path'])) {
            $method = strtoupper($data['method'] ?? $api->method);
            $path = $data['path'] ?? $api->path;

            $methodPathValidation = $this->validateMethodPathUnique($method, $path, $id);
            if (!$methodPathValidation['valid']) {
                return ['success' => false, 'error' => $methodPathValidation['error'], 'code' => 422];
            }
        }

        if (!empty($data['menu_id'])) {
            $menuValidation = $this->validateMenuExists($data['menu_id']);
            if (!$menuValidation['valid']) {
                return ['success' => false, 'error' => $menuValidation['error'], 'code' => 422];
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

            $api->updated_by = $currentUserId;
            $api->save();

            return ['success' => true, 'data' => []];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => '更新接口失败：' . $e->getMessage(), 'code' => 400];
        }
    }

    /**
     * 删除接口
     *
     * 物理删除指定接口记录。删除前校验 ID 有效性和记录存在性。
     * 注意：当前未做关联角色权限的级联清理，若接口已关联权限策略，
     * 需在业务层确保数据一致性。
     *
     * @param int $id 接口 ID，必须大于 0
     *
     * @return array ['success' => bool, 'data' => [], 'error' => string, 'code' => int]
     */
    public function deleteApi(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => '参数错误', 'code' => 422];
        }

        $api = ApiModel::find($id);
        if (!$api) {
            return ['success' => false, 'error' => '接口不存在', 'code' => 404];
        }

        try {
            ApiModel::destroy($id);
            return ['success' => true, 'data' => []];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => '删除接口失败：' . $e->getMessage(), 'code' => 400];
        }
    }

    /**
     * 切换接口状态（启用/禁用）
     *
     * 仅更新 status 字段，不涉及其他字段的修改。
     *
     * @param int $id     接口 ID
     * @param int $status 目标状态（1=启用，0=禁用）
     *
     * @return array ['success' => bool, 'data' => [], 'error' => string, 'code' => int, 'message' => string]
     */
    public function changeStatus(int $id, int $status): array
    {
        $api = ApiModel::find($id);
        if (!$api) {
            return ['success' => false, 'error' => '接口不存在', 'code' => 404];
        }

        $api->status = $status;
        $api->save();

        return [
            'success' => true,
            'data' => [],
            'message' => $status == 1 ? '接口已启用' : '接口已禁用',
        ];
    }

    /**
     * 获取所有接口分组列表
     *
     * 返回系统中已存在的接口分组，供前端下拉选择器等场景使用。
     *
     * @return array ['success' => bool, 'data' => ['groups' => array]]
     */
    public function getGroups(): array
    {
        $groups = (new ApiModel())->getAllGroups();

        return [
            'success' => true,
            'data' => ['groups' => $groups],
        ];
    }

    /**
     * 根据菜单 ID 获取关联接口列表
     *
     * 查询指定菜单下绑定的所有接口，用于菜单-接口关联管理场景。
     * 先校验菜单是否存在，再查询关联接口。
     *
     * @param int $menuId 菜单 ID
     *
     * @return array ['success' => bool, 'data' => ['list' => array], 'error' => string, 'code' => int]
     */
    public function getApisByMenu(int $menuId): array
    {
        $menu = Menu::find($menuId);
        if (!$menu) {
            return ['success' => false, 'error' => '菜单不存在', 'code' => 404];
        }

        $apis = (new ApiModel())->getApiListByMenu($menuId);

        return [
            'success' => true,
            'data' => ['list' => $apis],
        ];
    }

    /**
     * 构建列表查询条件
     *
     * 根据传入的筛选参数动态构建 WHERE 条件数组，
     * 支持关键词模糊搜索、状态/菜单/方法/分组的精确筛选。
     *
     * @param string $keyword 搜索关键词，模糊匹配 name|code|path
     * @param mixed  $status  状态筛选
     * @param mixed  $menuId  菜单 ID 筛选
     * @param string $method  HTTP 方法筛选
     * @param string $group   分组筛选
     *
     * @return array WHERE 条件数组
     */
    protected function buildListWhere(string $keyword, $status, $menuId, string $method, string $group): array
    {
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

        return $where;
    }

    /**
     * 批量附加菜单名称
     *
     * 遍历接口列表，为每条记录附加 menu_name 字段。
     * 直接修改传入数组的引用。
     *
     * @param array &$list 接口列表数据（引用传递）
     */
    protected function attachMenuNames(array &$list): void
    {
        foreach ($list as &$item) {
            $item['menu_name'] = $this->getMenuName($item['menu_id'] ?? null);
        }
        unset($item);
    }

    /**
     * 获取菜单名称
     *
     * @param int|null $menuId 菜单 ID
     *
     * @return string 菜单名称，不存在时返回空字符串
     */
    protected function getMenuName(?int $menuId): string
    {
        if (empty($menuId)) {
            return '';
        }

        $menu = Menu::find($menuId);
        return $menu ? $menu->name : '';
    }

    /**
     * 校验接口标识唯一性
     *
     * 接口标识（code）在系统中全局唯一。更新场景下通过 $excludeId 排除自身。
     *
     * @param string   $code      接口标识
     * @param int|null $excludeId 需要排除的接口 ID（更新场景传入当前接口 ID）
     *
     * @return array ['valid' => bool, 'error' => string]
     */
    protected function validateCodeUnique(string $code, ?int $excludeId = null): array
    {
        $query = ApiModel::where('code', $code);
        if ($excludeId !== null) {
            $query->where('id', '<>', $excludeId);
        }

        if ($query->find()) {
            return ['valid' => false, 'error' => '接口标识已存在'];
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * 校验 HTTP方法+路径组合唯一性
     *
     * 同一 HTTP 方法下不允许存在相同的路径。更新场景下通过 $excludeId 排除自身。
     *
     * @param string   $method    HTTP 方法（需已转为大写）
     * @param string   $path      接口路径
     * @param int|null $excludeId 需要排除的接口 ID（更新场景传入当前接口 ID）
     *
     * @return array ['valid' => bool, 'error' => string]
     */
    protected function validateMethodPathUnique(string $method, string $path, ?int $excludeId = null): array
    {
        $query = ApiModel::where('method', $method)->where('path', $path);
        if ($excludeId !== null) {
            $query->where('id', '<>', $excludeId);
        }

        if ($query->find()) {
            return ['valid' => false, 'error' => '该接口路径已存在'];
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * 校验菜单存在性
     *
     * @param int $menuId 菜单 ID
     *
     * @return array ['valid' => bool, 'error' => string]
     */
    protected function validateMenuExists(int $menuId): array
    {
        $menu = Menu::find($menuId);
        if (!$menu) {
            return ['valid' => false, 'error' => '所属菜单不存在'];
        }

        return ['valid' => true, 'error' => ''];
    }
}
