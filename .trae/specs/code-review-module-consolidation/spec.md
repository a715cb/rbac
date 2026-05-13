# 代码审查与模块合并方案

## Why

当前 `frontend/src` 目录下存在严重的模块重复问题。`pages/system` 和 `pages/permission` 目录下存在大量功能完全相同的页面和组件，导致：

1. **维护成本高** - 修改一处逻辑需要同步修改两处代码
2. **代码冗余** - 重复的文件和代码占用存储空间
3. **一致性风险** - 两套代码可能逐渐出现行为不一致
4. **增加认知负担** - 开发者需要理解两套相似但独立的代码体系

## 当前状态

用户已完成第一阶段的删除操作：
- ✅ 已删除 `pages/system/api/` 目录
- ✅ 已删除 `pages/system/button/` 目录
- ✅ 已删除 `pages/system/menu/` 目录

删除依据：这些文件夹的内容已在之前的迁移工作中完整转移至 `pages/permission/` 目录。

## What Changes

### 一、重叠项分析（已完成）

#### 1. 页面级重复（已删除 system 端）

| System 模块（原） | Permission 模块（保留） | 重合度 |
|-----------------|----------------------|-------|
| `pages/system/api/index.vue` | `pages/permission/api/index.vue` | 99% |
| `pages/system/button/index.vue` | `pages/permission/button/index.vue` | 99% |
| `pages/system/menu/index.vue` | `pages/permission/menu/index.vue` | 99% |

#### 2. 弹窗组件重复（已删除 system 端）

| System 模块（原） | Permission 模块（保留） | 重合度 |
|-----------------|----------------------|-------|
| `pages/system/api/components/ApiFormModal.vue` | `pages/permission/api/components/ApiFormModal.vue` | 99% |
| `pages/system/button/components/ButtonFormModal.vue` | `pages/permission/button/components/ButtonFormModal.vue` | 99% |
| `pages/system/button/components/ButtonDetailModal.vue` | `pages/permission/button/components/ButtonDetailModal.vue` | 99% |
| `pages/system/menu/components/MenuFormModal.vue` | `pages/permission/menu/components/MenuFormModal.vue` | 99% |
| `pages/system/menu/components/MenuButtonModal.vue` | `pages/permission/menu/components/MenuButtonModal.vue` | 99% |

### 二、引用检查结果

#### 已验证无引用

经过代码搜索，确认以下位置无对已删除模块的引用：

| 搜索路径 | 搜索模式 | 结果 |
|---------|---------|------|
| `pages/permission/` | `@/pages/system` | 无匹配 |
| `src/` 全局 | `system/api\|system/button\|system/menu` | 无匹配 |

#### 路由配置说明

- 路由使用动态路由机制（`router/index.ts`）
- 页面组件由后端菜单配置动态加载
- 不存在硬编码的静态路由引用

### 三、当前目录结构

```
pages/
├── dashboard/        # 仪表盘
├── error/           # 错误页面
├── login/           # 登录
├── monitor/         # 监控模块
│   ├── login/
│   └── operation/
├── permission/      # ✅ 权限模块（保留）
│   ├── api/
│   │   ├── components/
│   │   │   └── ApiFormModal.vue
│   │   └── index.vue
│   ├── button/
│   │   ├── components/
│   │   │   ├── ButtonDetailModal.vue
│   │   │   └── ButtonFormModal.vue
│   │   └── index.vue
│   └── menu/
│       ├── components/
│       │   ├── MenuButtonModal.vue
│       │   └── MenuFormModal.vue
│       └── index.vue
└── system/          # ✅ 系统模块（保留）
    ├── dept/        # 部门管理
    ├── dict/        # 字典管理
    ├── role/        # 角色管理
    └── user/        # 用户管理
```

### 四、后续工作

#### 待检查项

1. **后端菜单配置** - 需要确认后端数据库中的菜单配置是否已更新
   - 如果仍指向 `system/api`、`system/button`、`system/menu`，需要更新为 `permission/*`
   - 或者将 `permission/*` 路径映射到现有组件

2. **前端动态路由映射**（可选）- 如果后端菜单配置暂无法修改
   - 在 `router/dynamic.ts` 中添加路径映射逻辑
   - 将 `system/*` 路径映射到 `permission/*` 组件

#### API 层检查

| API 文件 | 功能 | 状态 |
|---------|------|------|
| `api/menu.ts` | 菜单 CRUD + 菜单下按钮管理 | ✅ 正常 |
| `api/button.ts` | 按钮列表 CRUD + 批量操作 | ✅ 正常 |

- `menu.ts` 中的 `getMenuButtons`、`createMenuButton`、`updateMenuButton`、`deleteMenuButton` 函数保留
- 这些函数用于菜单详情页面的按钮管理功能

## Impact

- **Affected specs**: 用户管理、角色管理、部门管理、字典管理等系统模块不受影响
- **Affected code**:
  - 已删除 `pages/system/api/`、`pages/system/button/`、`pages/system/menu/` 目录
  - 动态路由配置不受影响
  - API 层不受影响

## 完成状态

### ✅ 已完成

- [x] 识别并删除重复的 system 模块文件（api、button、menu）
- [x] 检查 permission 模块中无对已删除模块的引用
- [x] 检查全局代码中无对已删除模块的引用
- [x] 确认路由配置使用动态机制，无硬编码引用

### ⏳ 待完成

- [ ] 确认后端菜单配置是否需要更新
- [ ] （可选）如果后端无法修改，添加前端路径映射
- [ ] 验证应用功能正常

## ADDED Requirements

### Requirement: 目录结构标准化

系统 SHALL 保持单一的模块划分，移除重复的 system 模块权限相关目录。

#### Scenario: 目录清理完成
- **WHEN** 开发者执行代码审查
- **THEN** `pages/system/` 目录下不存在 `api/`、`button/`、`menu/` 子目录
- **AND** 所有权限相关功能使用 `pages/permission/` 模块

### Requirement: 无悬挂引用

系统 SHALL 确保无代码引用已删除的模块。

#### Scenario: 引用检查完成
- **WHEN** 开发者执行代码搜索
- **THEN** 不存在对 `pages/system/api`、`pages/system/button`、`pages/system/menu` 的引用
- **AND** 路由配置正常工作

## REMOVED Requirements

### Requirement: System 模块权限子模块

**Reason**: System 模块下的 api、button、menu 子模块与 Permission 模块功能完全重复，已移除 System 端的重复代码。

**Migration**:
- 前端代码引用已清理
- 后端菜单配置需要同步更新（或前端添加路径映射）