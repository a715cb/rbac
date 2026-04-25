# 企业级RBAC权限系统 - 项目概览

## 📋 项目概述

**项目名称**：企业级RBAC权限底座系统  
**项目定位**：通用可复用企业级RBAC权限底座，统一支撑多项目权限鉴权  
**核心特性**：高通用性、强可移植性、标准化、低耦合

---

## 🏗️ 系统架构

### 整体架构图
```
┌─────────────────────────────────────────────┐
│           多项目接入层                        │
│  ┌─────────┬──────────┬──────────┐          │
│  │ 自动化办公 │ 企业网站 │ 微信公众号 │ ...   │
│  └────┬────┴────┬─────┴────┬─────┘          │
│       └─────────┼──────────┘                │
│           统一权限SDK/中间件                   │
└──────────────────┼──────────────────────────┘
                   │ HTTP/API
┌──────────────────┼──────────────────────────┐
│              统一权限网关层                    │
│        API Gateway（认证、路由、限流）          │
└──────────────────┼──────────────────────────┘
                   │
┌──────────────────┼──────────────────────────┐
│               业务服务层                      │
│  ┌────────┬────────┬────────┬────────┐    │
│  │ 用户服务 │ 角色服务 │ 菜单服务 │ 权限服务 │    │
│  └────────┴────────┴────────┴────────┘    │
└──────────────────┼──────────────────────────┘
                   │
┌──────────────────┼──────────────────────────┐
│               数据访问层                     │
│  ┌────────┬────────┬────────┬────────┐    │
│  │  MySQL │  Redis │  文件  │  队列  │    │
│  └────────┴────────┴────────┴────────┘    │
└─────────────────────────────────────────────┘
```

---

## 💻 技术栈规范

### 前端技术栈（固定，不可修改）
| 技术 | 版本 | 用途 |
|------|------|------|
| Vue 3 | 3.4+ | 前端框架（Composition API） |
| TypeScript | 5.0+ | 静态类型检查 |
| Vite | 5.0+ | 构建工具 |
| Vue Router | 4.0+ | 路由管理 |
| Pinia | 2.0+ | 状态管理 |
| Ant Design Vue | 4.0+ | UI组件库 |
| Tailwind CSS | 3.0+ | CSS框架 |
| Less | - | CSS预处理器 |
| Axios | - | HTTP客户端 |
| Iconify | - | 图标库 |
| VueUse | - | 工具库 |
| ECharts | 5.0+ | 图表库（可选） |
| wangEditor | 5.0+ | 富文本编辑器（可选） |

### 后端技术栈（固定，不可修改）
| 技术 | 版本 | 用途 |
|------|------|------|
| ThinkPHP | 8.0+ | PHP框架 |
| MySQL | 8.0+ | 关系型数据库 |
| Redis | - | 缓存、Session |
| JWT | - | Token认证 |

---

## 📊 数据库设计

### 核心数据表（14张）
#### 系统管理基础表（5张）
1. `sys_user` - 用户表
2. `sys_department` - 部门表（树形）
3. `sys_role` - 角色表
4. `sys_user_role` - 用户角色关联表
5. `sys_menu` - 菜单表（树形）

#### 权限控制表（5张）
6. `sys_role_menu` - 角色菜单关联表
7. `sys_menu_button` - 菜单按钮表
8. `sys_role_menu_button` - 角色菜单按钮关联表
9. `sys_api` - 接口表
10. `sys_role_api` - 角色接口关联表

#### 日志表（2张）
11. `sys_login_log` - 登录日志表
12. `sys_operation_log` - 操作日志表

#### 扩展表（2张）
13. `sys_dict_type` - 字典类型表
14. `sys_dict_data` - 字典数据表

### ER关系图
```
用户(user) ←→ 用户角色(user_role) ←→ 角色(role)
  ↓                                      ↓
  └→ 部门(department) ←─────────────── 角色菜单(role_menu)
                                     ↓
                                     菜单(menu)
                                     ↓
                                     菜单按钮(menu_button)
                                     ↓
                                     接口(api)
```

---

## 📁 目录结构

