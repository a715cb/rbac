# 目录组织结构规范

## 1. 项目根目录结构

```
RBAC/
├── .github/                    # GitHub 配置文件
│   ├── CODE_STYLE_GUIDE.md    # 代码风格指南
│   ├── DIRECTORY_STRUCTURE.md # 本文档
│   ├── NAMING_CONVENTIONS.md  # 命名约定
│   ├── VERSION_CONTROL.md     # 版本控制流程
│   ├── COLLABORATION.md       # 协作规范
│   ├── workflows/              # CI/CD 工作流
│   │   └── playwright.yml    # Playwright 测试流程
│   └── ISSUE_TEMPLATE/        # Issue 模板
│       └── bug_report.md
│
├── backend/                    # 后端应用 (PHP/ThinkPHP)
│   ├── app/                   # 应用核心代码
│   │   ├── admin/            # 后台管理模块
│   │   ├── miniapp/          # 小程序模块
│   │   ├── common/           # 公共类库
│   │   ├── model/            # 数据模型
│   │   └── service/          # 服务层
│   ├── config/                # 配置文件
│   ├── database/              # 数据库文件
│   │   └── migrations/       # 数据库迁移
│   ├── public/               # Web 根目录
│   ├── route/                # 路由定义
│   └── ...
│
├── frontend/                  # 前端应用 (Vue3/TypeScript)
│   ├── public/               # 静态资源
│   ├── src/                  # 源代码
│   │   ├── api/              # API 接口定义
│   │   ├── assets/           # 资源文件
│   │   ├── components/       # 公共组件
│   │   ├── composables/      # 组合式函数
│   │   ├── config/           # 应用配置
│   │   ├── constants/        # 常量定义
│   │   ├── directives/       # 自定义指令
│   │   ├── layouts/          # 布局组件
│   │   ├── pages/            # 页面组件
│   │   ├── router/           # 路由配置
│   │   ├── stores/           # 状态管理
│   │   ├── styles/           # 样式文件
│   │   ├── types/            # TypeScript 类型
│   │   ├── utils/            # 工具函数
│   │   ├── App.vue           # 根组件
│   │   └── main.ts           # 入口文件
│   └── ...
│
├── .agents/                   # AI 代理工具和技能
│   ├── skills/               # 技能定义
│   └── tools/                # 工具集
│       └── playwright/       # Playwright 测试
│           ├── tests/        # 测试用例
│           ├── test-reports/ # 测试报告
│           ├── test-screenshots/  # 测试截图
│           └── config.js     # 测试配置
│
└── README.md                  # 项目说明文档
```

---

## 2. 前端目录详细规范

### 2.1 src/ 目录结构

