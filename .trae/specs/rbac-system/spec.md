# RBAC 权限系统 - 完整技术规格文档

> 本文档已合并原有 `docs/specs/` 的完整技术细节，包括数据库表结构、API 接口规范、目录结构等，既适合 SOLO Coder 执行，也适合人工查阅。

---

## 1. 项目概述

### 1.1 项目定位
通用可复用企业级 RBAC 权限底座系统，统一支撑自动化办公、企业网站、微信公众号、微信小程序等多项目权限鉴权。

### 1.2 核心特征
- **高通用性**：抽象通用权限模型，适配多种业务场景
- **强可移植性**：模块化设计，支持快速集成到新项目
- **标准化**：遵循行业最佳实践，统一接口规范
- **低耦合**：前后端分离，分层架构，模块间解耦

### 1.3 技术栈

#### 前端技术栈（严格遵循）
- **Vue 3.4+**：前端 JavaScript 框架（Composition API）
- **TypeScript 5.0+**：静态类型检查
- **Vite 5.0+**：前端构建工具
- **Vue Router 4.0+**：前端路由管理
- **Pinia 2.0+**：状态管理
- **Ant Design Vue 4.0+**：UI 组件库
- **Tailwind CSS 3.0+**：实用优先 CSS 框架
- **Less**：CSS 预处理器
- **Axios**：HTTP 客户端
- **Iconify**：图标库
- **VueUse**：Vue Composition API 工具库

#### 后端技术栈（严格遵循）
- **ThinkPHP 8.0+**：PHP 框架
- **MySQL 8.0+**：关系型数据库
- **Redis**：缓存、Session 存储、队列
- **JWT**：Token 认证

---

## 2. 系统架构设计

### 2.1 整体架构图

```
┌─────────────────────────────────────────────────────────────────┐
│                        前端应用层                                 │
│  ┌─────────────┬─────────────┬─────────────┬─────────────────┐   │
│  │  自动化办公  │   企业网站   │  微信公众号  │   微信小程序     │   │
│  └─────────────┴─────────────┴─────────────┴─────────────────┘   │
│                          │                                       │
│              ┌───────────┴───────────┐                           │
│              │    权限 SDK/中间件      │                           │
│              └───────────┬───────────┘                           │
└──────────────────────────┼──────────────────────────────────────┘
                           │ HTTP/API
┌──────────────────────────┼──────────────────────────────────────┐
│                    统一权限网关层                                  │
│              ┌───────────┴───────────┐                          │
│              │    API Gateway         │                          │
│              │  (认证、路由、限流)       │                          │
│              └───────────┬───────────┘                          │
└──────────────────────────┼──────────────────────────────────────┘
                           │
┌──────────────────────────┼──────────────────────────────────────┐
│                      业务服务层                                    │
│  ┌─────────────┬─────────────┬─────────────┬─────────────────┐   │
│  │   用户服务   │   角色服务   │   菜单服务   │   权限服务       │   │
│  └─────────────┴─────────────┴─────────────┴─────────────────┘   │
│                          │                                       │
│              ┌───────────┴───────────┐                           │
│              │     权限计算引擎       │                           │
│              └───────────┬───────────┘                           │
└──────────────────────────┼──────────────────────────────────────┘
                           │
┌──────────────────────────┼──────────────────────────────────────┐
│                      数据访问层                                    │
│  ┌─────────────┬─────────────┬─────────────┬─────────────────┐   │
│  │   MySQL     │    Redis    │    文件     │    消息队列     │   │
│  └─────────────┴─────────────┴─────────────┴─────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 分层架构

#### 前端分层
```
frontend/
├── src/
│   ├── api/              # API 接口层（统一封装 Axios）
│   ├── assets/           # 静态资源
│   ├── components/       # 公共组件
│   ├── composables/      # 组合式函数（VueUse 二次封装）
│   ├── config/           # 配置文件
│   ├── directives/       # 自定义指令（权限按钮）
│   ├── layouts/          # 布局组件
│   ├── router/           # 路由配置（静态 + 动态路由）
│   ├── stores/           # Pinia 状态管理
│   ├── styles/          # 全局样式（Tailwind+Less）
│   ├── types/           # TypeScript 类型定义
│   ├── utils/           # 工具函数
│   └── views/           # 页面组件
```

#### 后端分层（ThinkPHP）
```
backend/
├── app/
│   ├── common/          # 公共模块
│   │   ├── controller/  # 基础控制器
│   │   ├── model/       # 基础模型
│   │   └── validate/    # 验证器
│   ├── admin/           # 后台管理模块
│   │   ├── controller/  # 控制器（认证、用户、角色等）
│   │   ├── model/       # 数据模型
│   │   ├── service/     # 业务逻辑层
│   │   └── validate/    # 验证器
│   ├── api/             # API 接口模块（对外服务）
│   │   ├── controller/  # 控制器
│   │   ├── service/     # 业务逻辑层
│   │   └── validate/    # 验证器
│   ├── middleware/      # 中间件（认证、权限、日志）
│   └── common.php       # 公共函数库
```

---

## 3. 数据库设计

### 3.1 ER 图概述
```
用户表 (user) ←→ 用户角色关联表 (user_role) ←→ 角色表 (role)
  ↓                                                    ↓
  └→ 部门表 (department) ←─────────────────────────── 角色菜单关联表 (role_menu)
                                                    ↓
                                                    菜单表 (menu)
                                                    ↓
                                                    菜单按钮表 (menu_button)
                                                    ↓
                                                    接口表 (api)