### 前端目录结构（通用底座）
```
frontend/
├── public/                      # 公共资源目录
│   ├── favicon.ico             # 网站图标
│   └── index.html              # 入口 HTML
├── src/
│   ├── api/                    # API 接口层（统一封装）
│   │   ├── request.ts          # Axios 请求封装
│   │   ├── base.ts             # 基础接口定义
│   │   ├── system/             # 系统管理模块（RBAC 核心）
│   │   │   ├── auth.ts         # 认证接口（登录、登出、刷新 Token）
│   │   │   ├── user.ts         # 用户管理
│   │   │   ├── role.ts         # 角色管理
│   │   │   ├── menu.ts         # 菜单管理
│   │   │   ├── dept.ts         # 部门管理
│   │   │   ├── api.ts          # 接口管理
│   │   │   ├── dict.ts         # 字典管理
│   │   │   └── log.ts          # 日志管理
│   │   └── project/            # 项目管理模块（多项目支持）
│   │       ├── project.ts      # 项目管理接口
│   │       ├── oa.ts           # OA 项目专用接口
│   │       ├── wechat-mp.ts    # 微信公众号接口
│   │       ├── wechat-mini.ts  # 微信小程序接口
│   │       └── website.ts      # 企业网站接口
│   ├── assets/                 # 静态资源
│   │   ├── images/             # 图片资源
│   │   ├── icons/              # 图标资源（Iconify/SVG）
│   │   └── styles/             # 全局样式
│   │       ├── variables.less  # Less 变量（主题配置）
│   │       ├── global.less     # 全局样式
│   │       └── tailwind.css    # Tailwind CSS
│   ├── components/             # 公共组件库
│   │   ├── common/             # 通用组件
│   │   │   ├── Table/          # 表格封装（支持分页、搜索）
│   │   │   ├── Form/           # 表单封装（动态表单）
│   │   │   ├── Dialog/         # 弹窗封装
│   │   │   ├── Pagination/     # 分页组件
│   │   │   └── Upload/         # 上传组件
│   │   ├── business/           # 业务组件
│   │   │   ├── UserSelect/     # 用户选择器
│   │   │   ├── RoleSelect/     # 角色选择器
│   │   │   ├── DeptTree/       # 部门树形选择器
│   │   │   └── MenuTree/       # 菜单树形选择器
│   │   └── project/            # 项目专用组件（按需加载）
│   │       ├── oa/             # OA 项目组件
│   │       ├── wechat/         # 微信生态组件
│   │       └── website/        # 企业网站组件
│   ├── composables/            # 组合式函数（VueUse 风格）
│   │   ├── useAuth.ts          # 认证相关（登录、权限）
│   │   ├── useTable.ts         # 表格数据处理
│   │   ├── useForm.ts          # 表单数据处理
│   │   ├── useDialog.ts        # 弹窗控制
│   │   ├── useMessage.ts       # 消息提示
│   │   ├── useLoading.ts       # 加载状态
│   │   └── useProject.ts       # 项目管理相关
│   ├── config/                 # 配置文件
│   │   ├── settings.ts         # 系统配置（主题、布局）
│   │   ├── routes.ts           # 路由配置
│   │   ├── menu.ts             # 菜单配置
│   │   └── project.ts          # 多项目配置
│   ├── directives/             # 自定义指令
│   │   ├── auth.ts             # 权限指令（v-auth）
│   │   ├── loading.ts          # 加载指令（v-loading）
│   │   ├── copy.ts             # 复制指令（v-copy）
│   │   └── permission.ts       # 按钮权限指令
│   ├── layouts/                # 布局组件
│   │   ├── DefaultLayout.vue   # 默认布局（后台管理）
│   │   ├── BlankLayout.vue     # 空白布局（登录页）
│   │   ├── ProjectLayout.vue   # 项目专用布局（可切换）
│   │   └── components/         # 布局子组件
│   │       ├── Sidebar/        # 侧边栏
│   │       ├── Header/         # 顶部导航
│   │       ├── TagsView/       # 标签页
│   │       └── AppMain/        # 主内容区
│   ├── router/                 # 路由配置
│   │   ├── index.ts            # 路由导出
│   │   ├── guard.ts            # 路由守卫（权限验证）
│   │   ├── staticRoutes.ts     # 静态路由（登录、首页）
│   │   ├── dynamicRoutes.ts    # 动态路由（根据权限生成）
│   │   └── projectRoutes.ts    # 项目路由（按需加载）
│   ├── stores/                 # Pinia 状态管理
│   │   ├── index.ts            # Store 导出
│   │   ├── user.ts             # 用户状态（信息、权限）
│   │   ├── menu.ts             # 菜单状态（动态菜单）
│   │   ├── permission.ts       # 权限状态（按钮、接口）
│   │   ├── settings.ts         # 设置状态（主题、布局）
│   │   ├── tabs.ts             # 标签页状态
│   │   └── project.ts          # 项目状态（当前项目、配置）
│   ├── types/                  # TypeScript 类型定义
│   │   ├── api.d.ts            # API 响应类型
│   │   ├── user.d.ts           # 用户相关类型
│   │   ├── role.d.ts           # 角色相关类型
│   │   ├── menu.d.ts           # 菜单相关类型
│   │   ├── project.d.ts        # 项目相关类型
│   │   └── global.d.ts         # 全局类型
│   ├── utils/                  # 工具函数库
│   │   ├── storage.ts          # 本地存储封装
│   │   ├── validate.ts         # 表单验证工具
│   │   ├── format.ts           # 数据格式化工具
│   │   ├── encrypt.ts          # 加密工具（MD5、AES）
│   │   ├── common.ts           # 通用工具函数
│   │   └── project.ts          # 项目相关工具
│   ├── views/                  # 页面视图
│   │   ├── login/              # 登录认证模块
│   │   │   └── index.vue
│   │   ├── dashboard/          # 首页仪表盘
│   │   │   └── index.vue
│   │   ├── system/             # 系统管理模块（RBAC 核心）
│   │   │   ├── user/           # 用户管理
│   │   │   ├── role/           # 角色管理
│   │   │   ├── menu/           # 菜单管理
│   │   │   ├── dept/           # 部门管理
│   │   │   ├── api/            # 接口管理
│   │   │   ├── dict/           # 字典管理
│   │   │   └── log/            # 日志管理
│   │   ├── profile/            # 个人中心
│   │   │   ├── index.vue       # 个人信息
│   │   │   └── password.vue    # 修改密码
│   │   └── projects/           # 项目管理模块（多项目支持）
│   │       ├── list/           # 项目列表
│   │       ├── oa/             # OA 项目页面（按需）
│   │       ├── wechat-mp/      # 微信公众号项目（按需）
│   │       ├── wechat-mini/    # 微信小程序项目（按需）
│   │       └── website/        # 企业网站项目（按需）
│   ├── App.vue                 # 根组件
│   └── main.ts                 # 应用入口
├── .env                        # 环境变量
├── .env.development            # 开发环境配置
├── .env.production             # 生产环境配置
├── .eslintrc.js               # ESLint 配置
├── .prettierrc                # Prettier 配置
├── index.html                  # 入口 HTML
├── package.json                # 依赖配置
├── tsconfig.json               # TypeScript 配置
├── vite.config.ts              # Vite 配置
└── README.md                   # 项目说明
```

