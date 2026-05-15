<?php
/**
 * JWT Token 工具类
 *
 * @文件: JwtToken.php
 * @描述: JSON Web Token（JWT）的生成、解析、验证和刷新工具类
 *
 * @功能说明:
 *   1. Token 生成：根据用户信息生成带签名的 JWT 令牌
 *   2. Token 解析：解析并验证 JWT 令牌的合法性
 *   3. Token 验证：快速验证 Token 是否有效
 *   4. Token 刷新：使用 Refresh Token 获取新的访问令牌
 *   5. 密钥缓存：避免重复读取配置，提高性能
 *
 * @设计思路:
 *   - 静态工具类：所有方法均为静态方法，无需实例化即可调用
 *   - HS256 签名算法：使用 HMAC SHA-256 算法进行签名，安全可靠
 *   - 密钥缓存机制：避免重复读取配置文件，提升性能
 *   - 完善的异常处理：区分不同类型的错误，便于上层捕获处理
 *
 * @技术实现:
 *   - 基于 firebase/php-jwt 库实现
 *   - Token 结构：Header.Payload.Signature
 *   - 标准声明：exp（过期时间）、nbf（生效时间）、iat（签发时间）
 *
 * @使用示例:
 *   // 生成 Token
 *   $payload = ['user_id' => 1, 'username' => 'admin'];
 *   $token = JwtToken::generate($payload);
 *
 *   // 解析 Token
 *   $payload = JwtToken::parse($token);
 *
 *   // 验证 Token
 *   $isValid = JwtToken::validate($token);
 *
 *   // 刷新 Token
 *   $newToken = JwtToken::refresh($refreshToken);
 *
 * @依赖组件:
 *   - Firebase\JWT\JWT: JWT 编解码核心类
 *   - Firebase\JWT\Key: 密钥类
 *   - Firebase\JWT\ExpiredException: 过期异常
 *   - Firebase\JWT\SignatureInvalidException: 签名无效异常
 *   - think\facade\Config: ThinkPHP 配置类
 *   - think\facade\Log: ThinkPHP 日志类
 *
 * @配置项:
 *   - jwt.secret: JWT 签名密钥（必填，至少32位）
 *   - jwt.ttl: Access Token 有效期（分钟，默认1440）
 *   - jwt.max_ttl: Refresh Token 最大有效期（分钟，默认4320）
 *
 * @版本: v1.0
 * @日期: 2026-05-14
 */

namespace app\common;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use think\facade\Config;
use think\facade\Log;

/**
 * JWT Token 工具类
 *
 * 提供 JWT Token 的完整生命周期管理功能，包括生成、解析、验证和刷新
 * 采用静态方法设计，无需实例化即可使用
 *
 * @property static string|null $cachedSecret 缓存的签名密钥，避免重复读取配置
 */
class JwtToken
{
    /** @var string|null 缓存的签名密钥，提升性能 */
    private static ?string $cachedSecret = null;

