# RBAC 权限管理系统 - 前端

> 基于 Vue 3 + TypeScript + Vite 构建的企业级 RBAC（基于角色的访问控制）权限管理前端应用

## 📖 项目简介

本项目是企业级 RBAC 权限管理系统的前端部分，提供完整的用户、角色、菜单、部门、接口等权限管理功能。系统采用前后端分离架构，支持动态路由加载、多级权限控制、数据权限过滤等企业级特性。

### 核心特性

- **动态路由**：根据用户权限动态生成可访问菜单与路由
- **三级权限控制**：菜单权限、按钮权限、接口权限精细化管理
- **数据权限**：支持全部、本部门、本部门及以下、仅本人、自定义等多种数据权限范围
- **Token 无感刷新**：JWT Token 自动续期，提升用户体验
- **标签页导航**：支持多标签页切换，记录访问历史
- **权限指令**：通过 `v-auth` 指令快速控制按钮显隐与禁用
- **布局切换**：支持侧边栏、顶部、混合等多种布局模式
- **主题定制**：支持亮色/暗色/深色暗黑等多种主题
- **表格设置**：支持列显隐控制、全屏、刷新、尺寸调整
- **拖拽排序**：支持表格和列表拖拽排序

## 🛠️ 技术栈

| 类别 | 技术 | 版本 |
|------|------|------|
| 核心框架 | Vue 3 (Composition API) | ^3.4.0 |
| 构建工具 | Vite | ^5.0.12 |
| 类型系统 | TypeScript | ^5.3.3 |
| UI 组件库 | Ant Design Vue | ^4.1.0 |
| 状态管理 | Pinia | ^2.1.7 |
| 路由管理 | Vue Router | ^4.2.5 |
| HTTP 客户端 | Axios | ^1.6.5 |
| 工具库 | VueUse | ^10.7.1 |
| 日期处理 | Day.js | ^1.11.10 |
| CSS 框架 | Tailwind CSS | ^3.4.1 |
| CSS 预处理器 | Less | ^4.2.0 |
| 页面进度条 | NProgress | ^0.2.0 |
| 拖拽排序 | Sortable.js | ^1.15.7 |
| 代码高亮 | Highlight.js | ^11.11.1 |
| 代码规范 | ESLint + Prettier | - |

## 📦 快速开始

### 环境要求

- Node.js >= 18.0.0
- npm >= 9.0.0 或 pnpm >= 8.0.0

> 完整环境要求（PHP/MySQL/Redis 等）请参阅 [安装与部署指南](../docs/deployment/DEPLOYMENT.md)

### 安装依赖

```bash
cd frontend
npm install
```

### 环境配置

复制环境变量模板文件并根据实际情况修改：

```bash
cp .env.development .env.local
```

环境变量说明：

| 变量名 | 说明 | 默认值 |
|--------|------|--------|
| `VITE_APP_TITLE` | 应用标题 | RBAC System |
| `VITE_APP_PORT` | 开发服务器端口 | 5173 |
| `VITE_APP_BASE_API` | API 请求前缀 | /api |
| `VITE_APP_API_BASE_URL` | 后端 API 地址 | http://localhost:8000 |
| `VITE_APP_STORAGE_PREFIX` | 存储键前缀 | rbac_admin |
| `VITE_APP_VIEW_COMPONENT_PREFIX` | 视图组件路径前缀 | @/pages/ |
| `VITE_APP_ERROR_COMPONENT_PATH` | 错误页面组件路径 | @/pages/error/404.vue |

### 启动开发服务器

```bash
npm run dev
```