### 后端目录结构（通用底座）
```
backend/
├── app/
│   ├── common/                 # 公共模块
│   │   ├── controller/         # 基础控制器
│   │   │   ├── Api.php         # API 基础控制器
│   │   │   └── Admin.php       # 后台基础控制器
│   │   ├── model/              # 基础模型
│   │   │   ├── Model.php       # 基础模型类
│   │   │   └── TreeModel.php   # 树形模型类
│   │   ├── validate/           # 基础验证器
│   │   │   └── Validate.php    # 基础验证器类
│   │   └── library/            # 公共类库
│   │       ├── Auth.php        # 权限认证类
│   │       ├── Jwt.php         # JWT Token 类
│   │       ├── Tree.php        # 树形结构类
│   │       ├── Helper.php      # 助手函数类
│   │       └── Project.php     # 项目管理类
│   ├── admin/                  # 后台管理模块（RBAC 核心）
│   │   ├── controller/         # 控制器
│   │   │   ├── Index.php       # 首页控制器
│   │   │   ├── Auth.php        # 认证控制器（登录、登出）
│   │   │   ├── User.php        # 用户管理控制器
│   │   │   ├── Role.php        # 角色管理控制器
│   │   │   ├── Menu.php        # 菜单管理控制器
│   │   │   ├── Dept.php        # 部门管理控制器
│   │   │   ├── Api.php         # 接口管理控制器
│   │   │   ├── Dict.php        # 字典管理控制器
│   │   │   ├── Config.php      # 配置管理控制器
│   │   │   ├── LoginLog.php    # 登录日志控制器
│   │   │   └── OperationLog.php# 操作日志控制器
│   │   ├── model/              # 数据模型
│   │   │   ├── User.php        # 用户模型
│   │   │   ├── Role.php        # 角色模型
│   │   │   ├── Menu.php        # 菜单模型
│   │   │   ├── Dept.php        # 部门模型
│   │   │   ├── Api.php         # 接口模型
│   │   │   ├── LoginLog.php    # 登录日志模型
│   │   │   └── OperationLog.php# 操作日志模型
│   │   ├── service/            # 业务逻辑层
│   │   │   ├── UserService.php     # 用户服务
│   │   │   ├── RoleService.php     # 角色服务
│   │   │   ├── MenuService.php     # 菜单服务
│   │   │   ├── DeptService.php     # 部门服务
│   │   │   ├── AuthService.php     # 认证服务
│   │   │   ├── LogService.php      # 日志服务
│   │   │   └── PermissionService.php# 权限服务
│   │   ├── validate/           # 验证器
│   │   │   ├── UserValidate.php    # 用户验证器
│   │   │   ├── RoleValidate.php    # 角色验证器
│   │   │   ├── MenuValidate.php    # 菜单验证器
│   │   │   └── DeptValidate.php    # 部门验证器
│   │   ├── middleware/         # 中间件
│   │   │   └── OperationLog.php    # 操作日志中间件
│   │   └── route/              # 路由配置
│   │       └── admin.php       # 后台路由
│   ├── api/                    # API 接口模块（对外服务）
│   │   ├── controller/         # 控制器
│   │   │   ├── Index.php       # 首页控制器
│   │   │   ├── Auth.php        # 认证控制器
│   │   │   └── User.php        # 用户信息控制器
│   │   ├── service/            # 业务逻辑层
│   │   │   └── AuthService.php     # API 认证服务
│   │   ├── middleware/         # 中间件
│   │   │   └── ApiAuth.php     # API 认证中间件
│   │   └── route/              # 路由配置
│   │       └── api.php         # API 路由
│   ├── project/                # 项目管理模块（多项目支持）
│   │   ├── controller/         # 项目控制器
│   │   │   ├── Project.php     # 项目管理控制器
│   │   │   ├── Oa.php          # OA 项目控制器（按需）
│   │   │   ├── WechatMp.php    # 微信公众号控制器（按需）
│   │   │   ├── WechatMini.php  # 微信小程序控制器（按需）
│   │   │   └── Website.php     # 企业网站控制器（按需）
│   │   ├── model/              # 项目模型
│   │   │   ├── Project.php     # 项目基础模型
│   │   │   ├── Oa.php          # OA 项目模型
│   │   │   ├── WechatMp.php    # 微信公众号模型
│   │   │   ├── WechatMini.php  # 微信小程序模型
│   │   │   └── Website.php     # 企业网站模型
│   │   ├── service/            # 项目业务层
│   │   │   ├── ProjectService.php    # 项目服务
│   │   │   ├── OaService.php         # OA 项目服务
│   │   │   ├── WechatMpService.php   # 微信公众号服务
│   │   │   ├── WechatMiniService.php # 微信小程序服务
│   │   │   └── WebsiteService.php    # 企业网站服务
│   │   └── route/              # 项目路由
│   │       └── project.php     # 项目路由配置
│   ├── wechat/                 # 微信生态模块（可选）
│   │   ├── controller/         # 微信控制器
│   │   ├── service/            # 微信服务层
│   │   │   ├── OfficialAccount.php   # 公众号服务
│   │   │   └── MiniProgram.php       # 小程序服务
│   │   └── model/              # 微信模型
│   └── job/                    # 队列任务（可选）
│       └── TestJob.php         # 测试任务
├── config/                     # 配置文件
│   ├── app.php                 # 应用配置
│   ├── cache.php               # 缓存配置
│   ├── console.php             # 控制台配置
│   ├── cookie.php              # Cookie 配置
│   ├── database.php            # 数据库配置
│   ├── lang.php                # 语言配置
│   ├── log.php                 # 日志配置
│   ├── middleware.php          # 中间件配置
│   ├── route.php               # 路由配置
│   ├── session.php             # Session 配置
│   ├── swagger.php             # Swagger 配置
│   ├── tenant.php              # 多租户配置（可选）
│   ├── project.php             # 多项目配置
│   └── trace.php               # 链路追踪配置
├── database/                   # 数据库文件
│   ├── migrations/             # 数据库迁移
│   │   ├── 001_init_tables.php # 初始化核心表
│   │   └── 002_project_tables.php# 项目表
│   └── seeders/                # 数据填充
│       ├── AdminSeeder.php     # 管理员数据
│       ├── MenuSeeder.php      # 菜单数据
│       └── ProjectSeeder.php   # 项目数据
├── public/                     # 公共资源
│   ├── index.php               # 入口文件
│   └── .htaccess               # Apache 重写规则
├── route/                      # 应用路由
│   └── app.php                 # 应用路由配置
├── runtime/                    # 运行时目录
├── storage/                    # 存储目录
│   ├── app/                    # 应用存储
│   │   ├── public/             # 公共存储
│   │   │   ├── uploads/        # 上传文件
│   │   │   └── projects/       # 项目文件
│   │   └── logs/               # 日志文件
│   ├── extend/                 # 扩展目录
│   └── logs/                   # 系统日志
├── tests/                      # 测试目录
│   ├── feature/                # 功能测试
│   └── unit/                   # 单元测试
├── think                       # 命令行入口
├── .env                        # 环境变量
├── .example.env                # 环境变量示例
├── composer.json               # Composer 依赖配置
└── README.md                   # 项目说明
```

