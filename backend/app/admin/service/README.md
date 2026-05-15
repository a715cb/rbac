# 后台管理服务层

## 概述

本文档描述后台管理系统认证模块的服务层架构设计。服务层负责处理所有业务逻辑，控制器仅负责请求接收和响应处理，实现了关注点分离。

## 架构设计

### 分层架构

```
┌─────────────────────────────────────────┐
│         Controller Layer                │
│    (AuthController - 瘦控制器)           │
│  - 请求参数提取                         │
│  - 输入验证                             │
│  - 响应格式化                           │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│          Service Layer                  │
│         (业务逻辑层)                     │
│  - AdminAuthService                     │
│  - LoginSecurityService                  │
│  - UserAgentParserService                │
│  - 业务规则处理                         │
│  - 数据验证                             │
│  - 事务管理                             │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│         Model / Component Layer         │
│    (数据访问层/基础组件)                 │
│  - User Model                           │
│  - Role Model                           │
│  - LoginLog Model                       │
│  - JwtToken                             │
│  - AdminAuth                            │
│  - SimpleCache                          │
└─────────────────────────────────────────┘
```

### 设计原则

1. **单一职责原则**: 每个服务类只负责一个业务领域
2. **依赖注入**: 服务间通过依赖注入实现解耦
3. **单例模式**: 服务类使用单例模式，确保全局唯一实例
4. **统一返回格式**: 所有服务方法返回统一结构的数组

## 服务详解

### 1. AdminAuthService - 认证核心服务

**职责**: 处理所有认证相关的核心业务逻辑

**功能模块**:
- 用户登录验证
- 用户登出处理
- Token刷新
- 用户资料获取
- 密码修改

**使用示例**:

```php
use app\admin\service\AdminAuthService;

// 获取服务实例
$authService = AdminAuthService::getInstance();

// 用户登录
$result = $authService->login($username, $password, $ip, $userAgent);
if ($result['success']) {
    $token = $result['data']['access_token'];
} else {
    echo $result['error'];
}

// 用户登出
$authService->logout($userId);

// 刷新Token
$result = $authService->refreshToken($refreshToken);

// 获取用户资料
$result = $authService->getProfile($userId);
if ($result['success']) {
    $profile = $result['data'];
}

// 修改密码
$result = $authService->changePassword($userId, $oldPassword, $newPassword);
```

**依赖服务**:
- LoginSecurityService
- UserAgentParserService

### 2. LoginSecurityService - 登录安全服务

**职责**: 处理登录安全相关的业务逻辑

**功能模块**:
- 登录失败次数计数
- 账户锁定检查
- 失败记录清除
- 安全配置获取

**使用示例**:

```php
use app\admin\service\LoginSecurityService;

$security = LoginSecurityService::getInstance();

// 检查登录失败次数
$result = $security->checkLoginFailTimes($username);
if ($result['locked']) {
    echo "账户已锁定";
}

// 检查账户是否锁定
if ($security->isAccountLocked($username)) {
    echo "请稍后再试";
}

// 获取锁定剩余时间（分钟）
$lockMinutes = $security->getLockDurationMinutes();

// 清除失败记录（登录成功后）
$security->clearLoginFailTimes($username);
```

### 3. UserAgentParserService - User-Agent解析服务

**职责**: 解析HTTP User-Agent字符串

**功能模块**:
- 操作系统类型识别
- 浏览器类型识别
- 完整User-Agent信息提取

**使用示例**:

```php
use app\admin\service\UserAgentParserService;

$parser = UserAgentParserService::getInstance();

// 解析操作系统
$os = $parser->parseOs('Mozilla/5.0 (Windows NT 10.0; Win64; x64) ...');
// 返回: "Windows 10"

// 解析浏览器
$browser = $parser->parseBrowser('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0...');
// 返回: "Chrome"

// 解析完整信息
$info = $parser->parse('Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0...');
// 返回: ['os' => 'Windows 10', 'browser' => 'Chrome', 'user_agent' => '...']
```

## 服务调用流程

### 用户登录流程

```
1. 控制器接收请求
   ↓
2. 参数验证（使用Validate类）
   ↓
3. 调用 AdminAuthService->login()
   ├─→ 检查账户是否锁定（LoginSecurityService）
   ├─→ 查询用户（User Model）
   ├─→ 验证密码（password_verify）
   ├─→ 检查密码是否需要重哈希
   ├─→ 获取用户角色（Role Model）
   ├─→ 生成JWT Token（JwtToken）
   ├─→ 更新登录信息（User Model）
   ├─→ 清除失败记录（LoginSecurityService）
   └─→ 记录登录日志（LoginLog Model + UserAgentParserService）
   ↓
4. 返回统一格式的响应
```