```
src/
├── api/                      # API 接口层
│   ├── api.ts               # API 基础配置
│   ├── auth.ts              # 认证相关 API
│   ├── user.ts              # 用户管理 API
│   ├── role.ts              # 角色管理 API
│   ├── menu.ts              # 菜单管理 API
│   ├── dept.ts              # 部门管理 API
│   ├── dict.ts              # 字典管理 API
│   ├── loginLog.ts          # 登录日志 API
│   ├── operateLog.ts        # 操作日志 API
│   └── dashboard.ts         # 仪表盘 API
│
├── components/              # 公共组件
│   ├── Button/              # 按钮组件
│   │   ├── SButton.vue
│   │   └── index.ts
│   ├── Dict/                # 字典组件
│   │   ├── DictRadio.vue
│   │   ├── DictSelect.vue
│   │   ├── DictTag.vue
│   │   └── index.ts
│   ├── Icon/                # 图标组件
│   ├── TableSetting/        # 表格设置组件
│   │   ├── ColumnSetting.vue
│   │   ├── FullScreenSetting.vue
│   │   ├── RefreshSetting.vue
│   │   ├── SizeSetting.vue
│   │   ├── TableSetting.vue
│   │   ├── types.ts
│   │   ├── useTableSetting.ts
│   │   └── index.ts
│   └── index.ts             # 组件导出
│
├── composables/             # 组合式函数
│   ├── index.ts             # 统一导出
│   ├── useDict.ts           # 字典使用
│   ├── usePageTable.ts      # 分页表格
│   ├── useSortable.ts       # 拖拽排序
│   ├── useTreeData.ts       # 树形数据
│   └── useTreeSearch.ts     # 树形搜索
│
├── layouts/                 # 布局组件
│   ├── DefaultLayout.vue    # 默认布局
│   ├── index.ts
│   └── components/          # 布局子组件
│       ├── Header.vue
│       ├── Sidebar/
│       ├── TagsView/
│       ├── SettingDrawer/
│       └── Widget/
│
├── pages/                   # 页面组件
│   ├── login/              # 登录页
│   │   └── index.vue
│   ├── dashboard/          # 仪表盘
│   │   └── index.vue
│   ├── error/              # 错误页面
│   │   ├── 403.vue
│   │   ├── 404.vue
│   │   └── 500.vue
│   ├── system/             # 系统管理
│   │   ├── user/
│   │   │   ├── index.vue
│   │   │   └── components/
│   │   ├── role/
│   │   │   ├── index.vue
│   │   │   └── components/
│   │   ├── dept/
│   │   │   ├── index.vue
│   │   │   └── components/
│   │   └── dict/
│   │       ├── index.vue
│   │       └── components/
│   ├── permission/         # 权限管理
│   │   ├── menu/
│   │   │   ├── index.vue
│   │   │   └── components/
│   │   ├── api/
│   │   │   ├── index.vue
│   │   │   └── components/
│   │   └── button/
│   │       ├── index.vue
│   │       └── components/
│   └── monitor/            # 系统监控
│       ├── login/
│       └── operation/
│
├── router/                 # 路由配置
│   ├── index.ts            # 路由主文件
│   └── dynamic.ts          # 动态路由
│
├── stores/                 # 状态管理
│   ├── index.ts            # store 导出
│   ├── user.ts             # 用户状态
│   └── types.ts            # 类型定义
│
├── styles/                 # 样式文件
│   ├── global/             # 全局样式
│   │   ├── index.less
│   │   ├── reset.less
│   │   ├── transition.less
│   │   ├── variables.less
│   │   └── variables.css
│   └── layout/             # 布局样式
│       ├── header.less
│       ├── sidebar.less
│       └── tags-view.less
│
├── types/                  # TypeScript 类型
│   ├── index.ts            # 统一导出
│   ├── api.ts              # API 类型
│   └── global.ts           # 全局类型
│
└── utils/                   # 工具函数
    ├── common.ts           # 通用工具
    ├── dom.ts              # DOM 操作
    ├── request.ts           # HTTP 请求
    ├── storage.ts           # 存储操作
    ├── token.ts             # Token 处理
    └── validators.ts        # 表单验证
```

### 2.2 组件目录规范

```
# 每个组件一个目录
ComponentName/
├── index.vue              # 主组件（必需）
├── ComponentName.vue      # 组件实现（可选）
├── ComponentNameItem.vue  # 子组件（可选）
├── types.ts               # 类型定义（可选）
├── useComponentName.ts    # 组合式函数（可选）
├── constants.ts           # 常量（可选）
└── README.md              # 组件说明（可选）
```

---

## 3. 后端目录详细规范

### 3.1 app/ 目录结构

```
app/
├── admin/                  # 后台管理模块
│   ├── controller/        # 控制器
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── RoleController.php
│   │   ├── MenuController.php
│   │   ├── DeptController.php
│   │   ├── DictController.php
│   │   ├── ApiController.php
│   │   ├── LoginLogController.php
│   │   ├── OperationLogController.php
│   │   ├── DashboardController.php
│   │   └── BaseController.php
│   │
│   ├── service/            # 服务层
│   │   └── AdminService.php
│   │
│   ├── validate/           # 验证器
│   │   ├── LoginValidate.php
│   │   ├── UserValidate.php
│   │   ├── RoleValidate.php
│   │   └── ...
│   │
│   ├── middleware/          # 中间件
│   │   ├── AuthCheck.php
│   │   ├── ApiPermission.php
│   │   └── RecordOperate.php
│   │
│   └── event/              # 事件
│       └── OperateLogEvent.php
│
├── miniapp/                # 小程序模块
│   ├── controller/
│   ├── service/
│   ├── middleware/
│   └── validate/
│
├── common/                 # 公共类库
│   ├── BaseController.php
│   ├── BaseModel.php
│   ├── BaseValidate.php
│   ├── AdminAuth.php
│   ├── JwtToken.php
│   ├── SimpleCache.php
│   └── exception/
│       └── AppException.php
│
├── model/                  # 数据模型
│   ├── User.php
│   ├── Role.php
│   ├── Menu.php
│   ├── Department.php
│   ├── Api.php
│   └── ...
│
└── service/                # 公共服务
    └── JwtService.php
```