---

## 💡 目录结构设计理念

### 核心设计原则
1. **模块化** - RBAC 核心与项目模块分离，按需加载
2. **可扩展性** - 支持快速添加新项目类型（OA、微信、网站等）
3. **代码复用** - 公共组件、工具函数统一封装
4. **清晰分层** - API 层、业务层、数据层职责明确

### 多项目支持机制
- **前端**：通过 `projects/` 目录和 `projectRoutes.ts` 实现项目页面按需加载
- **后端**：通过 `project/` 模块和独立路由实现项目业务隔离
- **配置**：通过 `project.ts` 配置文件管理不同项目的启用状态

### 外包项目快速集成
1. **OA 项目** - 启用 `oa/` 目录，添加 OA 专用组件和接口
2. **微信公众号** - 启用 `wechat-mp/` 目录，集成微信 SDK
3. **微信小程序** - 启用 `wechat-mini/` 目录，使用小程序 API
4. **企业网站** - 启用 `website/` 目录，添加网站管理功能

### 权限隔离
- 每个项目拥有独立的菜单、角色、接口权限
- 通过项目配置实现权限隔离
- 支持跨项目权限复用

---

## 🔐 权限体系

### 三级权限控制
1. **菜单权限** - 控制用户可访问的页面
2. **按钮权限** - 控制页面内的操作按钮
3. **接口权限** - 控制API接口访问

