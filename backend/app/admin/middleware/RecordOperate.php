<?php
/**
 * @file RecordOperate.php
 * @purpose 后台操作日志记录中间件
 * @description 在请求处理后，根据路径和 HTTP 方法判断是否需要记录操作日志，
 *              通过事件机制将操作信息异步写入数据库。支持路径排除、GET 请求白名单、
 *              参数截断、响应结果提取等功能。
 * @note 本中间件采用后置模式（先执行控制器再记录日志），日志写入通过 Event 异步触发，
 *       不影响主请求的响应性能
 */

namespace app\admin\middleware;

use think\Request;
use think\facade\Event;
use think\facade\Log;
use think\Response;

/**
 * 操作日志记录中间件
 *
 * 拦截后台请求，在控制器执行完毕后根据规则判断是否记录操作日志。
 * 日志内容包括：操作人、模块、动作、请求参数、响应结果、IP 地址等。
 * 通过 ThinkPHP 事件机制触发写入，实现与主业务逻辑的解耦。
 */
class RecordOperate
{
    /**
     * 排除记录的路径列表
     *
     * 这些路径的操作不记录日志，通常为高频查询或日志自身管理接口，
     * 避免产生大量无意义日志或递归写入。
     *
     * @var array<string>
     */
    protected array $excludePaths = [
        '/admin/login',
        '/admin/logout',
        '/admin/refresh-token',
        '/admin/operation-logs',
        '/admin/operation-logs/stats',
        '/admin/operation-logs/clean',
        '/admin/operation-logs/clear',
        '/admin/operation-logs/delete',
        '/admin/login-logs',
        '/admin/login-logs/stats',
        '/admin/login-logs/clean',
        '/admin/login-logs/clear',
        '/admin/login-logs/delete',
    ];

    /**
     * 排除记录的 HTTP 方法列表
     *
     * OPTIONS 请求为浏览器预检请求，无需记录。
     *
     * @var array<string>
     */
    protected array $excludeMethods = [
        'OPTIONS',
    ];

    /**
     * 需要记录日志的 GET 请求路径白名单
     *
     * 默认情况下 GET 请求不记录日志（多为查询操作），
     * 仅此列表中的 GET 路径（如导出接口）会被记录。
     *
     * @var array<string>
     */
    protected array $recordGetPaths = [
        '/admin/users/export',
        '/admin/operation-logs/export',
        '/admin/login-logs/export',
        '/admin/dict/export',
    ];