    /**
     * 生成 JWT Token
     *
     * @描述: 根据传入的载荷数据生成带签名的 JWT 令牌
     *       自动添加标准声明（exp、nbf、iat），支持自定义载荷扩展
     *
     * @参数:
     *   - payload (array): Token 载荷数据
     *     必填项：无（可传入空数组）
     *     推荐项：
     *       - user_id (int): 用户ID
     *       - username (string): 用户名
     *       - type (string): Token类型，'access' 或 'refresh'
     *
     * @返回: string 生成的 JWT Token 字符串
     *
     * @业务逻辑:
     *   1. 获取签名密钥（优先从缓存读取）
     *   2. 读取 Token 有效期配置（TTL）
     *   3. 计算过期时间戳 = 当前时间 + TTL * 60
     *   4. 添加标准声明：
     *      - exp: 过期时间
     *      - nbf: 生效时间（默认当前时间）
     *      - iat: 签发时间（如果未设置）
     *   5. 使用 HS256 算法签名并返回
     *
     * @Token 结构:
     *   eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.
     *   eyJ1c2VyX2lkIjoxLCJ1c2VybmFtZSI6ImFkbWluIiwiaWF0IjoxNjYwMDAwMDAwfQ.
     *   签名部分（Base64URL编码的HMAC-SHA256）
     *
     * @示例:
     *   $payload = [
     *       'user_id' => 1,
     *       'username' => 'admin',
     *       'type' => 'access'
     *   ];
     *   $token = JwtToken::generate($payload);
     *
     *   // 使用自定义 TTL（分钟）生成 Refresh Token
     *   $refreshToken = JwtToken::generate($payload, 10080);
     */
    public static function generate(array $payload, ?int $ttlMinutes = null): string
    {
        $secret = self::getSecret();
        $ttl = $ttlMinutes ?? (int) (Config::get('jwt.ttl') ?: env('JWT_TTL', 1440));

        $now = time();
        $expire = $now + ($ttl * 60);

        $payload['exp'] = $expire;
        $payload['nbf'] = $now;
        if (!isset($payload['iat'])) {
            $payload['iat'] = $now;
        }

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * 解析 JWT Token
     *
     * @描述: 解析并验证 JWT Token 的合法性，验证签名和有效期
     *       解析成功返回完整的载荷数据，失败抛出异常
     *
     * @参数:
     *   - token (string): JWT Token 字符串
     *
     * @返回: array Token 载荷数据（关联数组）
     *
     * @异常处理:
     *   - RuntimeException: Token 已过期
     *   - RuntimeException: Token 签名验证失败
     *   - RuntimeException: Token 解析失败（格式错误等）
     *
     * @业务逻辑:
     *   1. 获取签名密钥（优先从缓存读取）
     *   2. 使用 HS256 算法解码 Token
     *   3. 验证签名是否正确
     *   4. 验证 Token 是否在有效期内
     *   5. 返回解码后的载荷数据
     *
     * @示例:
     *   try {
     *       $payload = JwtToken::parse($token);
     *       $userId = $payload['user_id'];
     *   } catch (\RuntimeException $e) {
     *       // Token 无效处理
     *   }
     */
    public static function parse(string $token): array
    {
        $secret = self::getSecret();

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new \RuntimeException('Token 已过期');
        } catch (SignatureInvalidException $e) {
            throw new \RuntimeException('Token 签名验证失败');
        } catch (\Exception $e) {
            throw new \RuntimeException('Token 解析失败：' . $e->getMessage());
        }
    }