### 数据权限控制
| 权限级别 | 说明 | 适用场景 |
|---------|------|----------|
| 全部数据 | 可查看所有数据 | 超级管理员 |
| 本部门 | 仅可查看本部门数据 | 部门主管 |
| 本部门及以下 | 可查看本部门及下级部门数据 | 高层管理 |
| 仅本人 | 仅可查看自己的数据 | 普通员工 |
| 自定义 | 自定义数据权限范围 | 特殊需求 |

---

## 🔌 API接口规范

### RESTful规范
```
GET     /api/admin/users          # 获取用户列表
POST    /api/admin/users          # 创建用户
GET     /api/admin/users/{id}     # 获取用户详情
PUT     /api/admin/users/{id}     # 更新用户
DELETE  /api/admin/users/{id}     # 删除用户
```

### 统一响应格式
```json
{
  "code": 200,
  "message": "success",
  "data": {},
  "timestamp": 1677123456789
}
```

### HTTP状态码
- 200 - 成功
- 400 - 请求错误
- 401 - 未授权
- 403 - 禁止访问
- 404 - 资源不存在
- 500 - 服务器错误

---

## ⚙️ 核心功能模块

### 1. 用户管理
- ✅ 用户CRUD
- ✅ 用户状态管理
- ✅ 用户角色分配
- ✅ 用户导入导出
- ✅ 密码重置

### 2. 角色管理
- ✅ 角色CRUD
- ✅ 菜单权限分配
- ✅ 按钮权限分配
- ✅ 接口权限分配
- ✅ 数据权限配置

### 3. 菜单管理
- ✅ 菜单CRUD（树形）
- ✅ 菜单类型（目录/菜单/按钮）
- ✅ 菜单按钮配置
- ✅ 菜单排序
- ✅ 菜单图标配置

### 4. 部门管理
- ✅ 部门CRUD（树形）
- ✅ 部门负责人配置
- ✅ 部门排序
- ✅ 部门状态管理

### 5. 接口管理
- ✅ 接口CRUD
- ✅ 接口分组
- ✅ 接口权限分配

### 6. 日志管理
- ✅ 登录日志查询
- ✅ 操作日志查询
- ✅ 日志统计图表