```

### 3.2 核心数据表设计（14 张表）

#### 3.2.1 系统管理基础表

**sys_user - 用户表**
```sql
CREATE TABLE `sys_user` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户 ID',
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  `password` VARCHAR(255) NOT NULL COMMENT '密码（加密）',
  `nickname` VARCHAR(50) DEFAULT NULL COMMENT '昵称',
  `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
  `mobile` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
  `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像',
  `gender` TINYINT DEFAULT 0 COMMENT '性别：0 未知 1 男 2 女',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0 禁用 1 正常',
  `dept_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '部门 ID',
  `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '最后登录 IP',
  `last_login_time` DATETIME DEFAULT NULL COMMENT '最后登录时间',
  `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `updated_at` DATETIME DEFAULT NULL COMMENT '更新时间',
  `deleted_at` DATETIME DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_dept_id` (`dept_id`),
  KEY `idx_status` (`status`),
  KEY `idx_mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';
```

**sys_department - 部门表**
```sql
CREATE TABLE `sys_department` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '部门 ID',
  `parent_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父部门 ID',
  `name` VARCHAR(50) NOT NULL COMMENT '部门名称',
  `code` VARCHAR(50) NOT NULL COMMENT '部门编码',
  `leader` VARCHAR(50) DEFAULT NULL COMMENT '负责人',
  `phone` VARCHAR(20) DEFAULT NULL COMMENT '联系电话',
  `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0 禁用 1 正常',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `updated_at` DATETIME DEFAULT NULL COMMENT '更新时间',
  `deleted_at` DATETIME DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='部门表';
```

**sys_role - 角色表**
```sql
CREATE TABLE `sys_role` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '角色 ID',
  `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
  `code` VARCHAR(50) NOT NULL COMMENT '角色编码',
  `data_scope` TINYINT NOT NULL DEFAULT 1 COMMENT '数据权限：1 全部 2 本部门 3 本部门及以下 4 仅本人 5 自定义',
  `data_scope_dept_ids` VARCHAR(500) DEFAULT NULL COMMENT '自定义数据权限部门 ID 列表',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0 禁用 1 正常',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `updated_at` DATETIME DEFAULT NULL COMMENT '更新时间',
  `deleted_at` DATETIME DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';
```

**sys_user_role - 用户角色关联表**
```sql
CREATE TABLE `sys_user_role` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键 ID',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户 ID',
  `role_id` BIGINT UNSIGNED NOT NULL COMMENT '角色 ID',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role` (`user_id`, `role_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户角色关联表';
