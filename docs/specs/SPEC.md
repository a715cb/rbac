# 企业级RBAC权限系统规范文档

## 一、系统概述

### 1.1 项目定位
通用可复用企业级RBAC权限底座系统，统一支撑自动化办公、企业网站、微信公众号、微信小程序等多项目权限鉴权。

### 1.2 核心特征
- **高通用性**：抽象通用权限模型，适配多种业务场景
- **强可移植性**：模块化设计，支持快速集成到新项目
- **标准化**：遵循行业最佳实践，统一接口规范
- **低耦合**：前后端分离，分层架构，模块间解耦

### 1.3 技术栈规范

#### 前端技术栈（严格遵循）
- **Vue 3.4+**：前端JavaScript框架（Composition API）
- **TypeScript 5.0+**：静态类型检查
- **Vite 5.0+**：前端构建工具
- **Vue Router 4.0+**：前端路由管理
- **Pinia 2.0+**：状态管理
- **Ant Design Vue 4.0+**：UI组件库
- **Tailwind CSS 3.0+**：实用优先CSS框架
- **Less**：CSS预处理器
- **Axios**：HTTP客户端
- **Iconify**：图标库
- **VueUse**：Vue Composition API工具库
- **ECharts**：图表库（可选）
- **wangEditor**：富文本编辑器（可选）

#### 后端技术栈（严格遵循）
- **ThinkPHP 8.0+**：PHP框架
- **MySQL 8.0+**：关系型数据库
- **Redis**：缓存、Session存储、队列
- **JWT**：Token认证

---

## 二、系统架构设计

### 2.1 整体架构图

```
┌─────────────────────────────────────────────────────────────────┐
│                        前端应用层                                 │
│  ┌─────────────┬─────────────┬─────────────┬─────────────────┐   │
│  │  自动化办公  │   企业网站   │  微信公众号  │   微信小程序     │   │
│  └─────────────┴─────────────┴─────────────┴─────────────────┘   │
│                          │                                       │
│              ┌───────────┴───────────┐                           │
│              │    权限SDK/中间件      │                           │
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
src/
├── api/              # API接口层（统一封装Axios）
├── assets/           # 静态资源
├── components/       # 公共组件
├── composables/      # 组合式函数（VueUse二次封装）
├── config/           # 配置文件
├── directives/       # 自定义指令（权限按钮）
├── layouts/          # 布局组件
├── router/           # 路由配置（静态+动态路由）
├── stores/           # Pinia状态管理
├── styles/          # 全局样式（Tailwind+Less）
├── types/           # TypeScript类型定义
├── utils/           # 工具函数
└── views/           # 页面组件
```

#### 后端分层（ThinkPHP）
```
app/
├── common/          # 公共模块
│   ├── controller/  # 基础控制器
│   ├── model/       # 基础模型
│   └── validate/    # 验证器
├── admin/           # 后台管理模块
│   ├── controller/  # 控制器（认证、用户、角色等）
│   ├── model/       # 数据模型
│   ├── service/     # 业务逻辑层
│   └── validate/    # 验证器
├── api/             # API接口模块（对外服务）
│   ├── controller/  # 控制器
│   ├── service/     # 业务逻辑层
│   └── validate/    # 验证器
├── middleware/      # 中间件（认证、权限、日志）
└── common.php       # 公共函数库
```

---

## 三、数据库设计

### 3.1 ER图概述
```
用户表(user) ←→ 用户角色关联表(user_role) ←→ 角色表(role)
  ↓                                                    ↓
  └→ 部门表(department) ←─────────────────────────── 角色菜单关联表(role_menu)
                                                    ↓
                                                    菜单表(menu)
                                                    ↓
                                                    菜单按钮表(menu_button)
                                                    ↓
                                                    接口表(api)
```

### 3.2 核心数据表设计（14张表）

#### 3.2.1 系统管理基础表

