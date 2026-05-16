# RBAC 权限管理系统 - 后端

> 基于 ThinkPHP 8 构建的企业级 RBAC（基于角色的访问控制）权限管理后端服务

## 项目简介

本项目是企业级 RBAC 权限管理系统的后端部分，提供完整的用户、角色、菜单、部门、接口、字典等权限管理 API，同时支持微信小程序端接入。系统采用前后端分离架构，支持三级权限控制（菜单权限、按钮权限、接口权限）和五级数据权限（全部、本部门、本部门及以下、仅本人、自定义）。

### 核心特性

- **三级权限控制**：菜单权限、按钮权限、接口权限精细化管理
- **五级数据权限**：支持全部、本部门、本部门及以下、仅本人、自定义等数据范围
- **JWT 认证**：基于 firebase/php-jwt 的 Token 认证，支持 Token 刷新
- **API 权限中间件**：接口级别的权限校验
- **操作日志审计**：自动记录操作行为，支持日志统计与清理
- **微信小程序**：集成 EasyWeChat，支持小程序登录与业务交互
- **字典管理**：灵活的键值对数据管理，支持按编码获取字典

## 技术栈

| 类别 | 技术 | 版本 |
|------|------|------|
| 核心框架 | ThinkPHP | ^8.0 |
| PHP 版本 | PHP | >=8.0 |
| JWT 库 | firebase/php-jwt | ^7.0 |
| Redis 客户端 | predis/predis | ^3.4 |
| 微信 SDK | w7corp/easywechat | ^6.0 |
| ORM | topthink/think-orm | ^4.0 |
| 数据库 | MySQL | 5.7+ / 8.0 |
| 缓存 | Redis | 6.0+ |

## 环境要求

- PHP >= 8.0
- MySQL >= 5.7（推荐 8.0）
- Redis >= 6.0
- Composer >= 2.0
- PHP 扩展：pdo_mysql、mbstring、json、openssl、redis

## 快速开始

### 1. 安装依赖

```bash
cd backend
composer install
```

### 2. 环境配置

复制环境变量模板文件并根据实际情况修改：

```bash
cp .env.example .env
```

关键配置项说明：

| 变量名 | 说明 | 默认值 |
|--------|------|--------|
| `APP_DEBUG` | 调试模式 | false |
| `DB_CONNECTION` | 数据库类型 | mysql |
| `DB_HOSTNAME` | 数据库主机 | 127.0.0.1 |
| `DB_PORT` | 数据库端口 | 3306 |
| `DB_DATABASE` | 数据库名 | rbac_system |
| `DB_USERNAME` | 数据库用户名 | root |
| `DB_PASSWORD` | 数据库密码 | - |
| `DB_CHARSET` | 数据库字符集 | utf8mb4 |
| `JWT_SECRET` | JWT 密钥（必须修改） | - |
| `JWT_TTL` | Token 有效期（分钟） | 1440 |
| `JWT_REFRESH_TTL` | 刷新 Token 有效期（分钟） | 10080 |
| `REDIS_HOST` | Redis 主机 | 127.0.0.1 |
| `REDIS_PORT` | Redis 端口 | 6379 |
| `REDIS_PASSWORD` | Redis 密码 | - |
| `REDIS_DB` | Redis 数据库 | 0 |
| `CACHE_DRIVER` | 缓存驱动 | redis |
| `WX_MINIAPP_APPID` | 微信小程序 AppID | - |
| `WX_MINIAPP_SECRET` | 微信小程序 Secret | - |

> **安全提示**：生产环境必须通过环境变量 `JWT_SECRET` 设置强随机密钥（至少 64 字符），可使用 `php -r "echo bin2hex(random_bytes(32));"` 生成。

### 3. 初始化数据库

按顺序执行数据库迁移脚本：

```bash
mysql -u root -p < database/migrations/001_init_schema.sql
mysql -u root -p < database/migrations/002_init_data.sql
```

> **注意**：
> - 数据库迁移脚本位于 `database/migrations/` 目录
> - 建议在生产环境中仔细审查每个迁移脚本，确保数据安全

默认管理员账号：`admin` / `123456`

### 4. 启动服务

**开发环境**：

```bash
php think run
```

默认监听 `http://localhost:8000`

**生产环境**：

建议使用 Nginx + PHP-FPM 部署，将网站根目录指向 `public/` 目录。

## 项目结构