### 3.2 控制器目录规范

```
controller/
├── AuthController.php      # 认证（登录、登出）
├── UserController.php      # 用户管理
├── RoleController.php      # 角色管理
├── MenuController.php      # 菜单管理
├── DeptController.php      # 部门管理
├── DictController.php      # 字典管理
├── ApiController.php       # API管理
├── LoginLogController.php  # 登录日志
├── OperationLogController.php  # 操作日志
├── DashboardController.php  # 仪表盘
├── ProfileController.php   # 个人中心
├── BaseController.php      # 基类控制器
└── index  # index 控制器
```

---

## 4. 测试目录规范

### 4.1 Playwright 测试结构

```
.agents/tools/playwright/
├── config.js              # 统一配置
├── tests/                  # 测试用例
│   ├── login/             # 登录测试
│   │   ├── login.spec.ts
│   │   └── login.e2e.ts
│   ├── user/              # 用户管理测试
│   │   ├── user-list.spec.ts
│   │   ├── user-create.spec.ts
│   │   └── user-crud.spec.ts
│   └── ...
│
├── test-reports/           # 测试报告（统一目录）
│   ├── html/              # HTML 报告
│   │   └── playwright-report/
│   └── json/              # JSON 报告
│       ├── playwright-report.json
│       └── test_report_{name}_{timestamp}.json
│
├── test-screenshots/       # 测试截图（统一目录）
│   ├── login/
│   ├── dashboard/
│   └── ...
│
└── test-results/           # Playwright 原生结果
    └── ...
```

### 4.2 测试报告命名规范

```
# 格式：{prefix}_{test_type}_{timestamp}.{format}
test_report_comprehensive_20260510_143020.json
test_report_api_20260510_143020.json
test_report_ui_20260510_143020.json
test_report_regression_20260510_143020.json
```

---

## 5. 配置文件规范

### 5.1 配置文件优先级

```
# 从高到低（后面的覆盖前面的）
1. .env                        # 通用配置
2. .env.development           # 开发环境
3. .env.production            # 生产环境
4. .env.local                # 本地覆盖（不提交）
```

### 5.2 配置文件示例

```
.env                           # 基础配置模板
.env.development              # 开发环境
.env.production               # 生产环境
.env.example                  # 配置示例（应提交）
```

---

## 6. 文档目录规范

### 6.1 文档存放位置

```
.github/                       # GitHub 配置（已在上方定义）
└── docs/                      # 项目文档（可选）

README.md                      # 项目根说明
CHANGELOG.md                   # 变更日志
CONTRIBUTING.md                # 贡献指南
LICENSE                        # 许可证
```

### 6.2 Wiki 子目录结构

```
.trae/wiki/                    # 项目 Wiki
├── 01-项目概述.md
├── 02-架构设计.md
├── 03-后端模块.md
├── 04-前端模块.md
├── 05-数据库设计.md
├── 06-核心类与函数.md
├── 07-API接口文档.md
├── 08-权限体系.md
├── 09-依赖关系.md
├── 10-环境配置与部署.md
├── 11-常见问题.md
└── README.md
```

---

## 7. 资源文件规范

### 7.1 前端资源

```
frontend/src/assets/
├── icons/                    # SVG 图标
│   ├── layout-left.svg
│   ├── layout-side.svg
│   ├── moon.svg
│   └── sun.svg
├── images/                  # 图片资源（如有）
│   └── logo.svg
└── fonts/                    # 字体文件（如有）
```

### 7.2 静态资源

```
frontend/public/
├── favicon.svg              # 网站图标
├── icons.svg                # 图标集
└── robots.txt               # 爬虫规则
```

---

## 8. 目录创建权限

### 8.1 新建目录原则

- 每个功能模块应有独立目录
- 目录名应使用有意义的英文名词
- 遵循上述规范中的命名约定
- 需在 CODE_STYLE_GUIDE.md 中记录

### 8.2 新建文件原则

- 遵循命名约定（参考 NAMING_CONVENTIONS.md）
- 确保文件放置在正确的目录
- 更新对应的导出文件（如 `index.ts`、`index.php`）

---

**版本**: v1.0
**最后更新**: 2026-05-10
**维护人**: 开发团队