**sys_user - 用户表**
```sql
CREATE TABLE `sys_user` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  `password` VARCHAR(255) NOT NULL COMMENT '密码（加密）',
  `nickname` VARCHAR(50) DEFAULT NULL COMMENT '昵称',
  `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
  `mobile` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
  `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像',
  `gender` TINYINT DEFAULT 0 COMMENT '性别：0未知 1男 2女',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `dept_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '部门ID',
  `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '最后登录IP',
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
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '部门ID',
  `parent_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父部门ID',
  `name` VARCHAR(50) NOT NULL COMMENT '部门名称',
  `code` VARCHAR(50) NOT NULL COMMENT '部门编码',
  `leader` VARCHAR(50) DEFAULT NULL COMMENT '负责人',
  `phone` VARCHAR(20) DEFAULT NULL COMMENT '联系电话',
  `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
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
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
  `code` VARCHAR(50) NOT NULL COMMENT '角色编码',
  `data_scope` TINYINT NOT NULL DEFAULT 1 COMMENT '数据权限：1全部 2本部门 3本部门及以下 4仅本人 5自定义',
  `data_scope_dept_ids` VARCHAR(500) DEFAULT NULL COMMENT '自定义数据权限部门ID列表',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
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
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
  `role_id` BIGINT UNSIGNED NOT NULL COMMENT '角色ID',
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
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '菜单ID',
  `parent_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父菜单ID',
  `name` VARCHAR(50) NOT NULL COMMENT '菜单名称',
  `code` VARCHAR(100) NOT NULL COMMENT '菜单标识',
  `path` VARCHAR(200) DEFAULT NULL COMMENT '路由路径',
  `icon` VARCHAR(100) DEFAULT NULL COMMENT '图标',
  `component` VARCHAR(255) DEFAULT NULL COMMENT '组件路径',
  `menu_type` TINYINT NOT NULL COMMENT '菜单类型：1目录 2菜单 3按钮',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `visible` TINYINT DEFAULT 1 COMMENT '显示状态：0隐藏 1显示',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `keep_alive` TINYINT DEFAULT 1 COMMENT '是否缓存：0否 1是',
  `always_show` TINYINT DEFAULT 1 COMMENT '是否总是显示：0否 1是',
  `breadcrumb` TINYINT DEFAULT 1 COMMENT '是否显示面包屑：0否 1是',
  `active_menu` VARCHAR(255) DEFAULT NULL COMMENT '高亮菜单',
  `is_external` TINYINT DEFAULT 0 COMMENT '是否外链：0否 1是',
  `is_frame` TINYINT DEFAULT 1 COMMENT '是否iframe：0否 1是',
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
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `role_id` BIGINT UNSIGNED NOT NULL COMMENT '角色ID',
  `menu_id` BIGINT UNSIGNED NOT NULL COMMENT '菜单ID',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_menu` (`role_id`, `menu_id`),
  KEY `idx_menu_id` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色菜单关联表';
```