```

#### 3.2.2 菜单权限表

**sys_menu - 菜单表**
```sql
CREATE TABLE `sys_menu` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '菜单 ID',
  `parent_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父菜单 ID',
  `name` VARCHAR(50) NOT NULL COMMENT '菜单名称',
  `code` VARCHAR(100) NOT NULL COMMENT '菜单标识',
  `path` VARCHAR(200) DEFAULT NULL COMMENT '路由路径',
  `icon` VARCHAR(100) DEFAULT NULL COMMENT '图标',
  `component` VARCHAR(255) DEFAULT NULL COMMENT '组件路径',
  `menu_type` TINYINT NOT NULL COMMENT '菜单类型：1 目录 2 菜单 3 按钮',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `visible` TINYINT DEFAULT 1 COMMENT '显示状态：0 隐藏 1 显示',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0 禁用 1 正常',
  `keep_alive` TINYINT DEFAULT 1 COMMENT '是否缓存：0 否 1 是',
  `always_show` TINYINT DEFAULT 1 COMMENT '是否总是显示：0 否 1 是',
  `breadcrumb` TINYINT DEFAULT 1 COMMENT '是否显示面包屑：0 否 1 是',
  `active_menu` VARCHAR(255) DEFAULT NULL COMMENT '高亮菜单',
  `is_external` TINYINT DEFAULT 0 COMMENT '是否外链：0 否 1 是',
  `is_frame` TINYINT DEFAULT 1 COMMENT '是否 iframe：0 否 1 是',
  `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `updated_at` DATETIME DEFAULT NULL COMMENT '更新时间',
  `deleted_at` DATETIME DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_menu_type` (`menu_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='菜单表';
```

**sys_role_menu - 角色菜单关联表**
```sql
CREATE TABLE `sys_role_menu` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键 ID',
  `role_id` BIGINT UNSIGNED NOT NULL COMMENT '角色 ID',
  `menu_id` BIGINT UNSIGNED NOT NULL COMMENT '菜单 ID',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_menu` (`role_id`, `menu_id`),
  KEY `idx_menu_id` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色菜单关联表';
```