启动成功后，浏览器将自动打开 [http://localhost:5173](http://localhost:5173)。

### 构建生产版本

```bash
# 类型检查 + 构建
npm run build

# 仅构建（跳过类型检查）
npx vite build
```

构建产物将输出到 `dist` 目录。

### 预览生产构建

```bash
npm run preview
```

## 📁 项目结构

```
frontend/
├── public/                       # 静态资源
│   ├── favicon.svg               # 网站图标
│   └── icons.svg                 # SVG 图标集
├── src/
│   ├── api/                      # API 接口定义（10 个模块）
│   │   ├── api.ts                # 接口管理 API
│   │   ├── auth.ts               # 认证相关 API
│   │   ├── dashboard.ts          # 仪表盘 API
│   │   ├── dept.ts               # 部门树 API
│   │   ├── dict.ts               # 字典管理 API
│   │   ├── loginLog.ts           # 登录日志 API
│   │   ├── menu.ts               # 菜单管理 API
│   │   ├── operateLog.ts         # 操作日志 API
│   │   ├── role.ts               # 角色管理 API
│   │   └── user.ts               # 用户管理 API
│   ├── assets/                   # 静态资源
│   │   ├── svgs/                 # SVG 图标目录
│   │   └── logo.svg              # 项目 Logo
│   ├── components/               # 公共组件库
│   │   ├── Breadcrumb/           # 面包屑组件
│   │   ├── Button/               # 自定义按钮
│   │   ├── Icon/                 # 图标组件
│   │   │   ├── SIcon.vue          # 图标渲染组件
│   │   │   ├── SIconSelect.vue    # 图标选择器组件
│   │   │   ├── components/        # 图标子组件
│   │   │   │   ├── IconSelector.vue # 图标浏览器（分组 + 搜索）
│   │   │   │   └── icons.ts       # 图标库数据（6 大分类）
│   │   ├── TableSetting/         # 表格设置（列设置/全屏/刷新/尺寸）
│   │   ├── TokenProvider/        # CSS Token 变量提供者
│   │   ├── Captcha/             # 验证码组件
│   │   └── index.ts              # 组件统一导出
│   ├── composables/              # 组合式函数
│   │   ├── usePageTable.ts        # 分页表格逻辑
│   │   ├── useMenuTree.ts         # 菜单树数据处理
│   │   ├── useDeptTree.ts         # 部门树数据处理
│   │   ├── useTreeSearch.ts       # 树搜索逻辑
│   │   ├── useSortable.ts         # 拖拽排序
│   │   └── index.ts
│   ├── config/                   # 全局配置
│   │   └── index.ts              # AppConfig（存储键、路由前缀等）
│   ├── directives/               # 自定义指令
│   │   └── auth.ts               # 权限指令 (v-auth / v-auth-disabled)
│   ├── composables/              # 组合式函数（布局相关）
│   │   ├── useHeaderSetting.ts
│   │   ├── useSetting.ts         # 主题/布局设置
│   │   └── index.ts
│   ├── layouts/                  # 布局组件
│   │   ├── components/
│   │   │   ├── Footer.vue        # 页脚
│   │   │   ├── Header.vue        # 顶部导航栏
│   │   │   ├── Menu/             # 菜单组件
│   │   │   ├── SettingDrawer/    # 设置抽屉（主题/布局切换）
│   │   │   ├── Sidebar/          # 侧边栏（含左侧导航）
│   │   │   ├── TagsView/         # 标签页导航
│   │   │   ├── Widget/           # Logo、触发器、用户菜单
│   │   │   ├── menuUtils.ts      # 菜单工具函数
│   │   │   └── index.ts          # 组件导出
│   │   ├── composables/              # 布局相关组合式函数
│   │   │   ├── useHeaderSetting.ts
│   │   │   ├── useSetting.ts     # 主题/布局设置
│   │   │   └── index.ts
│   │   ├── DefaultLayout.vue     # 默认布局
│   │   └── index.ts
│   ├── pages/                    # 页面视图
│   │   ├── dashboard/            # 仪表盘
│   │   ├── error/                # 错误页面 (403 / 404)
│   │   ├── login/                # 登录页
│   │   ├── monitor/              # 监控中心
│   │   │   ├── login/            # 登录日志
│   │   │   └── operation/        # 操作日志
│   │   └── system/               # 系统管理
│   │       ├── api/              # 接口管理（含 ApiFormModal）
│   │       ├── dept/             # 部门管理（含 DeptFormModal / DeptUsersModal）
│   │       ├── dict/             # 字典管理（含 DictTypeModal / DictDataModal）
│   │       ├── menu/             # 菜单管理（含 MenuFormModal / MenuButtonModal）
│   │       ├── role/             # 角色管理（含 RoleFormModal / PermissionModal / DataScopeModal）
│   │       └── user/             # 用户管理（含 UserFormModal / ResetPasswordModal / DeptTree）
│   ├── router/                   # 路由配置
│   │   ├── dynamic.ts            # 动态路由生成（菜单树 → 路由配置）
│   │   └── index.ts              # 路由入口（含守卫、白名单）
│   ├── stores/                   # Pinia 状态管理
│   │   ├── index.ts              # Store 入口
│   │   ├── types.ts              # Store 类型定义
│   │   └── user.ts               # 用户状态（Token、角色、权限、菜单）
│   ├── styles/                   # 全局样式
│   │   ├── global/               # 全局样式
│   │   │   ├── index.less        # 样式入口
│   │   │   ├── reset.less        # 样式重置
│   │   │   ├── transition.less   # 过渡动画
│   │   │   ├── variables.css     # CSS 变量
│   │   │   └── variables.less    # Less 变量
│   │   └── layout/               # 布局相关样式
│   │       ├── header.less
│   │       ├── sidebar.less
│   │       ├── tags-view.less
│   │       └── variables.less
│   ├── types/                    # TypeScript 类型定义
│   │   ├── api.ts                # API 响应/分页类型
│   │   ├── global.ts             # 全局类型
│   │   └── index.ts
│   ├── utils/                    # 工具函数
│   │   ├── common.ts             # 通用工具
│   │   ├── dom.ts                # DOM 工具（CSS 变量获取）
│   │   ├── request.ts            # Axios 请求封装（拦截器 + Token 刷新）
│   │   ├── storage.ts            # 本地存储封装（sessionStorage / localStorage）
│   │   ├── token.ts              # Token 管理工具
│   │   └── common.ts
│   ├── App.vue                   # 根组件
│   ├── main.ts                   # 应用入口
│   └── vite-env.d.ts             # Vite 环境类型声明
├── .env.development              # 开发环境变量
├── .env.production               # 生产环境变量
├── .eslintrc.cjs                 # ESLint 配置
├── .prettierrc.json              # Prettier 配置
├── .eslintignore                 # ESLint 忽略
├── .prettierignore               # Prettier 忽略
├── index.html                    # HTML 入口
├── package.json                  # 项目依赖
├── tailwind.config.js            # Tailwind CSS 配置
├── tsconfig.json                 # TypeScript 配置
├── tsconfig.app.json             # 应用 TS 配置
├── tsconfig.node.json            # Node TS 配置
├── vite.config.ts                # Vite 配置
├── postcss.config.js             # PostCSS 配置
```

## 🔧 功能模块

### 1. 认证模块
- 用户登录（账号密码）
- Token 管理与持久化（sessionStorage）
- Token 无感刷新（Axios 拦截器自动处理）
- 自动登出

### 2. 用户管理
- 用户列表展示与多条件搜索
- 用户新增、编辑、删除
- 用户角色分配
- 密码重置
- 用户状态管理（启用/禁用）
- 用户导入导出

### 3. 角色管理
- 角色列表展示与搜索
- 角色新增、编辑、删除
- 权限配置（菜单权限、按钮权限）
- 数据权限配置（5 级数据范围）
- 接口权限配置

### 4. 菜单管理
- 菜单树形展示
- 菜单新增、编辑、删除
- 菜单按钮管理（增删改查）
- 图标选择器（分组浏览 + 关键词搜索，6 大分类、300+ 图标）
- 菜单路由与组件配置
- 菜单变更后自动清除前端缓存，刷新时重新加载最新权限

### 5. 部门管理
- 部门树形展示
- 部门新增、编辑、删除
- 部门状态/排序管理
- 部门用户管理

### 6. 接口管理
- 接口列表展示与搜索
- 接口新增、编辑、删除
- 接口分组筛选
- 接口与菜单关联

### 7. 日志管理
#### 登录日志
- 登录日志列表（IP、UA、OS、浏览器）
- 登录统计图表
- 日志清理

#### 操作日志
- 操作日志列表（模块、参数、结果、耗时）
- 操作统计图表
- 日志清理

### 8. 字典管理
- 字典类型管理
- 字典数据管理
- 字典数据排序

### 9. 布局功能
- 多种布局模式（侧边栏 / 顶部 / 混合）
- 主题切换（亮色 / 暗色 / 深色暗黑）
- 标签页导航
- 侧边栏折叠
- 面包屑导航
- 全屏切换

## 💡 使用示例

### 权限指令使用

```vue
<template>
  <!-- 根据权限控制按钮显示 -->
  <a-button v-auth="'system:user:add'">新增用户</a-button>

  <!-- 根据权限控制按钮禁用 -->
  <a-button v-auth-disabled="'system:user:delete'">删除</a-button>
</template>
```

### API 调用示例

```typescript
import { getUserList, createUser } from '@/api/user'

// 获取用户列表
const { data } = await getUserList({ page: 1, limit: 10 })

// 创建用户
await createUser({
  username: 'admin',
  nickname: '管理员',
  password: '123456',
  role_ids: [1]
})
```

### 状态管理使用

```typescript
import { useUserStore } from '@/stores/user'

const userStore = useUserStore()

// 获取用户信息（含菜单、权限、角色）
await userStore.fetchUserInfo()

// 获取 Token
const token = userStore.token

// 获取角色列表
const roles = userStore.roleCodes

// 登出
await userStore.logout()
```

## ⌨️ 常用命令

| 命令 | 说明 |
|------|------|
| `npm run dev` | 启动开发服务器 |
| `npm run build` | 类型检查并构建生产版本 |
| `npm run preview` | 预览生产构建 |
| `npm run lint` | 执行 ESLint 代码检查并自动修复 |
| `npm run format` | 使用 Prettier 格式化代码 |

## 🌐 部署说明

### 构建产物

执行 `npm run build` 后，构建产物将生成在 `dist` 目录：

```
dist/
├── index.html
└── static/
    ├── css/
    ├── js/
    └── images/
```

### Nginx 配置示例

```nginx
server {
    listen       80;
    server_name  your-domain.com;

    location / {
        root   /usr/share/nginx/html;
        index  index.html;
        try_files $uri $uri/ /index.html;
    }

    # API 代理
    location /api/ {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

### 环境变量

生产环境需配置 `.env.production` 文件：

```env
VITE_APP_TITLE=RBAC System
VITE_APP_BASE_API=/api
VITE_APP_API_BASE_URL=https://api.example.com
VITE_APP_STORAGE_PREFIX=rbac_admin
VITE_APP_VIEW_COMPONENT_PREFIX=@/pages/
VITE_APP_ERROR_COMPONENT_PATH=@/pages/error/404.vue
```

## ❓ 常见问题

### 1. 开发环境跨域问题

本项目在 `vite.config.ts` 中已配置开发代理，确保 `.env.local` 中的 `VITE_APP_API_BASE_URL` 指向正确的后端地址。

### 2. Token 过期处理

当 Token 过期时，Axios 拦截器会自动尝试使用 refresh_token 刷新。如果刷新失败，将自动跳转至登录页并清除本地存储。

### 3. 菜单不显示

请检查：
- 当前用户是否已分配角色
- 角色是否已配置菜单权限
- 后端的 `/admin/profile` 接口是否返回了正确的菜单树

### 4. 类型检查报错

执行 `npm run build` 时 TypeScript 类型检查失败，请确保所有 `.vue` 文件的 `<script setup>` 中类型定义正确。可执行 `npx vue-tsc -b` 查看详细错误信息。

### 5. 样式不生效

- 确认 Less 预处理器配置正确
- 检查 `variables.less` 中的变量是否被正确引入
- 确认组件是否引入了正确的样式文件

## 📋 开发规范

### 代码风格

- 使用 ESLint + Prettier 统一代码风格
- Vue 组件使用 `<script setup lang="ts">` 语法
- TypeScript 严格模式

### 命名规范

- 页面组件：kebab-case 文件，PascalCase 目录（如 `UserFormModal/` 目录）
- 工具函数：camelCase（如 `formatDate`）
- 常量：UPPER_SNAKE_CASE（如 `API_BASE_URL`）
- 类型定义：PascalCase（如 `UserInfo`、`ApiResponse`）
- 样式类：kebab-case + Tailwind utility classes

### Git 提交规范

提交信息格式：

```
<type>(<scope>): <subject>

<body>
```

常用 type：

- `feat`: 新功能
- `fix`: 修复 Bug
- `docs`: 文档更新
- `style`: 代码格式调整
- `refactor`: 重构
- `test`: 测试相关
- `chore`: 构建/工具链相关

## 🔗 相关资源

- [Vue 3 官方文档](https://cn.vuejs.org/)
- [Vite 官方文档](https://cn.vitejs.dev/)
- [Ant Design Vue 文档](https://antdv.com/)
- [Pinia 官方文档](https://pinia.vuejs.org/zh/)
- [Vue Router 官方文档](https://router.vuejs.org/zh/)
- [TypeScript 官方文档](https://www.typescriptlang.org/zh/)

---

*项目版本：v1.0*
*技术栈：Vue 3 + TypeScript + Vite + Ant Design Vue + Tailwind CSS*
*最后更新：2026-05-12*