```
backend/
├── app/
│   ├── admin/                    # 后台管理模块
│   │   ├── controller/           # 控制器
│   │   │   ├── ApiController.php     # 接口管理
│   │   │   ├── AuthController.php    # 认证管理
│   │   │   ├── DashboardController.php # 仪表盘
│   │   │   ├── DepartmentController.php # 部门管理
│   │   │   ├── DictController.php    # 字典管理
│   │   │   ├── LoginLogController.php # 登录日志
│   │   │   ├── MenuButtonController.php # 菜单按钮管理
│   │   │   ├── MenuController.php    # 菜单管理
│   │   │   ├── OperationLogController.php # 操作日志
│   │   │   ├── ProfileController.php # 个人信息
│   │   │   ├── RoleController.php    # 角色管理
│   │   │   └── UserController.php    # 用户管理
│   │   ├── event/                # 事件
│   │   ├── middleware/           # 中间件
│   │   │   ├── ApiPermission.php     # API 权限校验
│   │   │   ├── AuthCheck.php         # 登录认证
│   │   │   └── RecordOperate.php     # 操作记录
│   │   ├── service/              # 服务层
│   │   │   ├── AdminAuthService.php  # 管理员认证服务
│   │   │   ├── ApiService.php        # 接口服务
│   │   │   ├── DepartmentService.php # 部门服务
│   │   │   ├── LoginSecurityService.php # 登录安全服务
│   │   │   ├── UserAgentParserService.php # 用户代理解析服务
│   │   │   └── UserService.php       # 用户服务
│   │   └── validate/             # 验证器
│   ├── common/                   # 公共模块
│   │   ├── AdminAuth.php         # 管理员认证
│   │   ├── BaseController.php    # 基础控制器
│   │   ├── BaseModel.php         # 基础模型
│   │   ├── BaseValidate.php      # 基础验证器
│   │   ├── JwtToken.php          # JWT Token 处理
│   │   ├── SimpleCache.php       # 简单缓存实现
│   │   └── exception/            # 异常处理
│   ├── middleware/                # 全局中间件
│   │   └── AllowCrossDomain.php  # 跨域处理
│   ├── miniapp/                  # 微信小程序模块
│   │   ├── controller/           # 控制器
│   │   │   ├── AuthController.php    # 认证控制器
│   │   │   ├── BusinessController.php # 业务控制器
│   │   │   ├── HomeController.php    # 首页控制器
│   │   │   ├── MiniappBaseController.php # 小程序基础控制器
│   │   │   └── ProfileController.php  # 个人信息控制器
│   │   ├── middleware/           # 中间件
│   │   │   └── MiniappAuth.php       # 小程序认证
│   │   ├── service/              # 服务层
│   │   │   ├── BusinessService.php   # 业务服务
│   │   │   ├── HomeService.php       # 首页服务
│   │   │   ├── MiniappAuthService.php # 小程序认证服务
│   │   │   ├── TokenBlacklistService.php # Token黑名单服务
│   │   │   └── WechatService.php     # 微信服务
│   │   └── validate/             # 验证器
│   └── model/                    # 数据模型
│       ├── Api.php               # 接口模型
│       ├── Business.php              # 业务模型
│       ├── BusinessInteraction.php   # 业务交互模型
│       ├── Department.php            # 部门模型
│       ├── DictData.php              # 字典数据模型
│       ├── DictType.php              # 字典类型模型
│       ├── LoginLog.php              # 登录日志模型
│       ├── Menu.php                  # 菜单模型
│       ├── MenuButton.php            # 菜单按钮模型
│       ├── OperationLog.php          # 操作日志模型
│       ├── Role.php                  # 角色模型
│       ├── User.php                  # 用户模型
│       ├── UserDept.php              # 用户部门关联模型
│       ├── WxConfig.php              # 微信配置模型
│       └── WxUser.php                # 微信用户模型
├── config/                       # 配置文件
│   ├── app.php                   # 应用配置
│   ├── app_multi.php             # 多应用配置
│   ├── auth.php                  # 认证配置
│   ├── cache.php                 # 缓存配置
│   ├── database.php              # 数据库配置
│   ├── jwt.php                   # JWT 配置
│   ├── log.php                   # 日志配置
│   ├── middleware.php            # 中间件配置
│   ├── redis.php                 # Redis 配置
│   ├── route.php                 # 路由配置
│   └── wechat.php                # 微信配置
├── database/
│   └── migrations/               # 数据库迁移脚本
│       ├── 001_init_schema.sql   # 表结构初始化
│       └── 002_init_data.sql     # 初始数据
├── public/
│   ├── .htaccess
│   ├── index.php                 # 应用入口
│   └── router.php
├── route/                        # 路由定义
│   ├── admin.php                 # 后台路由
│   ├── miniapp.php               # 小程序路由
│   └── route.php                 # 路由入口
├── tests/                        # 单元测试
│   ├── AdminAuthServiceTest.php
│   ├── AdminAuthTest.php
│   ├── ApiServiceTest.php
│   ├── DepartmentServiceTest.php
│   ├── LoginSecurityServiceTest.php
│   ├── SimpleCacheTest.php
│   ├── UserAgentParserServiceTest.php
│   ├── UserServiceTest.php
│   └── bootstrap.php
├── .env.example                  # 环境变量模板
├── composer.json                 # 依赖配置
├── phpunit.xml                  # PHPUnit 配置
└── think                         # 命令行入口
```