    /**
     * 验证 JWT Token 有效性
     *
     * @描述: 快速验证 Token 是否有效，不抛出异常，返回布尔值
     *       适用于需要批量验证 Token 的场景
     *
     * @参数:
     *   - token (string): JWT Token 字符串
     *
     * @返回: bool true-Token有效，false-Token无效
     *
     * @业务逻辑:
     *   1. 调用 parse() 方法尝试解析 Token
     *   2. 解析成功返回 true
     *   3. 解析失败（任意异常）返回 false
     *   4. 失败原因记录到日志（warning级别）
     *
     * @使用场景:
     *   - 中间件快速验证
     *   - 批量 Token 状态检查
     *   - 无需区分具体错误类型的场景
     *
     * @示例:
     *   if (JwtToken::validate($token)) {
     *       // Token 有效，继续处理
     *   } else {
     *       // Token 无效，拒绝访问
     *   }
     */
    public static function validate(string $token): bool
    {
        try {
            self::parse($token);
            return true;
        } catch (\Exception $e) {
            Log::warning('JWT 验证失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 刷新 JWT Token
     *
     * @描述: 使用 Refresh Token 获取新的 Access Token
     *       验证 Refresh Token 的有效性后，生成新的访问令牌
     *       支持 Token 续期，防止用户频繁登录
     *
     * @参数:
     *   - token (string): Refresh Token 字符串
     *
     * @返回: string 新的 Access Token 字符串
     *
     * @前置条件:
     *   - Token 的 type 字段必须为 'refresh'
     *   - Token 不能超过最大刷新有效期（max_ttl）
     *   - Token 的生效时间（nbf）不能晚于当前时间
     *
     * @异常处理:
     *   - RuntimeException: 非刷新令牌，无法刷新
     *   - RuntimeException: Token 尚未生效
     *   - RuntimeException: Token 已超过最大刷新有效期
     *
     * @业务逻辑:
     *   1. 解析 Refresh Token 获取载荷
     *   2. 验证 Token 类型是否为 refresh
     *   3. 验证 Token 是否在生效期内（nbf）
     *   4. 验证 Token 是否在最大刷新有效期内
     *   5. 保留原始签发时间（iat），用于后续验证
     *   6. 清除过期和生效声明，重新生成 Token
     *   7. 返回新的 Access Token
     *
     * @配置项:
     *   - jwt.max_ttl: Refresh Token 最大有效期（分钟），默认4320（3天）
     *
     * @使用场景:
     *   - Access Token 即将过期时，前端调用此方法获取新 Token
     *   - 实现 Token 自动续期功能
     *
     * @示例:
     *   try {
     *       $newAccessToken = JwtToken::refresh($refreshToken);
     *       // 返回新的 Access Token 给前端
     *   } catch (\RuntimeException $e) {
     *       // Refresh Token 无效，需重新登录
     *   }
     */
    public static function refresh(string $token): string
    {
        $payload = self::parse($token);

        if (($payload['type'] ?? '') !== 'refresh') {
            throw new \RuntimeException('非刷新令牌，无法刷新');
        }

        $now = time();
        if (isset($payload['nbf']) && $payload['nbf'] > $now) {
            throw new \RuntimeException('Token 尚未生效，无法刷新');
        }

        $originalIat = $payload['iat'] ?? $now;
        $maxTtl = (int) (Config::get('jwt.max_ttl') ?: env('JWT_MAX_TTL', 4320));
        if ($now - $originalIat > $maxTtl * 60) {
            throw new \RuntimeException('Token 已超过最大刷新有效期，请重新登录');
        }

        $payload['iat'] = $originalIat;
        unset($payload['exp'], $payload['nbf']);

        return self::generate($payload);
    }

    /**
     * 获取 JWT 签名密钥
     *
     * @描述: 从配置或环境变量获取 JWT 签名密钥
     *       优先使用缓存的密钥，避免重复读取配置
     *
     * @返回: string JWT 签名密钥
     *
     * @密钥来源优先级:
     *   1. 缓存中的密钥（优先）
     *   2. Config::get('jwt.secret')
     *   3. env('JWT_SECRET')
     *
     * @异常处理:
     *   - RuntimeException: JWT密钥不能为空
     *   - RuntimeException: JWT密钥长度至少32位
     *
     * @安全提示:
     *   - 生产环境必须使用强随机密钥（至少64字符）
     *   - 生成命令：php -r "echo bin2hex(random_bytes(32));"
     */
    private static function getSecret(): string
    {
        if (self::$cachedSecret !== null) {
            return self::$cachedSecret;
        }

        $secret = Config::get('jwt.secret') ?: env('JWT_SECRET');
        self::validateSecret($secret);
        self::$cachedSecret = $secret;

        return $secret;
    }

    /**
     * 验证 JWT 密钥有效性
     *
     * @描述: 验证密钥是否满足最低安全要求
     *       确保密钥不为空且长度足够
     *
     * @参数:
     *   - secret (string): 待验证的密钥字符串
     *
     * @返回: void
     *
     * @验证规则:
     *   - 密钥不能为空字符串
     *   - 密钥长度至少 32 个字符
     *
     * @异常处理:
     *   - RuntimeException: JWT密钥不能为空
     *   - RuntimeException: JWT密钥长度至少32位
     */
    private static function validateSecret(string $secret): void
    {
        if (empty($secret)) {
            throw new \RuntimeException('JWT密钥不能为空');
        }
        if (strlen($secret) < 32) {
            throw new \RuntimeException('JWT密钥长度至少32位');
        }
    }

    /**
     * 清除密钥缓存
     *
     * @描述: 清除内部缓存的签名密钥
     *       用于密钥变更后强制重新加载
     *
     * @返回: void
     *
     * @使用场景:
     *   - JWT 密钥配置变更后
     *   - 运维操作需要刷新密钥
     *   - 测试环境切换配置
     *
     * @示例:
     *   // 修改密钥后调用
     *   JwtToken::clearCache();
     *   JwtToken::generate($payload); // 使用新密钥
     */
    public static function clearCache(): void
    {
        self::$cachedSecret = null;
    }
}