### 7. 个人中心
- ✅ 个人信息查看
- ✅ 个人信息修改
- ✅ 头像上传
- ✅ 密码修改

---

## 🔒 安全机制

### 认证安全
- ✅ JWT Token认证
- ✅ Token无感刷新
- ✅ 登录失败锁定
- ✅ 单点登录支持

### 权限安全
- ✅ 三级权限控制
- ✅ 数据权限控制
- ✅ RBAC权限模型
- ✅ 权限缓存

### 接口安全
- ✅ 接口限流
- ✅ SQL注入防护
- ✅ XSS攻击防护
- ✅ CSRF Token验证

---

## 📦 交付物清单

### 代码交付
- [x] 前端项目完整源码
- [x] 后端项目完整源码
- [x] 数据库表结构SQL
- [x] 初始化数据SQL
- [x] 默认管理员账号

### 文档交付
- [x] 系统架构设计文档（SPEC.md）
- [x] 开发任务清单（TASKS.md）
- [x] 验收检查清单（CHECKLIST.md）
- [x] 接口文档（Swagger）
- [x] 部署文档

### 环境交付
- [ ] 开发环境配置
- [ ] 测试环境配置
- [ ] 生产环境配置

---

## 📈 开发周期预估

| 阶段 | 任务数 | 预估工时 | 说明 |
|------|--------|----------|------|
| 项目初始化 | 2 | 8小时 | 前后端项目搭建 |
| 核心模块开发 | 19 | 220小时 | 主要功能开发 |
| 数据库与初始化 | 2 | 8小时 | 数据库设计与数据初始化 |
| 系统集成与测试 | 4 | 48小时 | 前后端联调与测试 |
| 部署与文档 | 2 | 16小时 | 项目部署与文档编写 |
| **总计** | **31** | **300小时** | 约37.5个工作日 |

---

## ✅ 验收标准

### 功能验收
- 所有CRUD功能正常运行
- 权限控制精确到按钮级别
- 菜单动态生成
- 数据权限正确过滤
- 日志完整记录
- Token无感刷新正常
- UI 1:1复刻参考项目

### 性能验收
- 首屏加载 < 3秒
- 接口响应 < 500ms
- 支持100+并发用户

### 安全验收
- 密码加密存储
- Token安全验证
- SQL注入防护
- XSS攻击防护
- CSRF Token验证

---

## 🔮 扩展预留

### 多项目接入
- 提供统一认证SDK
- 支持多项目单点登录
- 项目隔离机制

### 微信生态
- 微信公众号OAuth2.0接入
- 微信小程序登录授权
- 企业微信集成

### 第三方系统
- OAuth2.0标准对接
- CAS单点登录
- LDAP/AD集成

### 多租户（预留）
- 租户数据隔离
- 租户配置独立
- 租户资源配额

---

## 👥 项目团队

| 角色 | 职责 | 人数 |
|------|------|------|
| 项目经理 | 项目整体管理 | 1 |
| 前端开发 | 前端页面开发 | 2 |
| 后端开发 | 后端接口开发 | 2 |
| DBA | 数据库设计与优化 | 1 |
| 测试工程师 | 功能与性能测试 | 1 |
| UI设计师 | UI设计（1:1复刻） | 1 |

---

## 📅 项目时间线

### 第一阶段：基础框架（1-2天）
- 项目初始化
- 目录结构搭建
- 技术栈集成
- 公共组件封装

### 第二阶段：数据库设计（1-2天）
- 数据库表设计
- 数据初始化SQL
- 模型层开发
- 基础服务层

### 第三阶段：核心权限（3-4天）
- 认证模块
- 用户管理
- 角色管理
- 菜单管理
- 部门管理

### 第四阶段：业务功能（2-3天）
- 接口管理
- 日志管理
- 字典管理
- 配置管理
- 个人中心

### 第五阶段：前端UI（3-4天）
- 布局组件开发
- 页面组件开发
- 权限指令开发
- 动态路由开发
- UI 1:1复刻

### 第六阶段：集成测试（2-3天）
- 前后端联调
- 功能测试
- 性能测试
- 安全测试
- Bug修复

### 第七阶段：文档部署（1-2天）
- 开发文档编写
- 部署文档编写
- 正式环境部署
- 用户培训

**总工期：13-20个工作日**

---

## 📞 支持与联系

如有问题，请联系项目开发团队。

---

*文档版本：v1.0*  
*创建日期：2026-04-25*  
*最后更新：2026-04-25*