### Token刷新流程

```
1. 控制器接收refresh_token
   ↓
2. 调用 AdminAuthService->refreshToken()
   ├─→ 解析Token（JwtToken）
   ├─→ 验证Token类型（必须为refresh）
   ├─→ 查询用户状态（User Model）
   └─→ 生成新的Token对
   ↓
3. 返回新的Token
```

## 统一返回格式

所有服务方法返回统一格式的数组：

```php
// 成功响应
[
    'success' => true,
    'data' => [
        // 业务数据
    ],
]

// 失败响应
[
    'success' => false,
    'error' => '错误信息',
    'code' => 401,
]
```

### HTTP状态码映射

| 业务状态 | HTTP状态码 | 说明 |
|---------|-----------|------|
| 成功 | 200 | 操作成功 |
| 参数错误 | 400 | 请求参数错误 |
| 参数校验失败 | 422 | 数据格式验证失败 |
| 未认证 | 401 | 未登录或Token无效 |
| 禁止访问 | 403 | 权限不足或账户被禁用 |
| 资源不存在 | 404 | 用户或资源不存在 |

## 测试覆盖

### 单元测试

为每个服务类创建了完整的单元测试：

1. **UserAgentParserServiceTest**
   - 操作系统解析测试（Windows/macOS/Linux/iOS/Android）
   - 浏览器解析测试（Chrome/Firefox/Safari/Edge/IE/Opera）
   - 边界情况测试（空字符串、未知类型）
   - 大小写不敏感测试

2. **LoginSecurityServiceTest**
   - 登录失败计数测试
   - 账户锁定逻辑测试
   - 失败记录清除测试
   - 配置参数验证测试

3. **AdminAuthServiceTest**
   - 服务初始化测试
   - 单例模式测试
   - 方法返回结构测试
   - 错误处理测试

### 运行测试

```bash
cd backend
./vendor/bin/phpunit tests/
```

## 性能优化

### 1. 单例模式

所有服务使用单例模式，避免重复创建实例：

```php
private static ?AdminAuthService $instance = null;

public static function getInstance(): self
{
    if (self::$instance === null) {
        self::$instance = new self();
    }
    return self::$instance;
}
```

### 2. 依赖缓存

登录安全检查使用SimpleCache缓存失败次数，减少数据库查询。

### 3. 权限缓存

AdminAuth使用内存缓存权限数据，避免重复查询。

## 安全性考虑

### 1. 密码安全

- 使用PHP原生password_hash/password_verify
- 自动检测并更新弱哈希算法
- 原密码验证防止账户盗用

### 2. Token安全

- JWT HS256签名
- 双重Token机制（Access + Refresh）
- Token类型区分，防止Token误用

### 3. 登录安全

- 登录失败计数
- 账户自动锁定
- 完整的登录日志审计

## 扩展性

### 添加新服务

1. 在 `app/admin/service/` 目录创建服务类
2. 实现单例模式
3. 遵循统一返回格式
4. 添加单元测试
5. 更新本文档

### 修改现有服务

1. 确保向后兼容
2. 更新单元测试
3. 记录变更日志

## 最佳实践

### 控制器层

- 仅处理请求/响应
- 不包含业务逻辑
- 参数验证使用Validate类
- 错误处理返回统一格式

### 服务层

- 处理所有业务逻辑
- 抛出业务异常
- 不直接处理HTTP响应
- 保持方法简洁

### 模型层

- 数据访问封装
- 业务规则验证
- 避免跨模型直接调用

## 故障排查

### 常见问题

1. **Token验证失败**
   - 检查JWT密钥配置
   - 确认Token未过期
   - 验证Token签名

2. **账户意外锁定**
   - 检查登录失败计数缓存
   - 确认锁定时长配置
   - 使用clearLoginFailTimes清除记录

3. **服务获取失败**
   - 确认服务类已正确创建
   - 检查命名空间是否正确
   - 验证autoload配置

## 更新日志

### v1.0.0 (2026-05-14)

- 初始版本
- 实现AdminAuthService
- 实现LoginSecurityService
- 实现UserAgentParserService
- 添加单元测试
- 完善文档
