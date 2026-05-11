<?php
namespace app\miniapp\controller;

use app\miniapp\service\MiniappAuthService;
use app\miniapp\service\TokenBlacklistService;
use app\miniapp\validate\LoginValidate;
use app\common\JwtToken;
use think\Request;

class AuthController extends MiniappBaseController
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

        try {
            $authService = MiniappAuthService::getInstance();
            $result = $authService->loginByCode($data['code']);
            return $this->success($result, '登录成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function phone(Request $request)
    {
        $data = $request->post();

        try {
            $validate = new LoginValidate();
            $validate->scene('phone')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            if (!isset($request->wxUser) || !isset($request->wxUser['id'])) {
                return $this->error('用户认证信息缺失', 401);
            }
            $wxUserId = $request->wxUser['id'];

            $authService = MiniappAuthService::getInstance();
            $result = $authService->updatePhone(
                $wxUserId,
                $data['iv'] ?? '',
                $data['encrypted_data'] ?? ''
            );
            return $this->success($result, '手机号授权成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateProfile(Request $request)
    {
        $data = $request->post();

        try {
            if (!isset($request->wxUser) || !isset($request->wxUser['id'])) {
                return $this->error('用户认证信息缺失', 401);
            }
            $wxUserId = $request->wxUser['id'];

            $authService = MiniappAuthService::getInstance();
            $result = $authService->updateProfile(
                $wxUserId,
                $data['nickname'] ?? '',
                $data['avatar'] ?? ''
            );
            return $this->success($result, '更新成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function refreshToken(Request $request)
    {
        $data = $request->post();
        $refreshToken = $data['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            return $this->error('Refresh Token 不能为空', 400);
        }

        try {
            $accessToken = $request->header('Authorization', '');
            if (strpos($accessToken, 'Bearer ') === 0) {
                $accessToken = substr($accessToken, 7);
            }

            if (!empty($accessToken)) {
                try {
                    $payload = JwtToken::parse($accessToken);
                    $expireAt = (int) ($payload['exp'] ?? 0);
                    TokenBlacklistService::getInstance()->add($accessToken, $expireAt);
                } catch (\Exception $e) {
                }
            }

            $authService = MiniappAuthService::getInstance();
            $result = $authService->refreshToken($refreshToken);
            return $this->success($result, 'Token 刷新成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 401);
        }
    }

    public function logout(Request $request)
    {
        try {
            $token = $request->header('Authorization', '');
            if (strpos($token, 'Bearer ') === 0) {
                $token = substr($token, 7);
            }

            if (!empty($token)) {
                try {
                    $payload = JwtToken::parse($token);
                    $expireAt = (int) ($payload['exp'] ?? 0);
                    TokenBlacklistService::getInstance()->add($token, $expireAt);
                } catch (\Exception $e) {
                }
            }

            $refreshToken = $request->post('refresh_token', '');
            if (!empty($refreshToken)) {
                try {
                    $payload = JwtToken::parse($refreshToken);
                    $expireAt = (int) ($payload['exp'] ?? 0);
                    TokenBlacklistService::getInstance()->add($refreshToken, $expireAt);
                } catch (\Exception $e) {
                }
            }

            return $this->success([], '登出成功');
        } catch (\Exception $e) {
            return $this->success([], '登出成功');
        }
    }
}