## 服务层架构

项目采用服务层（Service Layer）架构，将业务逻辑从控制器中分离出来，提高代码的可维护性和可测试性。

### 核心服务

| 服务类 | 职责说明 |
|--------|---------|
| `AdminAuthService` | 后台管理员认证服务，负责登录、登出、Token 管理、资料获取、密码修改、登录安全（失败计数、账户锁定）等核心认证逻辑 |
| `ApiService` | API 接口管理服务，处理接口的增删改查、状态切换、分组查询及按菜单关联查询 |
| `DepartmentService` | 部门管理服务，处理部门的增删改查、树形结构构建、循环引用防护、状态切换、排序调整 |
| `LoginSecurityService` | 登录安全服务，实现登录失败计数、账户锁定机制，防止暴力破解 |
| `UserAgentParserService` | User-Agent 解析服务，从浏览器请求头中提取操作系统和浏览器类型 |
| `UserService` | 用户管理服务，处理用户全生命周期的核心业务逻辑，包括角色分配、多部门关联管理、密码重置、数据导入导出 |

### 设计原则

- **单例模式**：所有服务类采用 `getInstance()` 方法获取实例，与项目现有架构保持一致
- **统一返回格式**：所有服务方法返回统一结果结构 `['success' => bool, 'data' => ..., 'error' => string, 'code' => int]`
- **单一职责**：每个服务类专注于特定的业务领域
- **事务支持**：涉及数据变更的操作在数据库事务中执行，保证数据一致性
- **缓存管理**：数据变更时自动清除相关缓存，确保权限数据一致性

### 小程序服务

| 服务类 | 职责说明 |
|--------|---------|
| `MiniappAuthService` | 小程序认证服务，处理小程序登录、Token 管理、用户信息获取 |
| `WechatService` | 微信服务，封装 EasyWeChat SDK，提供微信接口调用能力 |
| `TokenBlacklistService` | Token 黑名单服务，管理已失效的 Token |
| `BusinessService` | 业务服务，处理小程序业务逻辑 |
| `HomeService` | 首页服务，提供小程序首页数据 |

## API 文档

### 认证接口

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/admin/login` | 管理员登录 |
| POST | `/admin/logout` | 管理员登出 |
| POST | `/admin/refresh-token` | 刷新 Token |
| GET | `/admin/profile` | 获取当前用户信息 |
| PUT | `/admin/password` | 修改密码 |

### 用户管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/users` | 用户列表 |
| GET | `/admin/users/:id` | 用户详情 |
| POST | `/admin/users` | 创建用户 |
| PUT | `/admin/users/:id` | 更新用户 |
| DELETE | `/admin/users/:id` | 删除用户 |
| POST | `/admin/users/:id/assign-roles` | 分配角色 |
| POST | `/admin/users/:id/reset-password` | 重置密码 |
| PUT | `/admin/users/:id/status` | 修改状态 |
| PUT | `/admin/users/:id/depts` | 更新用户部门 |
| POST | `/admin/users/:id/depts` | 添加用户部门 |
| DELETE | `/admin/users/:id/depts/:deptId` | 移除用户部门 |
| GET | `/admin/users/export` | 导出用户 |
| POST | `/admin/users/import` | 导入用户 |

### 角色管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/roles` | 角色列表 |
| GET | `/admin/roles/:id` | 角色详情 |
| POST | `/admin/roles` | 创建角色 |
| PUT | `/admin/roles/:id` | 更新角色 |
| DELETE | `/admin/roles/:id` | 删除角色 |
| POST | `/admin/roles/:id/assign-menus` | 分配菜单权限 |
| POST | `/admin/roles/:id/assign-buttons` | 分配按钮权限 |
| POST | `/admin/roles/:id/assign-apis` | 分配接口权限 |
| PUT | `/admin/roles/:id/data-scope` | 设置数据权限 |
| PUT | `/admin/roles/:id/status` | 修改状态 |

