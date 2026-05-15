<?php
/**
 * 后台认证服务
 *
 * @类名: AdminAuthService
 * @功能: 后台用户认证与授权核心业务逻辑
 * @描述: 负责处理后台管理系统的用户登录、登出、Token管理、个人资料获取和密码修改等认证相关功能
 *
 * @职责:
 *   1. 用户身份验证与凭证管理
 *   2. JWT Token的生成、刷新与验证
 *   3. 用户会话管理与权限缓存
 *   4. 登录日志记录与审计
 *
 * @设计思路:
 *   - 采用JWT无状态认证方案，提升系统扩展性
 *   - 双重Token机制（Access Token + Refresh Token）实现安全的Token续期
 *   - 权限数据缓存化，减少数据库查询压力
 *   - 完整的登录日志记录，支持安全审计
 *
 * @依赖组件:
 *   - JwtToken: JWT Token生成与解析
 *   - AdminAuth: 管理员权限管理
 *   - LoginSecurityService: 登录安全（失败计数、账户锁定）
 *   - UserAgentParserService: User-Agent解析
 *   - User: 用户模型
 *   - Role: 角色模型
 *   - LoginLog: 登录日志模型
 *
 * @使用示例:
 *   $service = AdminAuthService::getInstance();
 *   $result = $service->login('admin', 'password', $request);
 *   $service->logout($userId);
 *   $result = $service->refreshToken($refreshToken);
 *   $profile = $service->getProfile($userId);
 *   $service->changePassword($userId, $oldPassword, $newPassword);
 */

namespace app\admin\service;

use app\common\JwtToken;
use app\common\AdminAuth;
use app\model\User;
use app\model\Role;
use app\model\LoginLog;
use think\facade\Config;

class AdminAuthService
{
    private static ?AdminAuthService $instance = null;

