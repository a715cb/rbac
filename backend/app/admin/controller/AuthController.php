<?php
namespace app\admin\controller;

use app\common\JwtToken;
use app\common\AdminAuth;
use app\common\SimpleCache;
use app\model\User;
use app\model\Role;
use app\model\LoginLog;
use app\admin\validate\LoginValidate;
use app\admin\validate\ChangePasswordValidate;
use think\facade\Config;
use think\Request;

class AuthController extends BaseController
{
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

        $lockDuration = Config::get('auth.login_lock_duration', 900);
        if ($lockDuration > 0 && SimpleCache::get('login_lock_' . $username)) {
            $this->recordLoginLog($username, 0, '账户已锁定', $request);
            $lockMinutes = ceil($lockDuration / 60);
            return $this->error("账户已锁定，请{$lockMinutes}分钟后重试", 403);
        }

        $user = User::where('username', $username)->find();
        if (!$user) {
            $this->recordLoginLog($username, 0, '用户不存在', $request);
            return $this->error('用户名或密码错误', 401);
        }

        if ($user->status !== 1) {
            $this->recordLoginLog($username, 0, '用户已被禁用', $request);
            return $this->error('账户已被禁用，请联系管理员', 403);
        }

        if (!password_verify($password, $user->password)) {
            $this->recordLoginLog($username, 0, '密码错误', $request);
            $this->checkLoginFailTimes($username, $request);
            return $this->error('用户名或密码错误', 401);
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
        $refreshToken = JwtToken::generate($refreshPayload);

        $user->last_login_ip = $request->ip();
        $user->last_login_time = date('Y-m-d H:i:s');
        $user->save();

        $this->clearLoginFailTimes($username);
        $this->recordLoginLog($username, 1, '登录成功', $request);

        return $this->success([
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
        ], '登录成功');
    }

    public function logout(Request $request)
    {
        $userId = $request->userInfo['id'] ?? 0;

        if ($userId > 0) {
            $auth = AdminAuth::instance();
            $auth->setUser($userId);
            $auth->clearCache();
        }

        return $this->success([], '登出成功');
    }

    public function refreshToken(Request $request)
    {
        $data = $request->post();
        $refreshToken = $data['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            return $this->error('Refresh Token 不能为空', 400);
        }

        try {
            $payload = JwtToken::parse($refreshToken);

            if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
                return $this->error('无效的 Refresh Token', 401);
            }

            $userId = $payload['user_id'] ?? 0;
            $user = User::find($userId);
            if (!$user || $user->status !== 1) {
                return $this->error('用户已被禁用', 401);
            }

            unset($payload['type'], $payload['exp'], $payload['nbf']);

            $newAccessToken = JwtToken::generate($payload);
            $newRefreshPayload = array_merge($payload, ['type' => 'refresh']);
            $newRefreshToken = JwtToken::generate($newRefreshPayload);

            return $this->success([
                'access_token' => $newAccessToken,
                'refresh_token' => $newRefreshToken,
                'token_type' => 'Bearer',
                'expires_in' => (int) Config::get('jwt.ttl', 1440) * 60,
            ], 'Token 刷新成功');
        } catch (\Exception $e) {
            return $this->error('Token 刷新失败：' . $e->getMessage(), 401);
        }
    }

    public function profile(Request $request)
    {
        $userId = $request->userInfo['id'] ?? 0;
        if ($userId <= 0) {
            return $this->error('用户未登录', 401);
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $auth = AdminAuth::instance();
        $auth->setUser($userId);

        $roles = (new Role())->getUserRoles($userId);
        $menus = $auth->getMenuTree();

        return $this->success([
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
            'menus' => $menus,
            'permissions' => $auth->getMenuCodes(),
            'button_codes' => $auth->getButtonCodes(),
        ], '获取成功');
    }

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

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        if (!password_verify($data['old_password'], $user->password)) {
            return $this->error('原密码错误', 400);
        }

        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $user->save();

        AdminAuth::instance()->clearCache();

        return $this->success([], '密码修改成功');
    }

    protected function recordLoginLog(string $username, int $status, string $msg, Request $request): void
    {
        $loginLog = new LoginLog();
        $loginLog->recordLogin(
            $username,
            $status,
            $msg,
            $request->ip(),
            '',
            $request->header('user-agent', ''),
            $this->parseOs($request->header('user-agent', '')),
            $this->parseBrowser($request->header('user-agent', ''))
        );
    }

    protected function checkLoginFailTimes(string $username, Request $request): void
    {
        $key = 'login_fail_' . $username;
        $maxTimes = Config::get('auth.max_login_fail_times', 5);
        $lockDuration = Config::get('auth.login_lock_duration', 900);

        if ($lockDuration <= 0) {
            return;
        }

        $currentTimes = SimpleCache::get($key, 0);
        if ($currentTimes === 0) {
            SimpleCache::set($key, 1, $lockDuration);
            $times = 1;
        } else {
            $times = SimpleCache::increment($key, 1, $lockDuration);
        }

        if ($times >= $maxTimes) {
            SimpleCache::setIfNotExists('login_lock_' . $username, 1, $lockDuration);
            $this->recordLoginLog($username, 0, '登录失败超过次数，已锁定', $request);
        }
    }

    protected function clearLoginFailTimes(string $username): void
    {
        SimpleCache::delete('login_fail_' . $username);
        SimpleCache::delete('login_lock_' . $username);
    }

    protected function parseOs(string $userAgent): string
    {
        if (preg_match('/Windows NT 10/i', $userAgent)) return 'Windows 10';
        if (preg_match('/Windows NT 6.3/i', $userAgent)) return 'Windows 8.1';
        if (preg_match('/Windows NT 6.2/i', $userAgent)) return 'Windows 8';
        if (preg_match('/Windows NT 6.1/i', $userAgent)) return 'Windows 7';
        if (preg_match('/Mac OS X/i', $userAgent)) return 'macOS';
        if (preg_match('/Linux/i', $userAgent)) return 'Linux';
        if (preg_match('/iPhone/i', $userAgent)) return 'iOS';
        if (preg_match('/Android/i', $userAgent)) return 'Android';
        return 'Unknown';
    }

    protected function parseBrowser(string $userAgent): string
    {
        if (preg_match('/MSIE/i', $userAgent) || preg_match('/Trident/i', $userAgent)) return 'IE';
        if (preg_match('/Edge/i', $userAgent)) return 'Edge';
        if (preg_match('/Firefox/i', $userAgent)) return 'Firefox';
        if (preg_match('/Chrome/i', $userAgent)) return 'Chrome';
        if (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) return 'Safari';
        if (preg_match('/Opera/i', $userAgent)) return 'Opera';
        return 'Unknown';
    }
}
