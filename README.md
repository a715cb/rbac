# 企业级 RBAC 权限系统

> 通用可复用企业级 RBAC 权限底座系统，统一支撑自动化办公、企业网站、微信公众号、微信小程序等多项目权限鉴权。

## 📚 项目文档

本项目已完成系统架构设计和开发规划，完整技术规格文档位于 `.trae/specs/` 目录下，符合 **TRAE CN SOLO Coder** 规范：

### 核心规范文档（SOLO Coder）
- **[spec.md](.trae/specs/rbac-system/spec.md)** - 完整技术规格文档（架构、数据库 SQL、API 规范等）
- **[tasks.md](.trae/specs/rbac-system/tasks.md)** - 开发任务清单（23 个开发任务，带状态跟踪）
- **[checklist.md](.trae/specs/rbac-system/checklist.md)** - 验收检查清单（200+ 检查项）

### 架构文档
- **[OVERVIEW.md](docs/architecture/OVERVIEW.md)** - 项目概览（快速了解项目）

### 🤖 SOLO Coder 使用说明
本项目完全符合 TRAE CN IDE 的 SOLO Coder 规范，支持自动化开发：

```bash
# 在 TRAE IDE 中，输入以下命令启动 Spec 模式
/spec

# SOLO Coder 会自动读取 .trae/specs/rbac-system/ 目录
# 并按照 tasks.md 中的任务列表自动执行开发
```

**SOLO Coder 工作流程：**
1. 分析需求 → 生成 spec.md 大纲
2. 任务拆解 → 生成 tasks.md 任务列表
3. 验收标准 → 生成 checklist.md 检查清单
4. 等待确认 → 用户确认文档
5. 自动执行 → 按任务列表逐项完成开发
6. 状态更新 → 自动更新任务状态

## 🎯 项目概述

### 核心定位
通用可复用企业级RBAC权限底座系统，统一支撑自动化办公、企业网站、微信公众号、微信小程序等多项目权限鉴权。

### 核心特征
- **高通用性** - 抽象通用权限模型，适配多种业务场景
- **强可移植性** - 模块化设计，支持快速集成到新项目
- **标准化** - 遵循行业最佳实践，统一接口规范
- **低耦合** - 前后端分离，分层架构，模块间解耦

## 🛠️ 技术栈

### 前端技术栈（固定）
- Vue 3 + TypeScript + Vite
- Vue Router + Pinia
- Ant Design Vue + Tailwind CSS
- Less + Axios
- Iconify + VueUse

### 后端技术栈（固定）
- ThinkPHP 8 + MySQL 8 + Redis
- JWT Token认证

## 📊 数据库设计

### 14张核心数据表
1. `sys_user` - 用户表
2. `sys_department` - 部门表（树形）
3. `sys_role` - 角色表
4. `sys_user_role` - 用户角色关联表
5. `sys_menu` - 菜单表（树形）
6. `sys_role_menu` - 角色菜单关联表
7. `sys_menu_button` - 菜单按钮表
8. `sys_role_menu_button` - 角色菜单按钮关联表
9. `sys_api` - 接口表
10. `sys_role_api` - 角色接口关联表
11. `sys_login_log` - 登录日志表
12. `sys_operation_log` - 操作日志表
13. `sys_dict_type` - 字典类型表
14. `sys_dict_data` - 字典数据表

## 🔐 权限体系

### 三级权限控制
- **菜单权限** - 控制用户可访问的页面
- **按钮权限** - 控制页面内的操作按钮
- **接口权限** - 控制API接口访问

### 数据权限控制
- 全部数据权限
- 本部门数据权限
- 本部门及以下数据权限
- 仅本人数据权限
- 自定义数据权限

## 📁 项目结构

```
d:/AI/RBAC/
├── .trae/                     # TRAE IDE 配置
│   └── specs/                 # SOLO Coder 规范文档
│       └── rbac-system/       # RBAC 系统任务
│           ├── spec.md        # 技术规格文档
│           ├── tasks.md       # 开发任务列表
│           └── checklist.md   # 验收检查清单
├── docs/                      # 项目文档
│   └── architecture/          # 架构文档
│       └── OVERVIEW.md        # 项目概览
├── frontend/                  # 前端项目（待开发）
├── backend/                   # 后端项目（待开发）
└── database/                  # 数据库文件（待创建）
    └── init.sql               # 初始化 SQL（待创建）
```

## 📦 交付物清单

### 代码交付
- ✅ 前端项目完整源码
- ✅ 后端项目完整源码
- ✅ 数据库表结构SQL（14张表）
- ✅ 初始化数据SQL
- ✅ 默认管理员账号

### 文档交付
- ✅ 系统架构设计文档（spec.md）
- ✅ 开发任务清单（tasks.md）
- ✅ 验收检查清单（checklist.md）
- ✅ 项目概览文档（OVERVIEW.md）
- ✅ 接口文档（Swagger，待开发）
- ✅ 部署文档（待编写）

## ⏱️ 开发周期预估

| 阶段 | 任务数 | 预估工时 |
|------|--------|----------|
| 项目初始化 | 2 | 8小时 |
| 核心模块开发 | 19 | 220小时 |
| 数据库与初始化 | 2 | 8小时 |
| 系统集成与测试 | 4 | 48小时 |
| 部署与文档 | 2 | 16小时 |
| **总计** | **31** | **300小时** |

**总工期：13-20个工作日**

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

## 🚀 下一步行动

### 架构设计阶段 ✅ 已完成
- [x] 系统架构设计
- [x] 数据库设计（14 张表）
- [x] 开发任务清单（23 个任务）
- [x] 验收标准（200+ 检查项）
- [x] 完整技术规格文档
- [x] SOLO Coder 规范配置

### 开发实施阶段 ⏸️ 等待确认

#### 方式一：使用 SOLO Coder（推荐）⭐
```bash
# 在 TRAE IDE 中，输入以下命令启动 Spec 模式
/spec

# SOLO Coder 会自动：
# 1. 读取 .trae/specs/rbac-system/ 目录
# 2. 按照 tasks.md 执行 23 个开发任务
# 3. 自动更新任务状态
# 4. 完成所有开发工作
```

#### 方式二：手动开发
- [ ] 前端项目初始化
- [ ] 后端项目初始化
- [ ] 核心模块开发
- [ ] 系统集成测试
- [ ] 部署上线

---

**📌 当前状态**：所有架构设计已完成，可以使用 SOLO Coder 启动自动化开发

---

## 📞 联系方式

如有问题或需要进一步讨论，请随时联系项目团队。

---

*项目版本：v1.0*  
*创建日期：2026-04-25*  
*文档状态：✅ 架构设计完成，等待开发确认*