### 菜单管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/menus` | 菜单列表 |
| GET | `/admin/menus/tree` | 菜单树 |
| GET | `/admin/menus/:id` | 菜单详情 |
| POST | `/admin/menus` | 创建菜单 |
| PUT | `/admin/menus/:id` | 更新菜单 |
| DELETE | `/admin/menus/:id` | 删除菜单 |
| PUT | `/admin/menus/:id/status` | 修改状态 |
| GET | `/admin/menus/:id/buttons` | 获取菜单按钮 |
| POST | `/admin/menus/:id/buttons` | 创建菜单按钮 |
| PUT | `/admin/menus/:id/buttons/:buttonId` | 更新菜单按钮 |
| DELETE | `/admin/menus/:id/buttons/:buttonId` | 删除菜单按钮 |

### 菜单按钮管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/menu-buttons` | 菜单按钮列表 |
| GET | `/admin/menu-buttons/:id` | 菜单按钮详情 |
| PUT | `/admin/menu-buttons/:id/status` | 修改菜单按钮状态 |
| POST | `/admin/menu-buttons/batch-status` | 批量修改菜单按钮状态 |
| POST | `/admin/menu-buttons/batch-delete` | 批量删除菜单按钮 |

> **缓存说明**：菜单和按钮的创建、更新、删除操作会自动清除所有活跃用户的菜单缓存（`user_menu_tree_*`、`user_menu_codes_*`、`user_api_codes_*`），确保前端能立即获取最新权限数据。

### 部门管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/depts` | 部门列表 |
| GET | `/admin/depts/tree` | 部门树 |
| GET | `/admin/depts/:id` | 部门详情 |
| POST | `/admin/depts` | 创建部门 |
| PUT | `/admin/depts/:id` | 更新部门 |
| DELETE | `/admin/depts/:id` | 删除部门 |
| PUT | `/admin/depts/:id/status` | 修改状态 |
| PUT | `/admin/depts/:id/sort` | 修改排序 |
| GET | `/admin/depts/:id/users` | 部门用户列表 |

### 接口管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/apis` | 接口列表 |
| GET | `/admin/apis/groups` | 接口分组 |
| GET | `/admin/apis/menu/:menuId` | 按菜单获取接口 |
| GET | `/admin/apis/:id` | 接口详情 |
| POST | `/admin/apis` | 创建接口 |
| PUT | `/admin/apis/:id` | 更新接口 |
| DELETE | `/admin/apis/:id` | 删除接口 |
| PUT | `/admin/apis/:id/status` | 修改状态 |

### 字典管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/dict/types` | 字典类型列表 |
| POST | `/admin/dict/types` | 创建字典类型 |
| GET | `/admin/dict/types/:id` | 字典类型详情 |
| PUT | `/admin/dict/types/:id` | 更新字典类型 |
| DELETE | `/admin/dict/types/:id` | 删除字典类型 |
| PUT | `/admin/dict/types/:id/status` | 切换字典类型状态 |
| GET | `/admin/dict/data` | 字典数据列表 |
| POST | `/admin/dict/data` | 创建字典数据 |
| GET | `/admin/dict/data/:id` | 字典数据详情 |
| PUT | `/admin/dict/data/:id` | 更新字典数据 |
| DELETE | `/admin/dict/data/:id` | 删除字典数据 |
| PUT | `/admin/dict/data/:id/status` | 切换字典数据状态 |
| POST | `/admin/dict/data/sort` | 字典数据排序 |
| GET | `/admin/dict/code/:code` | 按编码获取字典 |

### 日志管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/login-logs` | 登录日志列表 |
| GET | `/admin/login-logs/stats` | 登录日志统计 |
| POST | `/admin/login-logs/clean` | 清理登录日志 |
| POST | `/admin/login-logs/clear` | 清空登录日志（超管） |
| POST | `/admin/login-logs/delete` | 批量删除登录日志 |
| GET | `/admin/operation-logs` | 操作日志列表 |
| GET | `/admin/operation-logs/stats` | 操作日志统计 |
| POST | `/admin/operation-logs/clean` | 清理操作日志 |
| POST | `/admin/operation-logs/clear` | 清空操作日志（超管） |
| POST | `/admin/operation-logs/delete` | 批量删除操作日志 |