    /**
     * 处理请求：后置记录操作日志
     *
     * @param Request $request 当前请求对象
     * @param \Closure $next 下一个中间件或控制器处理函数
     * @return Response 控制器的原始响应，本中间件不修改响应内容
     *
     * 处理流程：
     *   1. 先执行后续中间件和控制器，获取响应
     *   2. 判断当前请求路径和方法是否需要记录日志
     *   3. 若需要记录，调用 recordLog() 组装日志数据并通过事件触发写入
     *   4. 日志记录失败时仅写入错误日志，不影响主请求响应
     */
    public function handle(Request $request, \Closure $next)
    {
        $response = $next($request);

        $path = '/' . trim($request->pathinfo(), '/');
        $method = strtoupper($request->method());

        if ($this->shouldRecord($path, $method)) {
            try {
                $this->recordLog($request, $response);
            } catch (\Exception $e) {
                Log::error('操作日志记录失败: ' . $e->getMessage(), [
                    'path' => $path,
                    'method' => $method,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return $response;
    }

    /**
     * 判断当前请求是否需要记录操作日志
     *
     * @param string $path 标准化后的请求路径，如 /admin/users
     * @param string $method HTTP 方法（大写），如 POST、GET、DELETE
     * @return bool true 表示需要记录，false 表示跳过
     *
     * 判断规则（按优先级）：
     *   1. OPTIONS 请求直接跳过
     *   2. 匹配排除路径列表的请求跳过（支持前缀匹配，如 /admin/operation-logs/1）
     *   3. GET 请求仅白名单路径记录，其余跳过
     *   4. 非 GET 请求（POST/PUT/DELETE 等）默认记录
     */
    protected function shouldRecord(string $path, string $method): bool
    {
        if (in_array($method, $this->excludeMethods)) {
            return false;
        }

        foreach ($this->excludePaths as $excludePath) {
            if ($path === $excludePath || str_starts_with($path, $excludePath . '/')) {
                return false;
            }
        }

        if ($method === 'GET') {
            foreach ($this->recordGetPaths as $recordPath) {
                if ($path === $recordPath || str_starts_with($path, $recordPath . '/')) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    /**
     * 组装日志数据并通过事件触发写入
     *
     * @param Request $request 当前请求对象，用于提取用户信息和请求参数
     * @param Response $response 控制器响应对象，用于提取操作结果和状态
     * @return void
     *
     * 处理流程：
     *   1. 从 $request->userInfo 提取操作人信息（由 AuthCheck 中间件注入）
     *   2. 对请求参数进行 JSON 编码，超长参数（>2000字节）做截断处理
     *   3. 根据路径解析操作模块和动作名称
     *   4. 解析响应内容，判断操作是否成功，提取结果数据（同样限制 2000 字节）
     *   5. 通过 Event::trigger('OperateLog', ...) 触发事件写入日志
     *
     * @note 参数和结果均限制 2000 字节，防止超长数据写入数据库
     */
    protected function recordLog(Request $request, Response $response): void
    {
        try {
            $userInfo = $request->userInfo ?? [];
            $userId = (int) ($userInfo['id'] ?? 0);
            $username = $userInfo['username'] ?? '';
            $method = strtoupper($request->method());
            $path = '/' . trim($request->pathinfo(), '/');
            $ip = $request->ip();
            $params = $request->param();

            if (!empty($params)) {
                try {
                    $jsonParams = json_encode($params, JSON_UNESCAPED_UNICODE);
                    if ($jsonParams === false) {
                        Log::warning('参数JSON编码失败: ' . json_last_error_msg());
                        $params = ['json_encode_error' => true];
                    } elseif (strlen($jsonParams) > 2000) {
                        $params = ['truncated' => true, 'original_length' => strlen($jsonParams)];
                    }
                } catch (\Exception $e) {
                    Log::warning('参数JSON编码异常: ' . $e->getMessage());
                    $params = ['json_encode_error' => true];
                }
            }

            $module = $this->getModule($path);
            $action = $this->getAction($path, $method);

            $status = 1;
            $errorMsg = null;
            $result = null;

            if ($response) {
                $content = $response->getContent();
                $data = json_decode($content, true);
                if (isset($data['code']) && $data['code'] != 200) {
                    $status = 0;
                    $errorMsg = $data['msg'] ?? '操作失败';
                }
                if (isset($data['data'])) {
                    $resultStr = json_encode($data['data'], JSON_UNESCAPED_UNICODE);
                    if (strlen($resultStr) <= 2000) {
                        $result = $resultStr;
                    }
                }
            }

            Event::trigger('OperateLog', [
                'user_id' => $userId,
                'username' => $username,
                'module' => $module,
                'action' => $action,
                'method' => $method,
                'url' => $path,
                'ip' => $ip,
                'param' => !empty($params) ? json_encode($params, JSON_UNESCAPED_UNICODE) : '',
                'result' => $result,
                'status' => $status,
                'error_msg' => $errorMsg,
            ]);
        } catch (\Exception $e) {
            Log::error('操作日志数据组装失败: ' . $e->getMessage(), [
                'path' => '/' . trim($request->pathinfo(), '/'),
                'method' => $request->method(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 根据请求路径解析操作所属模块
     *
     * @param string $path 标准化后的请求路径，如 /admin/users/1
     * @return string 模块中文名称，如"用户管理"；未匹配时返回"系统"
     *
     * 解析规则：取路径第二段（/admin/{module}/...）作为模块标识，
     * 通过预定义映射表转换为中文名称。
     */
    protected function getModule(string $path): string
    {
        $pathParts = array_filter(explode('/', $path));
        $moduleMap = [
            'users' => '用户管理',
            'roles' => '角色管理',
            'menus' => '菜单管理',
            'depts' => '部门管理',
            'apis' => '接口管理',
            'dict' => '字典管理',
            'profile' => '个人中心',
            'dashboard' => '仪表盘',
        ];

        if (count($pathParts) >= 2) {
            $key = $pathParts[1];
            if (isset($moduleMap[$key])) {
                return $moduleMap[$key];
            }
            if ($key === 'login-logs') {
                return '登录日志';
            }
            if ($key === 'operation-logs') {
                return '操作日志';
            }
        }

        return '系统';
    }

    /**
     * 根据请求路径和 HTTP 方法解析操作动作名称
     *
     * @param string $path 标准化后的请求路径，如 /admin/users/1/assign-roles
     * @param string $method HTTP 方法（大写），如 POST、PUT、DELETE
     * @return string 动作中文名称，如"分配角色"、"编辑"、"删除"；未匹配时返回"操作"
     *
     * 解析规则：
     *   1. 优先匹配路径末段的特殊动作关键词（如 assign-roles → 分配角色）
     *   2. 无特殊关键词时，根据 HTTP 方法映射默认动作（POST→新增、PUT→编辑、DELETE→删除）
     */
    protected function getAction(string $path, string $method): string
    {
        $actionMap = [
            'POST' => '新增',
            'PUT' => '编辑',
            'DELETE' => '删除',
        ];

        $pathParts = array_filter(explode('/', $path));
        $lastPart = end($pathParts);

        $specialActions = [
            'assign-roles' => '分配角色',
            'assign-menus' => '分配菜单',
            'assign-buttons' => '分配按钮',
            'assign-apis' => '分配接口',
            'data-scope' => '设置数据权限',
            'status' => '修改状态',
            'reset-password' => '重置密码',
            'sort' => '设置排序',
            'avatar' => '上传头像',
            'password' => '修改密码',
            'import' => '导入',
            'export' => '导出',
            'clean' => '清理',
            'clear' => '清空',
            'delete' => '删除',
        ];

        if (isset($specialActions[$lastPart])) {
            return $specialActions[$lastPart];
        }

        return $actionMap[$method] ?? '操作';
    }
}
