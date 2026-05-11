<?php
namespace app\admin\middleware;

use app\common\AdminAuth;
use app\model\Api;
use think\facade\Log;
use think\Request;

class ApiPermission
{
    protected array $whitelistPaths = [
        ['GET', '/admin/profile'],
        ['PUT', '/admin/password'],
        ['PUT', '/admin/profile'],
        ['POST', '/admin/profile/avatar'],
        ['GET', '/admin/dashboard/statistics'],
        ['GET', '/admin/dict/code'],
    ];

    protected function isWhitelisted(string $method, string $path): bool
    {
        foreach ($this->whitelistPaths as $item) {
            if ($item[0] === $method && str_starts_with($path, $item[1])) {
                return true;
            }
        }
        return false;
    }

    public function handle(Request $request, \Closure $next)
    {
        $method = strtoupper($request->method());
        $path = '/' . trim($request->pathinfo(), '/');

        $userId = $request->userInfo['id'] ?? 0;
        if ($userId <= 0) {
            return json([
                'code' => 401,
                'msg' => '用户未登录',
                'data' => []
            ], 401);
        }

        $auth = AdminAuth::instance();
        $auth->setUser($userId);

        if ($auth->isSuperAdmin()) {
            return $next($request);
        }

        if ($this->isWhitelisted($method, $path)) {
            return $next($request);
        }

        $apiModel = new Api();
        $api = $apiModel->matchApiByPath($method, $path);

        if ($api === null) {
            Log::info('API权限验证：接口未配置，默认放行', [
                'method' => $method,
                'path' => $path,
                'user_id' => $userId,
            ]);
            return $next($request);
        }

        $apiCodes = $auth->getApiCodes();
        if (!in_array($api['code'], $apiCodes)) {
            Log::warning('API权限验证：无权限访问', [
                'method' => $method,
                'path' => $path,
                'api_code' => $api['code'],
                'user_id' => $userId,
            ]);
            return json([
                'code' => 403,
                'msg' => '无权限访问此接口',
                'data' => []
            ], 403);
        }

        return $next($request);
    }
}