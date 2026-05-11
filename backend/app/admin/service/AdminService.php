<?php
namespace app\admin\service;

use app\common\AdminAuth;
use app\common\JwtToken;
use app\model\User;
use app\model\Role;

class AdminService
{
    private static ?AdminService $instance = null;

    private ?AdminAuth $auth = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function auth(): AdminAuth
    {
        if ($this->auth === null) {
            $this->auth = AdminAuth::instance();
        }
        return $this->auth;
    }

    public function setAuthUser(int $userId): void
    {
        $this->auth()->setUser($userId);
    }

    public function getCurrentUserId(): int
    {
        return $this->auth()->getUserId();
    }

    public function getCurrentUser(): ?User
    {
        return $this->auth()->getUser();
    }

    public function isSuperAdmin(): bool
    {
        return $this->auth()->isSuperAdmin();
    }

    public function checkPermission(string|array $permission, int $type = 1): bool
    {
        return $this->auth()->check($permission, $type);
    }

    public function clearAuthCache(): void
    {
        $this->auth()->clearCache();
    }

    public function generateToken(array $payload): string
    {
        return JwtToken::generate($payload);
    }

    public function parseToken(string $token): array
    {
        return JwtToken::parse($token);
    }

    public function buildUserPayload(User $user, array $roles = []): array
    {
        $roleCodes = array_column($roles, 'code');

        return [
            'user_id' => $user->id,
            'username' => $user->username,
            'realname' => $user->nickname ?? '',
            'roles' => $roleCodes,
        ];
    }

    public function getUserRoles(int $userId): array
    {
        return (new Role())->getUserRoles($userId);
    }

    public function getDataScope(): int
    {
        return $this->auth()->getDataScope();
    }

    public function getDataScopeDeptIds(): array
    {
        return $this->auth()->getDataScopeDeptIds();
    }
}