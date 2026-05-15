<?php
/**
 * API 接口权限验证中间件
 *
 * @文件: ApiPermission.php
 * @用途: 后台管理系统的 API 接口级别权限验证
 * @描述: 在 AuthCheck 中间件之后执行，对已认证的用户进行接口级别的细粒度权限控制。
 *        通过匹配当前请求的 HTTP 方法和路径与数据库中配置的 API 权限码，
 *        验证用户角色是否拥有对应接口的访问权限。
 *
 * @中间件执行顺序:
 *   AuthCheck（身份认证）→ ApiPermission（接口权限）→ RecordOperate（操作日志）
 *
 * @权限验证策略:
 *   1. 超级管理员：直接放行，拥有所有接口权限
 *   2. 白名单接口：无需权限验证，直接放行
 *   3. 未配置接口：数据库中无匹配记录时默认放行，并记录 info 日志
 *   4. 已配置接口：验证用户的 API 权限码列表是否包含该接口的权限码
 *
 * @白名单设计说明:
 *   白名单中的接口路径使用前缀匹配（str_starts_with），例如：
 *   - '/admin/dict/code' 可匹配 '/admin/dict/code/xxx'
 *   白名单接口的选择原则：
 *   - 个人信息相关接口：用户查看/修改自己的资料不应受角色权限限制
 *   - 仪表盘统计接口：登录后的首页数据属于基础功能
 *   - 字典查询接口：下拉选项等公共数据，多个业务模块依赖
 *
 * @依赖组件:
 *   - AdminAuth: 权限认证类，提供用户角色判断和 API 权限码获取
 *   - Api: 接口模型，提供请求路径与权限码的匹配
 *   - Log: 日志门面，记录权限验证的放行和拒绝事件
 *
 * @使用示例:
 *   该中间件在 route/admin.php 中通过 middleware 组注册，
 *   应用于除登录、登出、刷新 Token 外的所有 admin 路由组
 *
 * @注意事项:
 *   - 本中间件依赖 AuthCheck 中间件先执行，从 $request->userInfo 获取用户 ID
 *   - 接口未配置时默认放行，后续可通过配置项切换为拒绝策略
 *   - AdminAuth 单例在同一请求周期内复用，避免重复加载权限数据
 */

namespace app\admin\middleware;

use app\common\AdminAuth;
use app\model\Api;
use think\facade\Log;
use think\Request;

/**
 * API 接口权限验证中间件
 *
 * 负责在用户身份认证通过后，进行接口级别的权限校验。
 * 与 AuthCheck 中间件配合使用，AuthCheck 验证"你是谁"，ApiPermission 验证"你能做什么"。
 *
 * @property array $whitelistPaths 白名单路径列表，每项为 [HTTP方法, 路径前缀] 格式
 */
class ApiPermission
{
    /**
     * 接口白名单路径配置
     *
     * @描述: 白名单中的接口路径无需 API 权限验证，已认证用户均可访问。
     *        每项为 [HTTP方法, 路径前缀] 格式，路径匹配使用前缀匹配策略。
     *
     * @配置项说明:
     *   - ['GET', '/admin/profile']             获取当前用户个人资料
     *   - ['PUT', '/admin/password']            修改当前用户密码
     *   - ['PUT', '/admin/profile']             更新当前用户个人资料
     *   - ['POST', '/admin/profile/avatar']     上传当前用户头像
     *   - ['GET', '/admin/dashboard/statistics'] 获取仪表盘统计数据
     *   - ['GET', '/admin/dict/code']           根据字典编码查询字典数据（公共下拉选项）
     *
     * @匹配规则: HTTP 方法精确匹配 + 路径前缀匹配（str_starts_with）
     */
    protected array $whitelistPaths = [
        ['GET', '/admin/profile'],
        ['PUT', '/admin/password'],
        ['PUT', '/admin/profile'],
        ['POST', '/admin/profile/avatar'],
        ['GET', '/admin/dashboard/statistics'],
        ['GET', '/admin/dict/code'],
    ];

    /**
     * 判断请求路径是否在白名单中
     *
     * @描述: 遍历白名单配置，使用 HTTP 方法精确匹配和路径前缀匹配判断当前请求是否免验证
     *
     * @参数:
     *   - method (string): HTTP 请求方法（大写），如 'GET'、'POST'
     *   - path (string): 请求路径（已标准化为 / 开头），如 '/admin/profile'
     *
     * @返回: bool true-在白名单中免验证，false-需要权限验证
     *
     * @匹配规则:
     *   - HTTP 方法必须完全一致（区分大小写）
     *   - 路径使用前缀匹配，白名单路径为请求路径的前缀即匹配
     *   - 示例：白名单 '/admin/dict/code' 可匹配 '/admin/dict/code/xxx'
     */
    protected function isWhitelisted(string $method, string $path): bool
    {
        foreach ($this->whitelistPaths as $item) {
            if ($item[0] === $method && str_starts_with($path, $item[1])) {
                return true;
            }
        }
        return false;
    }

    /**
     * 中间件处理入口
     *
     * @描述: 对已认证用户的 API 请求进行接口级别权限验证，
     *        验证通过则将请求传递给后续中间件，否则返回 403 错误
     *
     * @参数:
     *   - request (Request): 当前 HTTP 请求对象，由 AuthCheck 中间件注入 userInfo
     *   - next (Closure): 下一个中间件或控制器处理函数
     *
     * @返回: \think\response\Json 401/403 错误响应，或传递给后续处理器的响应
     *
     * @业务逻辑:
     *   1. 提取请求方法和路径：从请求对象获取 HTTP 方法和标准化路径
     *   2. 用户身份检查：从 userInfo 获取用户 ID，无效则返回 401
     *   3. 初始化权限上下文：设置 AdminAuth 用户上下文，加载角色和权限数据
     *   4. 超级管理员放行：超级管理员拥有所有权限，直接通过
     *   5. 白名单放行：个人信息、仪表盘等基础接口免验证
     *   6. 接口配置匹配：在数据库中查找当前路径对应的 API 权限配置
     *   7. 未配置接口放行：数据库无匹配记录时默认放行（记录 info 日志）
     *   8. 权限码验证：检查用户的 API 权限码列表是否包含该接口的权限码
     *   9. 验证失败拒绝：无权限时返回 403（记录 warning 日志）
     *
     * @响应格式:
     *   401: {"code": 401, "msg": "用户未登录", "data": []}
     *   403: {"code": 403, "msg": "无权限访问此接口", "data": []}
     *
     * @日志记录:
     *   - info 级别：接口未配置时的默认放行记录
     *   - warning 级别：权限验证失败的拒绝记录
     *   日志包含 method、path、user_id 等上下文信息，便于安全审计
     */
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