**sys_menu_button - 菜单按钮表**
```sql
CREATE TABLE `sys_menu_button` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '按钮ID',
  `menu_id` BIGINT UNSIGNED NOT NULL COMMENT '菜单ID',
  `name` VARCHAR(50) NOT NULL COMMENT '按钮名称',
  `code` VARCHAR(100) NOT NULL COMMENT '按钮编码',
  `icon` VARCHAR(100) DEFAULT NULL COMMENT '按钮图标',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
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
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `role_id` BIGINT UNSIGNED NOT NULL COMMENT '角色ID',
  `menu_button_id` BIGINT UNSIGNED NOT NULL COMMENT '菜单按钮ID',
  `created_at` DATETIME DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_button` (`role_id`, `menu_button_id`),
  KEY `idx_button_id` (`menu_button_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色菜单按钮关联表';
```

**sys_api - 接口表**
```sql
CREATE TABLE `sys_api` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '接口ID',
  `menu_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '所属菜单',
  `name` VARCHAR(100) NOT NULL COMMENT '接口名称',
  `code` VARCHAR(100) NOT NULL COMMENT '接口标识',
  `method` VARCHAR(10) NOT NULL COMMENT '请求方法：GET/POST/PUT/DELETE',
  `path` VARCHAR(200) NOT NULL COMMENT '接口路径',
  `group` VARCHAR(50) DEFAULT NULL COMMENT '接口分组',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
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
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `role_id` BIGINT UNSIGNED NOT NULL COMMENT '角色ID',
  `api_id` BIGINT UNSIGNED NOT NULL COMMENT '接口ID',
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
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  `ip` VARCHAR(50) DEFAULT NULL COMMENT '登录IP',
  `address` VARCHAR(255) DEFAULT NULL COMMENT '登录地址',
  `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'User-Agent',
  `os` VARCHAR(100) DEFAULT NULL COMMENT '操作系统',
  `browser` VARCHAR(100) DEFAULT NULL COMMENT '浏览器',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '登录状态：0失败 1成功',
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
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `user_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '用户ID',
  `username` VARCHAR(50) DEFAULT NULL COMMENT '用户名',
  `module` VARCHAR(100) DEFAULT NULL COMMENT '操作模块',
  `action` VARCHAR(100) DEFAULT NULL COMMENT '操作功能',
  `method` VARCHAR(10) DEFAULT NULL COMMENT '请求方法',
  `url` VARCHAR(500) DEFAULT NULL COMMENT '请求地址',
  `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP地址',
  `address` VARCHAR(255) DEFAULT NULL COMMENT '操作地址',
  `param` TEXT DEFAULT NULL COMMENT '请求参数',
  `result` TEXT DEFAULT NULL COMMENT '返回结果',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '操作状态：0异常 1正常',
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
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '字典ID',
  `name` VARCHAR(100) NOT NULL COMMENT '字典名称',
  `code` VARCHAR(100) NOT NULL COMMENT '字典编码',
  `type` VARCHAR(50) NOT NULL DEFAULT 'string' COMMENT '类型：string/number/date/time',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
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
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '字典数据ID',
  `dict_type_id` BIGINT UNSIGNED NOT NULL COMMENT '字典类型ID',
  `label` VARCHAR(100) NOT NULL COMMENT '字典标签',
  `value` VARCHAR(100) NOT NULL COMMENT '字典键值',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
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
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `name` VARCHAR(100) NOT NULL COMMENT '配置名称',
  `code` VARCHAR(100) NOT NULL COMMENT '配置编码',
  `value` TEXT DEFAULT NULL COMMENT '配置值',
  `type` VARCHAR(50) NOT NULL DEFAULT 'string' COMMENT '类型：string/number/json/xml',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
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

## 四、前端目录结构

### 4.1 完整目录树
```
frontend/
├── public/                      # 静态公共资源
│   ├── favicon.ico             # 网站图标
│   └── index.html              # HTML模板
├── src/
│   ├── api/                    # API接口层
│   │   ├── index.ts           # API统一导出
│   │   ├── request.ts         # Axios封装
│   │   ├── sys/               # 系统管理模块
│   │   │   ├── user.ts       # 用户管理
│   │   │   ├── role.ts       # 角色管理
│   │   │   ├── menu.ts       # 菜单管理
│   │   │   ├── dept.ts       # 部门管理
│   │   │   ├── dict.ts       # 字典管理
│   │   │   ├── config.ts     # 配置管理
│   │   │   ├── loginLog.ts   # 登录日志
│   │   │   └── operationLog.ts# 操作日志
│   │   └── api/              # 接口管理模块
│   │       └── index.ts
│   ├── assets/                # 静态资源
│   │   ├── images/           # 图片资源
│   │   ├── icons/            # 自定义图标
│   │   └── styles/           # 全局样式
│   │       ├── variables.less# Less变量
│   │       └── global.less   # 全局样式
│   ├── components/            # 公共组件
│   │   ├── common/           # 通用组件
│   │   │   ├── Pagination.vue# 分页组件
│   │   │   ├── Table.vue     # 表格封装
│   │   │   ├── Form.vue      # 表单封装
│   │   │   └── Dialog.vue    # 弹窗封装
│   │   ├── layout/           # 布局组件
│   │   │   ├── Sidebar.vue   # 侧边栏
│   │   │   ├── Header.vue    # 顶部导航
│   │   │   ├── TagsView.vue  # 标签页
│   │   │   └── AppMain.vue   # 主内容区
│   │   └── icons/            # 图标组件
│   │       └── index.vue
│   ├── composables/          # 组合式函数
│   │   ├── useAuth.ts        # 权限钩子
│   │   ├── useTable.ts       # 表格钩子
│   │   ├── useForm.ts        # 表单钩子
│   │   ├── useDialog.ts      # 弹窗钩子
│   │   ├── useMessage.ts     # 消息提示
│   │   └── useLoading.ts     # 加载状态
│   ├── config/                # 配置文件
│   │   ├── index.ts         # 配置导出
│   │   ├── settings.ts      # 系统配置
│   │   ├── routes.ts        # 路由配置
│   │   └── theme.ts         # 主题配置
│   ├── directives/          # 自定义指令
│   │   ├── auth.ts          # 权限指令 v-auth
│   │   ├── loading.ts       # 加载指令 v-loading
│   │   └── copy.ts          # 复制指令 v-copy
│   ├── hooks/                # 生命周期钩子
│   │   └── index.ts
│   ├── layouts/              # 布局页面
│   │   ├── DefaultLayout.vue# 默认布局
│   │   ├── BlankLayout.vue  # 空布局
│   │   └── Components/      # 布局子组件
│   ├── router/               # 路由配置
│   │   ├── index.ts         # 路由导出
│   │   ├── guard.ts         # 路由守卫
│   │   ├── staticRoutes.ts  # 静态路由
│   │   └── dynamicRoutes.ts # 动态路由
│   ├── stores/               # Pinia状态管理
│   │   ├── index.ts         # Store导出
│   │   ├── user.ts          # 用户状态
│   │   ├── menu.ts          # 菜单状态
│   │   ├── permission.ts    # 权限状态
│   │   ├── settings.ts      # 设置状态
│   │   └── tabs.ts          # 标签页状态
│   ├── types/               # TypeScript类型
│   │   ├── index.ts         # 类型导出
│   │   ├── api.d.ts         # API类型
│   │   ├── store.d.ts       # Store类型
│   │   ├── router.d.ts      # 路由类型
│   │   └── global.d.ts      # 全局类型
│   ├── utils/                # 工具函数
│   │   ├── index.ts         # 工具导出
│   │   ├── storage.ts       # 存储封装
│   │   ├── validate.ts      # 表单验证
│   │   ├── format.ts        # 格式化工具
│   │   ├── encrypt.ts       # 加密工具
│   │   └── common.ts        # 通用工具
│   ├── views/                # 页面视图
│   │   ├── login/           # 登录页面
│   │   │   └── index.vue
│   │   ├── home/            # 首页
│   │   │   └── index.vue
│   │   ├──个人中心/         # 个人中心
│   │   │   ├── profile.vue  # 个人资料
│   │   │   └── password.vue# 修改密码
│   │   ├── system/          # 系统管理
│   │   │   ├── user/       # 用户管理
│   │   │   │   ├── index.vue
│   │   │   │   ├── detail.vue
│   │   │   │   └── import.vue
│   │   │   ├── role/       # 角色管理
│   │   │   │   ├── index.vue
│   │   │   │   ├── detail.vue
│   │   │   │   └── permission.vue
│   │   │   ├── menu/       # 菜单管理
│   │   │   │   ├── index.vue
│   │   │   │   └── detail.vue
│   │   │   ├── dept/       # 部门管理
│   │   │   │   ├── index.vue
│   │   │   │   └── detail.vue
│   │   │   ├── api/        # 接口管理
│   │   │   │   ├── index.vue
│   │   │   │   └── detail.vue
│   │   │   ├── dict/       # 字典管理
│   │   │   │   ├── type.vue
│   │   │   │   ├── data.vue
│   │   │   │   └── detail.vue
│   │   │   └── config/     # 配置管理
│   │   │       ├── index.vue
│   │   │       └── detail.vue
│   │   ├── logs/            # 日志管理
│   │   │   ├── loginLog/   # 登录日志
│   │   │   │   └── index.vue
│   │   │   └── operationLog/# 操作日志
│   │   │       └── index.vue
│   │   └── error/          # 错误页面
│   │       ├── 403.vue
│   │       ├── 404.vue
│   │       └── 500.vue
│   ├── App.vue              # 根组件
│   ├── main.ts              # 应用入口
│   └── env.d.ts             # 环境变量
├── .env                     # 环境变量
├── .env.development         # 开发环境
├── .env.production          # 生产环境
├── .eslintrc.js            # ESLint配置
├── .prettierrc             # Prettier配置
├── index.html              # 入口HTML
├── package.json            # 依赖配置
├── tsconfig.json           # TypeScript配置
├── vite.config.ts          # Vite配置
└── README.md               # 项目说明
```

---

## 五、后端目录结构

### 5.1 完整目录树
```
backend/
├── app/
│   ├── common.php          # 公共函数库
│   ├── Event.php           # 事件类
│   ├── common/            # 公共模块
│   │   ├── controller/
│   │   │   ├── Api.php    # API基础控制器
│   │   │   └── Admin.php  # 后台基础控制器
│   │   ├── model/
│   │   │   ├── Model.php  # 基础模型
│   │   │   └── TreeModel.php# 树形模型
│   │   ├── validate/
│   │   │   └── Validate.php# 基础验证器
│   │   └── library/
│   │       ├── Auth.php   # 权限认证类
│   │       ├── Tree.php   # 树形结构类
│   │       ├── Helper.php # 助手函数类
│   │       └── Jwt.php    # JWT类
│   ├── admin/             # 后台管理模块
│   │   ├── controller/
│   │   │   ├── Index.php  # 首页控制器
│   │   │   ├── Auth.php   # 认证控制器（登录、登出）
│   │   │   ├── User.php   # 用户管理控制器
│   │   │   ├── Role.php   # 角色管理控制器
│   │   │   ├── Menu.php   # 菜单管理控制器
│   │   │   ├── Dept.php   # 部门管理控制器
│   │   │   ├── Api.php    # 接口管理控制器
│   │   │   ├── Dict.php   # 字典管理控制器
│   │   │   ├── Config.php # 配置管理控制器
│   │   │   ├── LoginLog.php# 登录日志控制器
│   │   │   └── OperationLog.php# 操作日志控制器
│   │   ├── model/
│   │   │   ├── User.php   # 用户模型
│   │   │   ├── Role.php   # 角色模型
│   │   │   ├── Menu.php   # 菜单模型
│   │   │   ├── Dept.php   # 部门模型
│   │   │   ├── Api.php    # 接口模型
│   │   │   ├── LoginLog.php# 登录日志模型
│   │   │   └── OperationLog.php# 操作日志模型
│   │   ├── service/
│   │   │   ├── UserService.php    # 用户服务层
│   │   │   ├── RoleService.php    # 角色服务层
│   │   │   ├── MenuService.php    # 菜单服务层
│   │   │   ├── DeptService.php    # 部门服务层
│   │   │   ├── AuthService.php    # 认证服务层
│   │   │   └── LogService.php     # 日志服务层
│   │   ├── validate/
│   │   │   ├── UserValidate.php   # 用户验证器
│   │   │   ├── RoleValidate.php   # 角色验证器
│   │   │   ├── MenuValidate.php   # 菜单验证器
│   │   │   └── DeptValidate.php   # 部门验证器
│   │   ├── middleware/
│   │   │   └── OperationLog.php   # 操作日志中间件
│   │   └── route/
│   │       └── admin.php  # 后台路由
│   ├── api/               # API接口模块
│   │   ├── controller/
│   │   │   ├── Index.php  # 首页控制器
│   │   │   ├── Auth.php   # 认证控制器
│   │   │   └── User.php   # 用户信息控制器
│   │   ├── service/
│   │   │   └── AuthService.php    # API认证服务
│   │   ├── middleware/
│   │   │   └── ApiAuth.php# API认证中间件
│   │   └── route/
│   │       └── api.php   # API路由
│   ├── wechat/            # 微信模块（预留）
│   │   ├── controller/
│   │   ├── service/
│   │   └── model/
│   └── job/               # 队列任务（预留）
│       └── TestJob.php
├── config/
│   ├── app.php           # 应用配置
│   ├── cache.php         # 缓存配置
│   ├── console.php       # 控制台配置
│   ├── cookie.php        # Cookie配置
│   ├── database.php       # 数据库配置
│   ├── lang.php          # 语言配置
│   ├── log.php           # 日志配置
│   ├── middleware.php    # 中间件配置
│   ├── route.php         # 路由配置
│   ├── session.php       # Session配置
│   ├── swagger.php       # Swagger配置
│   ├── tenant.php        # 多租户配置（预留）
│   ├── trace.php         # 链路追踪配置
│   └── view.php          # 视图配置
├── database/
│   ├── migrations/       # 数据迁移
│   └── seeders/         # 数据填充
├── public/
│   ├── index.php         # 入口文件
│   └── .htaccess         # Apache重写
├── route/
│   └── app.php           # 应用路由
├── runtime/              # 运行时目录
├── storage/              # 存储目录
│   ├── app/             # 应用存储
│   ├── extend/          # 扩展目录
│   └── logs/            # 日志目录
├── tests/
│   ├── feature/         # 功能测试
│   └── unit/           # 单元测试
├── think                # 命令行入口
├── .env                 # 环境变量
├── .example.env        # 环境变量示例
├── composer.json        # 依赖配置
└── README.md           # 项目说明
```

---

## 六、API接口规范

### 6.1 RESTful规范

#### 6.1.1 接口路径规范
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

#### 6.1.2 统一响应格式
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

#### 6.1.3 HTTP状态码
- `200` - 请求成功
- `201` - 创建成功
- `204` - 删除成功
- `400` - 请求参数错误
- `401` - 未授权（Token无效或过期）
- `403` - 禁止访问（无权限）
- `404` - 资源不存在
- `422` - 数据验证失败
- `429` - 请求过于频繁
- `500` - 服务器内部错误

### 6.2 认证授权

#### 6.2.1 JWT令牌
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

#### 6.2.2 令牌刷新策略
- Access Token：有效期2小时
- Refresh Token：有效期7天
- 令牌无感刷新：Token剩余有效期 < 30分钟时自动刷新

### 6.3 权限校验流程
```
请求 → 中间件(认证) → 控制器 → 中间件(授权) → 业务逻辑
                ↓                    ↓
          Token验证失败          权限校验失败
                ↓                    ↓
          返回401错误          返回403错误
```

---

## 七、核心功能模块

### 7.1 用户管理
- 用户CRUD操作
- 用户状态管理（启用/禁用）
- 密码管理（重置、修改）
- 用户头像上传
- 用户角色分配
- 用户数据导出/导入
- 用户查询（多条件筛选）

### 7.2 角色管理
- 角色CRUD操作
- 角色菜单权限分配（树形选择）
- 角色按钮权限分配
- 角色接口权限分配
- 数据权限配置（全部/本部门/本部门及以下/仅本人/自定义）
- 角色状态管理

### 7.3 菜单管理
- 菜单CRUD操作（支持树形结构）
- 菜单类型：目录、菜单、按钮
- 菜单图标选择
- 菜单路由配置
- 菜单组件配置
- 菜单排序
- 菜单显示/隐藏
- 菜单缓存配置
- 菜单外链配置

### 7.4 部门管理
- 部门CRUD操作（支持树形结构）
- 部门负责人设置
- 部门联系方式
- 部门排序
- 部门状态管理

### 7.5 接口管理
- 接口CRUD操作
- 接口分组管理
- 接口路径配置（支持参数占位符）
- 接口方法配置（GET/POST/PUT/DELETE）
- 接口权限分配

### 7.6 日志管理
#### 登录日志
- 登录时间
- 登录IP
- 登录地址（IP定位）
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
- IP地址

### 7.7 个人中心
- 个人信息查看
- 个人信息修改
- 头像上传
- 密码修改

### 7.8 系统配置
- 字典类型管理
- 字典数据管理
- 系统参数配置

---

## 八、安全机制

### 8.1 认证安全
- JWT Token认证
- Token无感刷新
- 单点登录支持（踢出登录）
- 登录失败锁定（5次/15分钟）
- 密码强度校验
- 密码加密存储（bcrypt）

### 8.2 权限安全
- 三级权限控制（菜单/按钮/接口）
- 数据权限控制（部门级别）
- RBAC权限模型
- 最小权限原则
- 权限缓存（Redis）

### 8.3 接口安全
- 接口限流
- SQL注入防护
- XSS攻击防护
- CSRF Token
- 请求签名验证（可选）

### 8.4 日志审计
- 登录日志记录
- 操作日志记录
- 日志脱敏处理
- 日志查询统计

---

## 九、部署架构

### 9.1 开发环境
- 前端：Vite Dev Server (localhost:5173)
- 后端：PHP Built-in Server (localhost:8000)
- 数据库：MySQL 8.0 (localhost:3306)
- 缓存：Redis (localhost:6379)

### 9.2 生产环境（推荐）
```
┌─────────────┐
│   Nginx     │ :80/443
│  反向代理    │
└──────┬──────┘
       │
┌──────┴──────┐
│   Nginx     │ 负载均衡
│  静态资源    │
└──────┬──────┘
       │
┌──────┴──────┐
│  Node.js    │ SSR/API服务
│  集群       │
└──────┬──────┘
       │
┌──────┴──────┐
│  Redis     │ 缓存集群
│  Cluster   │
└──────┬──────┘
       │
┌──────┴──────┐
│  MySQL     │ 主从复制
│  Cluster   │
└─────────────┘
```

---

## 十、扩展预留

### 10.1 多项目接入
- 提供统一认证SDK
- 支持多项目单点登录
- 项目隔离机制

### 10.2 微信生态
- 微信公众号OAuth2.0接入
- 微信小程序登录授权
- 企业微信集成

### 10.3 第三方系统
- OAuth2.0标准对接
- CAS单点登录
- LDAP/AD集成

### 10.4 多租户（预留）
- 租户数据隔离
- 租户配置独立
- 租户资源配额

---

## 十一、技术规范

### 11.1 代码规范
- 前端：ESLint + Prettier
- 后端：PSR-12编码规范
- Git提交：Conventional Commits

### 11.2 命名规范
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

### 11.3 数据库规范
- 表名前缀：`sys_`
- 主键命名：`id`
- 外键命名：`{table}_id` (如：`user_id`)
- 索引命名：`idx_{field}`、`uk_{field}`
- 时间戳字段：`created_at`、`updated_at`、`deleted_at`
- 状态字段：`status` (1正常/0禁用)
- 软删除：`deleted_at`

---

## 十二、性能优化

### 12.1 前端优化
- 路由懒加载
- 组件按需加载
- 图片懒加载
- CSS Tree Shaking
- Gzip压缩
- 浏览器缓存策略
- CDN加速

### 12.2 后端优化
- Redis缓存
- 数据库索引优化
- 查询缓存
- 异步队列
- 接口限流
- 连接池优化

---

## 十三、文档交付

### 13.1 开发文档
- API接口文档（Swagger/OpenAPI）
- 数据库设计文档
- 架构设计文档
- 部署文档

### 13.2 用户文档
- 使用手册
- 操作指南
- 常见问题（FAQ）

---

## 十四、验收标准

### 14.1 功能验收
- ✅ 所有CRUD功能正常运行
- ✅ 权限控制精确到按钮级别
- ✅ 菜单动态生成
- ✅ 数据权限正确过滤
- ✅ 日志完整记录
- ✅ Token无感刷新
- ✅ UI 1:1复刻参考项目

### 14.2 性能验收
- ✅ 首屏加载 < 3秒
- ✅ 接口响应 < 500ms
- ✅ 支持100+并发用户

### 14.3 安全验收
- ✅ 密码加密存储
- ✅ Token安全验证
- ✅ SQL注入防护
- ✅ XSS攻击防护
- ✅ CSRF Token验证

### 14.4 代码质量
- ✅ ESLint/Prettier检查通过
- ✅ TypeScript类型完整
- ✅ 单元测试覆盖率 > 80%
- ✅ 代码注释完整
- ✅ 文档齐全

---

## 十五、开发周期预估

### 第一阶段：基础框架搭建（1-2天）
- 项目初始化
- 目录结构搭建
- 技术栈集成
- 公共组件封装

### 第二阶段：数据库设计与实现（1-2天）
- 数据库表设计
- 数据初始化SQL
- 模型层开发
- 基础服务层

### 第三阶段：核心权限模块（3-4天）
- 认证模块（登录、Token）
- 用户管理
- 角色管理
- 菜单管理
- 部门管理

### 第四阶段：业务功能开发（2-3天）
- 接口管理
- 日志管理
- 字典管理
- 配置管理
- 个人中心

### 第五阶段：前端UI开发（3-4天）
- 布局组件开发
- 页面组件开发
- 权限指令开发
- 动态路由开发
- UI 1:1复刻

### 第六阶段：系统集成与测试（2-3天）
- 前后端联调
- 功能测试
- 性能测试
- 安全测试
- Bug修复

### 第七阶段：文档与部署（1-2天）
- 开发文档编写
- 部署文档编写
- 正式环境部署
- 用户培训

**总工期预估：13-20个工作日**

---

*文档版本：v1.0*
*创建日期：2026-04-25*
*作者：AI Assistant*
