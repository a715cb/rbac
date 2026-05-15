<?php
/**
 * 认证控制器
 *
 * @类名: AuthController
 * @功能: 后台用户认证与授权管理（请求入口层）
 * @描述: 负责处理后台管理系统的HTTP请求和响应，业务逻辑委托给AdminAuthService处理
 *
 * @职责:
 *   1. HTTP请求参数接收与验证
 *   2. 调用服务层处理业务逻辑
 *   3. 格式化HTTP响应
 *   4. 异常处理与错误返回
 *
 * @设计思路:
 *   - 控制器仅负责请求/响应处理，不包含业务逻辑
 *   - 所有业务逻辑委托给AdminAuthService
 *   - 遵循单一职责原则，保持代码清晰
 *
 * @依赖服务:
 *   - AdminAuthService: 认证核心服务
 *
 * @使用示例:
 *   POST /admin/auth/login
 *   { "username": "admin", "password": "xxx" }
 *
 *   GET /admin/auth/profile
 *   Header: Authorization: Bearer {access_token}
 */

namespace app\admin\controller;

use app\admin\service\AdminAuthService;
use app\admin\validate\LoginValidate;
use app\admin\validate\ChangePasswordValidate;
use app\common\BaseController;
use think\Request;

class AuthController extends BaseController
{
    private AdminAuthService $authService;

    public function __construct(\think\App $app)
    {
        parent::__construct($app);
        $this->authService = AdminAuthService::getInstance();
    }

    /**
     * 用户登录
     *
     * @请求方式: POST
     * @请求路径: /admin/auth/login
     * @功能描述: 接收用户名和密码，调用服务层验证并返回Token
     *
     * @请求参数:
     *   - username (string, required): 用户名，长度3-20字符
     *   - password (string, required): 密码，长度6-20字符
     *
     * @返回数据:
     *   {
     *     "code": 200,
     *     "msg": "登录成功",
     *     "data": {
     *       "access_token": "string",
     *       "refresh_token": "string",
     *       "token_type": "Bearer",
     *       "expires_in": 86400,
     *       "user_info": {...}
     *     }
     *   }
     *
     * @异常处理:
     *   - 422: 参数校验失败
     *   - 403: 账户已锁定
     *   - 401: 用户名或密码错误
     *   - 403: 账户已被禁用
     */
    public function login(Request $request)
    {
        $data = $request->post();

        try {
            $validate = new LoginValidate();
            $validate->scene('login')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $ip = $request->ip();
        $userAgent = $request->header('user-agent', '');

        $result = $this->authService->login($username, $password, $ip, $userAgent);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '登录成功');
    }

    /**
     * 用户登出
     *
     * @请求方式: POST
     * @请求路径: /admin/auth/logout
     * @认证要求: 需要有效的Access Token
     * @功能描述: 调用服务层清除用户权限缓存
     *
     * @返回数据:
     *   {
     *     "code": 200,
     *     "msg": "登出成功",
     *     "data": {}
     *   }
     */
    public function logout(Request $request)
    {
        $userId = $request->userInfo['id'] ?? 0;
        $this->authService->logout($userId);
        return $this->success([], '登出成功');
    }

    /**
     * 刷新访问令牌
     *
     * @请求方式: POST
     * @请求路径: /admin/auth/refreshToken
     * @功能描述: 使用刷新令牌获取新的访问令牌
     *
     * @请求参数:
     *   - refresh_token (string, required): 刷新令牌
     *
     * @返回数据:
     *   {
     *     "code": 200,
     *     "msg": "Token 刷新成功",
     *     "data": {
     *       "access_token": "string",
     *       "refresh_token": "string",
     *       "token_type": "Bearer",
     *       "expires_in": 86400
     *     }
     *   }
     *
     * @异常处理:
     *   - 400: refresh_token为空
     *   - 401: Token无效或已过期
     */
    public function refreshToken(Request $request)
    {
        $data = $request->post();
        $refreshToken = $data['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            return $this->error('Refresh Token 不能为空', 400);
        }

        $result = $this->authService->refreshToken($refreshToken);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], 'Token 刷新成功');
    }

    /**
     * 获取用户个人资料
     *
     * @请求方式: GET
     * @请求路径: /admin/auth/profile
     * @认证要求: 需要有效的Access Token
     * @功能描述: 调用服务层获取用户详细信息和权限数据
     *
     * @返回数据:
     *   {
     *     "code": 200,
     *     "msg": "获取成功",
     *     "data": {
     *       "id": 1,
     *       "username": "admin",
     *       "nickname": "管理员",
     *       "email": "admin@example.com",
     *       "mobile": "13800138000",
     *       "avatar": "url",
     *       "gender": 1,
     *       "dept_id": 1,
     *       "last_login_ip": "127.0.0.1",
     *       "last_login_time": "2024-01-01 12:00:00",
     *       "roles": [...],
     *       "menus": [...],
     *       "permissions": [...],
     *       "button_codes": [...]
     *     }
     *   }
     *
     * @异常处理:
     *   - 401: 用户未登录
     *   - 404: 用户不存在
     */
    public function profile(Request $request)
    {
        $userId = $request->userInfo['id'] ?? 0;
        if ($userId <= 0) {
            return $this->error('用户未登录', 401);
        }

        $result = $this->authService->getProfile($userId);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success($result['data'], '获取成功');
    }

    /**
     * 修改密码
     *
     * @请求方式: PUT
     * @请求路径: /admin/auth/changePassword
     * @认证要求: 需要有效的Access Token
     * @功能描述: 验证原密码后调用服务层修改密码
     *
     * @请求参数:
     *   - old_password (string, required): 原密码，长度6-20字符
     *   - password (string, required): 新密码，长度6-20字符
     *   - password_confirm (string, required): 确认密码
     *
     * @返回数据:
     *   {
     *     "code": 200,
     *     "msg": "密码修改成功",
     *     "data": {}
     *   }
     *
     * @异常处理:
     *   - 401: 用户未登录
     *   - 422: 参数校验失败
     *   - 404: 用户不存在
     *   - 400: 原密码错误
     */
    public function changePassword(Request $request)
    {
        $userId = $request->userInfo['id'] ?? 0;
        if ($userId <= 0) {
            return $this->error('用户未登录', 401);
        }

        $data = $request->put();

        try {
            $validate = new ChangePasswordValidate();
            $validate->scene('change')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $oldPassword = $data['old_password'] ?? '';
        $newPassword = $data['password'] ?? '';

        $result = $this->authService->changePassword($userId, $oldPassword, $newPassword);

        if (!$result['success']) {
            return $this->error($result['error'], $result['code']);
        }

        return $this->success([], '密码修改成功');
    }
}