**sys_menu_button - 菜单按钮表**
```sql
CREATE TABLE `sys_menu_button` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '按钮 ID',
  `menu_id` BIGINT UNSIGNED NOT NULL COMMENT '菜单 ID',
  `name` VARCHAR(50) NOT NULL COMMENT '按钮名称',
  `code` VARCHAR(100) NOT NULL COMMENT '按钮编码',
  `icon` VARCHAR(100) DEFAULT NULL COMMENT '按钮图标',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0 禁用 1 正常',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `updated_at` DATETIME DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_menu_button` (`menu_id`, `code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='菜单按钮表';
```

**sys_role_menu_button - 角色菜单按钮关联表**
```sql
CREATE TABLE `sys_role_menu_button` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键 ID',
  `role_id` BIGINT UNSIGNED NOT NULL COMMENT '角色 ID',
  `menu_button_id` BIGINT UNSIGNED NOT NULL COMMENT '菜单按钮 ID',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_button` (`role_id`, `menu_button_id`),
  KEY `idx_button_id` (`menu_button_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色菜单按钮关联表';
```

**sys_api - 接口表**
```sql
CREATE TABLE `sys_api` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '接口 ID',
  `menu_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '所属菜单',
  `name` VARCHAR(100) NOT NULL COMMENT '接口名称',
  `code` VARCHAR(100) NOT NULL COMMENT '接口标识',
  `method` VARCHAR(10) NOT NULL COMMENT '请求方法：GET/POST/PUT/DELETE',
  `path` VARCHAR(200) NOT NULL COMMENT '接口路径',
  `group` VARCHAR(50) DEFAULT NULL COMMENT '接口分组',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0 禁用 1 正常',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `updated_at` DATETIME DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  UNIQUE KEY `uk_method_path` (`method`, `path`),
  KEY `idx_menu_id` (`menu_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='接口表';
```

**sys_role_api - 角色接口关联表**
```sql
CREATE TABLE `sys_role_api` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键 ID',
  `role_id` BIGINT UNSIGNED NOT NULL COMMENT '角色 ID',
  `api_id` BIGINT UNSIGNED NOT NULL COMMENT '接口 ID',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_api` (`role_id`, `api_id`),
  KEY `idx_api_id` (`api_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色接口关联表';
```

#### 3.2.3 系统日志表

**sys_login_log - 登录日志表**
```sql
CREATE TABLE `sys_login_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志 ID',
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  `ip` VARCHAR(50) DEFAULT NULL COMMENT '登录 IP',
  `address` VARCHAR(255) DEFAULT NULL COMMENT '登录地址',
  `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'User-Agent',
  `os` VARCHAR(100) DEFAULT NULL COMMENT '操作系统',
  `browser` VARCHAR(100) DEFAULT NULL COMMENT '浏览器',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '登录状态：0 失败 1 成功',
  `msg` VARCHAR(255) DEFAULT NULL COMMENT '提示消息',
  `login_time` DATETIME NOT NULL COMMENT '登录时间',
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_login_time` (`login_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='登录日志表';
```

**sys_operation_log - 操作日志表**
```sql
CREATE TABLE `sys_operation_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志 ID',
  `user_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '用户 ID',
  `username` VARCHAR(50) DEFAULT NULL COMMENT '用户名',
  `module` VARCHAR(100) DEFAULT NULL COMMENT '操作模块',
  `action` VARCHAR(100) DEFAULT NULL COMMENT '操作功能',
  `method` VARCHAR(10) DEFAULT NULL COMMENT '请求方法',
  `url` VARCHAR(500) DEFAULT NULL COMMENT '请求地址',
  `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP 地址',
  `address` VARCHAR(255) DEFAULT NULL COMMENT '操作地址',
  `param` TEXT DEFAULT NULL COMMENT '请求参数',
  `result` TEXT DEFAULT NULL COMMENT '返回结果',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '操作状态：0 异常 1 正常',
  `error_msg` TEXT DEFAULT NULL COMMENT '错误信息',
  `duration` INT DEFAULT NULL COMMENT '耗时（毫秒）',
  `created_at` DATETIME DEFAULT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_username` (`username`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';
```

#### 3.2.4 扩展功能表

**sys_dict_type - 字典类型表**
```sql
CREATE TABLE `sys_dict_type` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '字典 ID',
  `name` VARCHAR(100) NOT NULL COMMENT '字典名称',
  `code` VARCHAR(100) NOT NULL COMMENT '字典编码',
  `type` VARCHAR(50) NOT NULL DEFAULT 'string' COMMENT '类型：string/number/date/time',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0 禁用 1 正常',
  `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `updated_at` DATETIME DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典类型表';
```

**sys_dict_data - 字典数据表**
```sql
CREATE TABLE `sys_dict_data` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '字典数据 ID',
  `dict_type_id` BIGINT UNSIGNED NOT NULL COMMENT '字典类型 ID',
  `label` VARCHAR(100) NOT NULL COMMENT '字典标签',
  `value` VARCHAR(100) NOT NULL COMMENT '字典键值',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0 禁用 1 正常',
  `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `updated_at` DATETIME DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_value` (`dict_type_id`, `value`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典数据表';
```

**sys_config - 系统配置表**
```sql
CREATE TABLE `sys_config` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '配置 ID',
  `name` VARCHAR(100) NOT NULL COMMENT '配置名称',
  `code` VARCHAR(100) NOT NULL COMMENT '配置编码',
  `value` TEXT DEFAULT NULL COMMENT '配置值',
  `type` VARCHAR(50) NOT NULL DEFAULT 'string' COMMENT '类型：string/number/json/xml',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0 禁用 1 正常',
  `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `updated_at` DATETIME DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';
```

---

## 4. API 接口规范

### 4.1 RESTful 规范

#### 4.1.1 接口路径规范
```
资源类接口：
GET     /api/admin/users          # 获取用户列表
POST    /api/admin/users          # 创建用户
GET     /api/admin/users/{id}     # 获取用户详情
PUT     /api/admin/users/{id}     # 更新用户
DELETE  /api/admin/users/{id}     # 删除用户
POST    /api/admin/users/{id}/reset-password  # 重置密码
POST    /api/admin/users/{id}/assign-roles    # 分配角色

动作类接口：
POST    /api/admin/login          # 登录
POST    /api/admin/logout         # 登出
POST    /api/admin/refresh-token  # 刷新令牌
GET     /api/admin/profile        # 获取个人信息
PUT     /api/admin/password       # 修改密码
```

#### 4.1.2 统一响应格式
```json
成功响应：
{
  "code": 200,
  "message": "success",
  "data": {
    "list": [],
    "pagination": {
      "page": 1,
      "page_size": 10,
      "total": 100,
      "total_pages": 10
    }
  },
  "timestamp": 1677123456789
}

错误响应：
{
  "code": 401,
  "message": "Unauthorized",
  "errors": {
    "username": "用户名不能为空"
  },
  "timestamp": 1677123456789
}
```

#### 4.1.3 HTTP 状态码
- `200` - 请求成功
- `201` - 创建成功
- `204` - 删除成功
- `400` - 请求参数错误
- `401` - 未授权（Token 无效或过期）
- `403` - 禁止访问（无权限）
- `404` - 资源不存在
- `422` - 数据验证失败
- `429` - 请求过于频繁
- `500` - 服务器内部错误

### 4.2 认证授权

#### 4.2.1 JWT 令牌
```json
{
  "header": {
    "alg": "HS256",
    "typ": "JWT"
  },
  "payload": {
    "iss": "RBAC-System",
    "sub": "1",
    "username": "admin",
    "roles": ["super_admin"],
    "exp": 1677206400,
    "iat": 1677120000,
    "jti": "unique-token-id"
  }
}
```

#### 4.2.2 令牌刷新策略
- Access Token：有效期 2 小时
- Refresh Token：有效期 7 天
- 令牌无感刷新：Token 剩余有效期 < 30 分钟时自动刷新

### 4.3 权限校验流程
```
请求 → 中间件 (认证) → 控制器 → 中间件 (授权) → 业务逻辑
                ↓                    ↓
          Token 验证失败          权限校验失败
                ↓                    ↓
          返回 401 错误          返回 403 错误
```

---

## 5. 核心功能模块

### 5.1 用户管理
- 用户 CRUD 操作
- 用户状态管理（启用/禁用）
- 密码管理（重置、修改）
- 用户头像上传
- 用户角色分配
- 用户数据导出/导入
- 用户查询（多条件筛选）

### 5.2 角色管理
- 角色 CRUD 操作
- 角色菜单权限分配（树形选择）
- 角色按钮权限分配
- 角色接口权限分配
- 数据权限配置（全部/本部门/本部门及以下/仅本人/自定义）
- 角色状态管理

### 5.3 菜单管理
- 菜单 CRUD 操作（支持树形结构）
- 菜单类型：目录、菜单、按钮
- 菜单图标选择
- 菜单路由配置
- 菜单组件配置
- 菜单排序
- 菜单显示/隐藏
- 菜单缓存配置
- 菜单外链配置

### 5.4 部门管理
- 部门 CRUD 操作（支持树形结构）
- 部门负责人设置
- 部门联系方式
- 部门排序
- 部门状态管理

### 5.5 接口管理
- 接口 CRUD 操作
- 接口分组管理
- 接口路径配置（支持参数占位符）
- 接口方法配置（GET/POST/PUT/DELETE）
- 接口权限分配

### 5.6 日志管理
#### 登录日志
- 登录时间
- 登录 IP
- 登录地址（IP 定位）
- 操作系统
- 浏览器
- 登录状态

#### 操作日志
- 操作人
- 操作模块
- 操作功能
- 请求方法
- 请求地址
- 请求参数
- 返回结果
- 响应时间
- IP 地址

### 5.7 个人中心
- 个人信息查看
- 个人信息修改
- 头像上传
- 密码修改

### 5.8 系统配置
- 字典类型管理
- 字典数据管理
- 系统参数配置

---

## 6. 安全机制

### 6.1 认证安全
- JWT Token 认证
- Token 无感刷新
- 单点登录支持（踢出登录）
- 登录失败锁定（5 次/15 分钟）
- 密码强度校验
- 密码加密存储（bcrypt）

### 6.2 权限安全
- 三级权限控制（菜单/按钮/接口）
- 数据权限控制（部门级别）
- RBAC 权限模型
- 最小权限原则
- 权限缓存（Redis）

### 6.3 接口安全
- 接口限流
- SQL 注入防护
- XSS 攻击防护
- CSRF Token
- 请求签名验证（可选）

### 6.4 日志审计
- 登录日志记录
- 操作日志记录
- 日志脱敏处理
- 日志查询统计

---

## 7. 开发阶段与任务

### 7.1 阶段一：项目初始化
- 前端项目搭建
- 后端项目搭建
- 开发环境配置

### 7.2 阶段二：核心模块开发
- 认证模块（登录、Token）
- 用户管理
- 角色管理
- 菜单管理

### 7.3 阶段三：业务功能开发
- 部门管理
- 接口管理
- 日志管理
- 个人中心

### 7.4 阶段四：系统集成与测试
- 前后端联调
- 功能测试
- 性能测试
- 安全测试

### 7.5 阶段五：部署与文档
- 生产环境部署
- 文档编写
- 用户培训

**总工期预估：13-20 个工作日**

---

## 7.6 UI 视觉设计要求（像素级复刻）

### 7.6.1 复刻目标
**参考项目**: Speed CRM (https://github.com/a715cb/speed)  
**演示地址**: https://crm.atsep.top/web  
**演示账号**: demo / 123456  
**复刻标准**: 像素级精确复刻（Pixel-Perfect）

### 7.6.2 页面布局复刻
- ✅ **整体布局结构** - 侧边栏、顶部导航、标签页、主内容区的布局比例完全一致
- ✅ **侧边栏** - 宽度、折叠动画、菜单项高度、图标尺寸、间距完全一致
- ✅ **顶部导航** - 高度、面包屑位置、用户信息下拉菜单样式完全一致
- ✅ **标签页** - 高度、标签项宽度、关闭按钮位置、滚动效果完全一致
- ✅ **主内容区** - 内边距、卡片间距、表单布局完全一致

### 7.6.3 色彩方案复刻
- ✅ **主题色** - 完全使用参考项目的主色调（蓝色系）
- ✅ **辅助色** - 成功、警告、错误、信息等状态颜色完全一致
- ✅ **文字颜色** - 主标题、次标题、正文、辅助文字的色值完全一致
- ✅ **背景颜色** - 页面背景、卡片背景、表单背景的色值完全一致
- ✅ **边框颜色** - 所有边框、分割线的颜色完全一致

### 7.6.4 字体样式复刻
- ✅ **字体家族** - 使用相同的字体栈（优先系统字体）
- ✅ **字号规范** - 标题、正文、辅助文字的字号完全一致
- ✅ **字重** - 粗体、常规、细体的字重完全一致
- ✅ **行高** - 所有文本的行高完全一致
- ✅ **字间距** - 字符间距、单词间距完全一致

### 7.6.5 间距规范复刻
- ✅ **内边距** - 所有组件的内边距（padding）完全一致
- ✅ **外边距** - 所有组件的外边距（margin）完全一致
- ✅ **元素间距** - 表单字段、按钮、表格单元格的间距完全一致
- ✅ **卡片间距** - 卡片之间的间距完全一致

### 7.6.6 组件样式复刻
- ✅ **按钮** - 高度、圆角、内边距、hover 效果、点击效果完全一致
- ✅ **表单** - 输入框高度、边框、focus 效果、验证提示样式完全一致
- ✅ **表格** - 表头高度、行高、边框、hover 效果、分页样式完全一致
- ✅ **弹窗** - 宽度、圆角、阴影、遮罩透明度、动画效果完全一致
- ✅ **菜单** - 菜单项高度、图标位置、箭头样式、选中效果完全一致
- ✅ **卡片** - 圆角、阴影、内边距、标题样式完全一致

### 7.6.7 交互效果复刻
- ✅ **hover 效果** - 所有可交互元素的 hover 效果完全一致
- ✅ **点击效果** - 按钮、菜单项的点击反馈效果完全一致
- ✅ **过渡动画** - 所有动画的缓动函数、时长完全一致
- ✅ **加载效果** - Loading 动画样式、骨架屏效果完全一致
- ✅ **滚动效果** - 滚动条样式、滚动动画完全一致

### 7.6.8 响应式适配
- ✅ **桌面端** - 1920px、1440px、1366px 分辨率下完全一致
- ✅ **平板端** - 768px-1024px 分辨率下的适配效果完全一致
- ✅ **移动端** - 375px-768px 分辨率下的适配效果完全一致

### 7.6.9 验收方法
- **视觉对比工具** - 使用 PixelSnap、PerfectPixel 等工具进行像素对比
- **截图对比** - 在相同分辨率下截图，使用 PS 进行叠加对比
- **人工验收** - 逐项检查所有页面、组件、交互效果
- **多浏览器测试** - Chrome、Firefox、Safari、Edge 下效果一致

---

## 8. 验收标准

### 8.1 功能验收
- ✅ 所有 CRUD 功能正常运行
- ✅ 权限控制精确到按钮级别
- ✅ 菜单动态生成
- ✅ 数据权限正确过滤
- ✅ 日志完整记录
- ✅ Token 无感刷新
- ✅ UI 1:1 复刻参考项目

### 8.2 性能验收
- ✅ 首屏加载 < 3 秒
- ✅ 接口响应 < 500ms
- ✅ 支持 100+ 并发用户

### 8.3 安全验收
- ✅ 密码加密存储
- ✅ Token 安全验证
- ✅ SQL 注入防护
- ✅ XSS 攻击防护
- ✅ CSRF Token 验证

### 8.4 代码质量
- ✅ ESLint/Prettier 检查通过
- ✅ TypeScript 类型完整
- ✅ 单元测试覆盖率 > 80%
- ✅ 代码注释完整
- ✅ 文档齐全

---

## 9. 技术规范

### 9.1 代码规范
- 前端：ESLint + Prettier
- 后端：PSR-12 编码规范
- Git 提交：Conventional Commits

### 9.2 命名规范

#### 前端
- 组件名：PascalCase (如：UserManagement.vue)
- 方法名：camelCase (如：getUserList)
- 常量名：UPPER_SNAKE_CASE
- 样式类：kebab-case
- 文件名：kebab-case (如：user-list.vue)

#### 后端
- 类名：PascalCase (如：UserController)
- 方法名：camelCase (如：getUserList)
- 常量名：UPPER_SNAKE_CASE
- 数据库表名：snake_case (如：sys_user)
- 字段名：snake_case (如：user_name)

### 9.3 数据库规范
- 表名前缀：`sys_`
- 主键命名：`id`
- 外键命名：`{table}_id` (如：`user_id`)
- 索引命名：`idx_{field}`、`uk_{field}`
- 时间戳字段：`created_at`、`updated_at`、`deleted_at`
- 状态字段：`status` (1 正常/0 禁用)
- 软删除：`deleted_at`

---

## 10. 扩展预留

### 10.1 多项目接入
- 提供统一认证 SDK
- 支持多项目单点登录
- 项目隔离机制

### 10.2 微信生态
- 微信公众号 OAuth2.0 接入
- 微信小程序登录授权
- 企业微信集成

### 10.3 第三方系统
- OAuth2.0 标准对接
- CAS 单点登录
- LDAP/AD 集成

### 10.4 多租户（预留）
- 租户数据隔离
- 租户配置独立
- 租户资源配额

---

## 11. 性能优化

### 11.1 前端优化
- 路由懒加载
- 组件按需加载
- 图片懒加载
- CSS Tree Shaking
- Gzip 压缩
- 浏览器缓存策略
- CDN 加速

### 11.2 后端优化
- Redis 缓存
- 数据库索引优化
- 查询缓存
- 异步队列
- 接口限流
- 连接池优化

---

*文档版本：v2.0（合并版）*  
*创建日期：2026-04-25*  
*最后更新：2026-04-25*  
*说明：本文档已合并原有 docs/specs/ 的完整技术细节*
