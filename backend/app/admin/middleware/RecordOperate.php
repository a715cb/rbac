<?php
namespace app\admin\middleware;

use think\Request;
use think\facade\Event;
use think\facade\Log;
use think\Response;

class RecordOperate
{
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

    protected array $excludeMethods = [
        'OPTIONS',
    ];

    protected array $recordGetPaths = [
        '/admin/users/export',
        '/admin/operation-logs/export',
        '/admin/login-logs/export',
        '/admin/dict/export',
    ];

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