### 仪表盘

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/dashboard/statistics` | 统计数据 |

### 个人中心

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/profile` | 获取个人信息 |
| PUT | `/admin/profile` | 更新个人信息 |
| POST | `/admin/profile/avatar` | 上传头像 |
| PUT | `/admin/profile/password` | 修改密码 |

### 微信小程序接口

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/miniapp/auth/login` | 小程序登录 |
| POST | `/miniapp/auth/refresh-token` | 刷新 Token |
| POST | `/miniapp/auth/phone` | 获取手机号 |
| POST | `/miniapp/auth/update-profile` | 更新用户信息 |
| POST | `/miniapp/auth/logout` | 小程序登出 |
| GET | `/miniapp/home/index` | 首页数据 |
| GET | `/miniapp/business/list` | 业务列表 |
| GET | `/miniapp/business/detail/:id` | 业务详情 |
| POST | `/miniapp/business/operate` | 业务操作 |
| GET | `/miniapp/profile/show` | 个人信息 |
| PUT | `/miniapp/profile/update` | 更新个人信息 |
| POST | `/miniapp/profile/avatar` | 上传头像 |

### 通用响应格式

```json
{
  "code": 200,
  "msg": "success",
  "data": {}
}
```

分页响应格式：

```json
{
  "code": 200,
  "msg": "success",
  "data": {
    "list": [],
    "pagination": {
      "page": 1,
      "page_size": 10,
      "total": 100,
      "total_pages": 10
    }
  }
}
```

## 常用命令

| 命令 | 说明 |
|------|------|
| `php think run` | 启动开发服务器 |
| `php think service:discover` | 发现服务 |
| `composer install` | 安装依赖 |
| `composer update` | 更新依赖 |

## 测试

项目包含完整的单元测试，覆盖核心服务层的功能测试。

### 测试文件

| 测试文件 | 覆盖范围 |
|---------|---------|
| `AdminAuthServiceTest.php` | 管理员认证服务测试 |
| `AdminAuthTest.php` | 管理员认证测试 |
| `ApiServiceTest.php` | API服务测试 |
| `DepartmentServiceTest.php` | 部门服务测试 |
| `LoginSecurityServiceTest.php` | 登录安全服务测试 |
| `SimpleCacheTest.php` | 简单缓存测试 |
| `UserAgentParserServiceTest.php` | 用户代理解析服务测试 |
| `UserServiceTest.php` | 用户服务测试 |

### 运行测试

```bash
# 运行所有测试
./vendor/bin/phpunit

# 运行单个测试文件
./vendor/bin/phpunit tests/AdminAuthServiceTest.php
```

## 部署说明

### Nginx 配置示例

```nginx
server {
    listen       80;
    server_name  api.your-domain.com;
    root         /var/www/rbac/backend/public;
    index        index.php;

    location / {
        if (!-e $request_filename) {
            rewrite ^(.*)$ /index.php?s=/$1 last;
        }
    }

    location ~ \.php$ {
        fastcgi_pass   unix:/run/php/php8.1-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }
}
```

### 安全注意事项

1. 生产环境必须修改 `JWT_SECRET` 为强随机密钥
2. 修改默认管理员密码
3. 关闭 `APP_DEBUG`
4. 确保 `.env` 文件不被版本控制追踪
5. Redis 建议设置密码
6. 数据库使用最小权限账户

## 贡献指南

### 代码规范

- 遵循 PSR-4 自动加载规范
- 遵循 PSR-12 编码风格
- 控制器保持精简，业务逻辑放在 Service 层
- 使用验证器校验输入参数

### 命名规范

- 控制器：PascalCase（如 `UserController`）
- 方法：camelCase（如 `getUserList`）
- 数据库表：snake_case 带前缀（如 `sys_user`）
- 模型：PascalCase（如 `User`）
- 路由：kebab-case（如 `/admin/login-logs`）

### Git 提交规范

提交信息格式：

```
<type>(<scope>): <subject>
```

常用 type：

- `feat`: 新功能
- `fix`: 修复 Bug
- `docs`: 文档更新
- `style`: 代码格式调整
- `refactor`: 重构
- `test`: 测试相关
- `chore`: 构建/工具链相关

## 相关资源

- [ThinkPHP 8 官方文档](https://www.kancloud.cn/manual/thinkphp8/1842723)
- [firebase/php-jwt](https://github.com/firebase/php-jwt)
- [EasyWeChat](https://easywechat.com/)
- [Predis](https://github.com/predis/predis)

---

*项目版本：v1.0*
*技术栈：ThinkPHP 8 + MySQL + Redis + JWT*
*最后更新：2026-05-16*