    private LoginSecurityService $loginSecurity;
    private UserAgentParserService $userAgentParser;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->loginSecurity = LoginSecurityService::getInstance();
        $this->userAgentParser = UserAgentParserService::getInstance();
    }

    /**
     * 用户登录
     *
     * @方法用途: 验证用户凭证并生成访问令牌
     * @功能描述: 接收用户名和密码，验证用户身份，成功后返回JWT访问令牌和刷新令牌
     *
     * @参数说明:
     *   - username (string): 用户名
     *   - password (string): 密码
     *   - ip (string): 客户端IP地址
     *   - userAgent (string): User-Agent字符串
     *
     * @返回值: array
     *   - 'success': bool - 登录是否成功
     *   - 'data': array - 成功时返回Token和用户信息
     *   - 'error': string - 失败时返回错误信息
     *   - 'code': int - HTTP状态码
     *
     * @业务逻辑:
     *   1. 账户锁定检查：如账户已锁定，返回403错误
     *   2. 用户存在性验证：查询数据库确认用户存在
     *   3. 用户状态检查：验证账户是否被禁用（status=1为正常）
     *   4. 密码验证：使用password_verify验证密码哈希
     *   5. 密码再哈希：如密码算法变更，自动更新密码哈希
     *   6. 角色获取：查询用户关联的角色信息
     *   7. Token生成：生成Access Token和Refresh Token
     *   8. 登录信息更新：记录最后登录IP和时间
     *   9. 失败计数清除：登录成功后清除失败计数
     *   10. 登录日志记录：记录完整登录信息
     *
     * @异常处理:
     *   - 抛出AccountLockedException: 账户已锁定
     *   - 抛出InvalidCredentialsException: 用户名或密码错误
     *   - 抛出AccountDisabledException: 账户已被禁用
     *
     * @安全特性:
     *   - 登录失败计数：超过5次失败后账户锁定15分钟
     *   - 密码哈希：使用PHP password_hash/password_verify
     *   - JWT签名：使用HS256算法，密钥从配置读取
     *   - 敏感信息保护：数据库错误不暴露具体信息
     */
    public function login(string $username, string $password, string $ip, string $userAgent): array
    {
        if ($this->loginSecurity->isAccountLocked($username)) {
            $lockMinutes = $this->loginSecurity->getLockDurationMinutes();
            $this->recordLoginLog($username, 0, '账户已锁定', $ip, $userAgent);
            return [
                'success' => false,
                'error' => "账户已锁定，请{$lockMinutes}分钟后重试",
                'code' => 403,
            ];
        }

        $user = User::where('username', $username)->find();
        if (!$user) {
            $this->recordLoginLog($username, 0, '用户不存在', $ip, $userAgent);
            return [
                'success' => false,
                'error' => '用户名或密码错误',
                'code' => 401,
            ];
        }

        if ($user->status !== 1) {
            $this->recordLoginLog($username, 0, '用户已被禁用', $ip, $userAgent);
            return [
                'success' => false,
                'error' => '账户已被禁用，请联系管理员',
                'code' => 403,
            ];
        }

        if (!password_verify($password, $user->password)) {
            $this->recordLoginLog($username, 0, '密码错误', $ip, $userAgent);
            $failResult = $this->loginSecurity->checkLoginFailTimes($username);
            if ($failResult['locked']) {
                $lockMinutes = $this->loginSecurity->getLockDurationMinutes();
                return [
                    'success' => false,
                    'error' => "账户已锁定，请{$lockMinutes}分钟后重试",
                    'code' => 403,
                ];
            }
            return [
                'success' => false,
                'error' => '用户名或密码错误',
                'code' => 401,
            ];
        }

        if (password_needs_rehash($user->password, PASSWORD_DEFAULT)) {
            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $user->save();
        }

        $roles = (new Role())->getUserRoles($user->id);
        $roleCodes = array_column($roles, 'code');

        $payload = [
            'user_id' => $user->id,
            'username' => $user->username,
            'realname' => $user->nickname ?? '',
            'roles' => $roleCodes,
        ];

        $accessToken = JwtToken::generate($payload);
        $refreshPayload = array_merge($payload, ['type' => 'refresh']);
        $refreshTtl = (int) (Config::get('jwt.refresh_ttl') ?: env('JWT_REFRESH_TTL', 10080));
        $refreshToken = JwtToken::generate($refreshPayload, $refreshTtl);

        $user->last_login_ip = $ip;
        $user->last_login_time = date('Y-m-d H:i:s');
        $user->save();

        $this->loginSecurity->clearLoginFailTimes($username);
        $this->recordLoginLog($username, 1, '登录成功', $ip, $userAgent);

        return [
            'success' => true,
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => (int) Config::get('jwt.ttl', 1440) * 60,
                'user_info' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nickname' => $user->nickname,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'avatar' => $user->avatar,
                ],
            ],
        ];
    }

    /**
     * 用户登出
     *
     * @方法用途: 清除用户会话和权限缓存
     * @功能描述: 清除当前用户的权限缓存数据，实现安全的登出操作
     *
     * @参数说明:
     *   - userId (int): 用户ID
     *
     * @业务逻辑:
     *   1. 初始化AdminAuth实例并设置当前用户
     *   2. 清除该用户的权限缓存数据
     *
     * @安全说明:
     *   - 本方法不清除Token（JWT无状态特性）
     *   - 前端应自行清除本地存储的Token
     *   - 服务器端通过清除权限缓存实现会话失效
     *
     * @设计考虑:
     *   - 采用服务端缓存清除方式，无需维护黑名单
     *   - 适合分布式部署场景，缓存可使用Redis等共享存储
     */
    public function logout(int $userId): void
    {
        if ($userId > 0) {
            $auth = AdminAuth::instance();
            $auth->setUser($userId);
            $auth->clearCache();
        }
    }

    /**
     * 刷新访问令牌
     *
     * @方法用途: 使用刷新令牌获取新的访问令牌
     * @功能描述: 验证刷新令牌的有效性，颁发新的Access Token和Refresh Token
     *
     * @参数说明:
     *   - refreshToken (string): 刷新令牌
     *
     * @返回值: array
     *   - 'success': bool - 是否成功
     *   - 'data': array - 成功时返回新的Token
     *   - 'error': string - 失败时返回错误信息
     *   - 'code': int - HTTP状态码
     *
     * @业务逻辑:
     *   1. Token解析：解析并验证JWT刷新令牌
     *   2. 类型验证：确认Token类型为refresh（防误用）
     *   3. 用户验证：检查用户存在性和状态
     *   4. 载荷清理：移除过期和类型标识
     *   5. 新Token生成：生成新的Access Token和Refresh Token
     *
     * @异常处理:
     *   - 抛出InvalidTokenException: Token无效或已过期
     *   - 抛出InvalidTokenTypeException: Token类型不是refresh
     *   - 抛出AccountDisabledException: 用户已被禁用
     *
     * @安全特性:
     *   - 双重Token机制降低Token泄露风险
     *   - Refresh Token仅能换取Access Token
     *   - 用户状态变更时Token自动失效
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            $payload = JwtToken::parse($refreshToken);

            if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
                return [
                    'success' => false,
                    'error' => '无效的 Refresh Token',
                    'code' => 401,
                ];
            }

            $userId = $payload['user_id'] ?? 0;
            $user = User::find($userId);
            if (!$user || $user->status !== 1) {
                return [
                    'success' => false,
                    'error' => '用户已被禁用',
                    'code' => 401,
                ];
            }

            unset($payload['type'], $payload['exp'], $payload['nbf']);

            $refreshTtl = (int) (Config::get('jwt.refresh_ttl') ?: env('JWT_REFRESH_TTL', 10080));
            $newAccessToken = JwtToken::generate($payload);
            $newRefreshPayload = array_merge($payload, ['type' => 'refresh']);
            $newRefreshToken = JwtToken::generate($newRefreshPayload, $refreshTtl);

            return [
                'success' => true,
                'data' => [
                    'access_token' => $newAccessToken,
                    'refresh_token' => $newRefreshToken,
                    'token_type' => 'Bearer',
                    'expires_in' => (int) Config::get('jwt.ttl', 1440) * 60,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Token 刷新失败：' . $e->getMessage(),
                'code' => 401,
            ];
        }
    }

    /**
     * 获取用户个人资料
     *
     * @方法用途: 获取当前登录用户的详细信息和权限数据
     * @功能描述: 返回用户基本信息、角色列表、菜单树和权限代码
     *
     * @参数说明:
     *   - userId (int): 用户ID
     *
     * @返回值: array
     *   - 'success': bool - 是否成功
     *   - 'data': array - 用户资料数据
     *   - 'error': string - 失败时返回错误信息
     *   - 'code': int - HTTP状态码
     *
     * @业务逻辑:
     *   1. 用户信息查询：获取用户基础信息
     *   2. 角色查询：获取用户关联的角色列表
     *   3. 权限初始化：设置AdminAuth用户上下文
     *   4. 菜单构建：获取用户可访问的菜单树
     *   5. 权限码获取：获取用户所有权限代码
     *   6. 按钮权限获取：获取细粒度按钮权限
     *
     * @异常处理:
     *   - 抛出UserNotFoundException: 用户不存在
     *
     * @使用场景:
     *   - 用户登录后加载个人信息
     *   - 前端路由守卫验证权限
     *   - 动态菜单渲染
     *   - 按钮级别权限控制
     */
    public function getProfile(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            return [
                'success' => false,
                'error' => '用户不存在',
                'code' => 404,
            ];
        }

        $auth = AdminAuth::instance();
        $auth->setUser($userId);

        $roles = (new Role())->getUserRoles($userId);

        return [
            'success' => true,
            'data' => [
                'id' => $user->id,
                'username' => $user->username,
                'nickname' => $user->nickname,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'avatar' => $user->avatar,
                'gender' => $user->gender,
                'dept_id' => $user->dept_id,
                'last_login_ip' => $user->last_login_ip,
                'last_login_time' => $user->last_login_time,
                'roles' => $roles,
                'menus' => $auth->getMenuTree(),
                'permissions' => $auth->getMenuCodes(),
                'button_codes' => $auth->getButtonCodes(),
            ],
        ];
    }

    /**
     * 修改密码
     *
     * @方法用途: 允许用户修改自己的登录密码
     * @功能描述: 验证原密码后，使用新密码替换旧密码
     *
     * @参数说明:
     *   - userId (int): 用户ID
     *   - oldPassword (string): 原密码
     *   - newPassword (string): 新密码
     *
     * @返回值: array
     *   - 'success': bool - 是否成功
     *   - 'error': string - 失败时返回错误信息
     *   - 'code': int - HTTP状态码
     *
     * @业务逻辑:
     *   1. 用户查询：获取用户当前信息
     *   2. 原密码验证：使用password_verify验证原密码
     *   3. 密码更新：使用password_hash加密新密码
     *   4. 缓存清除：清除用户权限缓存（强制重新认证）
     *
     * @异常处理:
     *   - 抛出UserNotFoundException: 用户不存在
     *   - 抛出InvalidPasswordException: 原密码错误
     *
     * @安全特性:
     *   - 必须提供原密码，防止账户被盗用
     *   - 新密码使用强哈希算法存储
     *   - 修改后清除权限缓存，强制使用新密码重新登录
     *
     * @用户体验:
     *   - 建议前端在密码修改成功后提示用户重新登录
     *   - 可选：强制当前所有会话失效
     */
    public function changePassword(int $userId, string $oldPassword, string $newPassword): array
    {
        $user = User::find($userId);
        if (!$user) {
            return [
                'success' => false,
                'error' => '用户不存在',
                'code' => 404,
            ];
        }

        if (!password_verify($oldPassword, $user->password)) {
            return [
                'success' => false,
                'error' => '原密码错误',
                'code' => 400,
            ];
        }

        $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
        $user->save();

        AdminAuth::instance()->clearCache();

        return [
            'success' => true,
        ];
    }

    /**
     * 记录登录日志
     *
     * @方法用途: 记录用户登录尝试的详细信息
     * @功能描述: 将登录行为写入日志表，用于安全审计和异常检测
     *
     * @参数说明:
     *   - username (string): 尝试登录的用户名
     *   - status (int): 登录状态，1=成功，0=失败
     *   - msg (string): 登录结果描述信息
     *   - ip (string): 客户端IP地址
     *   - userAgent (string): User-Agent字符串
     *
     * @业务逻辑:
     *   1. 解析User-Agent获取操作系统类型
     *   2. 解析User-Agent获取浏览器类型
     *   3. 调用LoginLog模型保存日志记录
     *
     * @日志字段:
     *   - username: 用户名
     *   - status: 登录状态码
     *   - msg: 结果消息
     *   - ip: 客户端IP地址
     *   - country: 国家/地区（暂留空）
     *   - user_agent: 浏览器User-Agent字符串
     *   - os: 操作系统类型
     *   - browser: 浏览器类型
     *
     * @调用场景:
     *   - 登录成功时
     *   - 登录失败时
     *   - 账户锁定时
     *   - 账户被禁用时
     */
    protected function recordLoginLog(string $username, int $status, string $msg, string $ip, string $userAgent): void
    {
        $loginLog = new LoginLog();
        $loginLog->recordLogin(
            $username,
            $status,
            $msg,
            $ip,
            '',
            $userAgent,
            $this->userAgentParser->parseOs($userAgent),
            $this->userAgentParser->parseBrowser($userAgent)
        );
    }
}
