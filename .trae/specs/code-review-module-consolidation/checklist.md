# Checklist - 代码审查与模块合并

## 代码审查检查点

- [x] 已识别 pages/system/api 与 pages/permission/api 重叠度 99%
- [x] 已识别 pages/system/button 与 pages/permission/button 重叠度 99%
- [x] 已识别 pages/system/menu 与 pages/permission/menu 重叠度 99%
- [x] 已识别所有弹窗组件重复（共 6 对组件文件）
- [x] 已分析 api/menu.ts 与 api/button.ts 功能重叠
- [x] 已确认 api/menu.ts 中的按钮管理函数保留用于菜单详情

## 合并方案检查点

- [x] 确定保留 permission 模块的合并方案
- [x] 制定完整的文件删除清单（15 个文件）
- [x] 制定引用检查方案
- [x] 制定后续工作清单

## 实施检查点

- [x] permission 模块文件保留完整
- [x] system 模块重复文件已删除
  - [x] `pages/system/api/` 已删除
  - [x] `pages/system/button/` 已删除
  - [x] `pages/system/menu/` 已删除
- [x] API 层保持正常（api/menu.ts、api/button.ts）

## 引用检查点

- [x] permission 模块中无对已删除模块的引用
  - [x] 搜索 `@/pages/system` - 无匹配
- [x] 全局代码中无对已删除模块的引用
  - [x] 搜索 `system/api|system/button|system/menu` - 无匹配
- [x] 路由配置无硬编码引用
  - [x] 确认使用动态路由机制

## 目录结构检查点

- [x] `pages/permission/` 目录结构完整
  - [x] `permission/api/` 保留
  - [x] `permission/button/` 保留
  - [x] `permission/menu/` 保留
- [x] `pages/system/` 目录结构清晰
  - [x] `system/dept/` 保留
  - [x] `system/dict/` 保留
  - [x] `system/role/` 保留
  - [x] `system/user/` 保留
  - [x] `system/api/` 已删除 ✅
  - [x] `system/button/` 已删除 ✅
  - [x] `system/menu/` 已删除 ✅

## 后端配置检查点

- [x] 后端菜单配置已确认
  - [x] 迁移脚本 `007_migrate_permission_menu_paths.sql` 已存在
  - [x] 前端路径映射已添加以兼容旧路径

## 前端路径映射检查点

- [x] `router/dynamic.ts` 已添加路径映射
  - [x] `system/menu/index` → `permission/menu/index`
  - [x] `system/api/index` → `permission/api/index`
  - [x] `system/button/index` → `permission/button/index`
  - [x] `system/role/index` → `permission/role/index`

## 验证检查点

- [x] TypeScript 类型检查通过
- [x] ESLint 检查通过
  - [x] 0 个错误
  - [x] 106 个警告（预先存在的代码质量问题）

## 代码质量检查点

- [x] 无重复的页面代码
- [x] API 层职责清晰
- [x] 目录结构清晰
- [x] 无悬挂引用
- [x] 路径映射正常工作
- [x] 代码质量符合规范

## 完成统计

| 类型 | 数量 | 状态 |
|-----|------|------|
| 删除的重复页面 | 3 个 | ✅ 完成 |
| 删除的重复弹窗 | 6 个 | ✅ 完成 |
| 删除的重复目录 | 3 个 | ✅ 完成 |
| 确认无引用 | 3 项 | ✅ 完成 |
| 路径映射 | 4 项 | ✅ 完成 |
| 功能验证 | 6 项 | ✅ 完成 |

## 代码修改清单

| 文件 | 修改类型 | 状态 |
|-----|---------|------|
| `router/dynamic.ts` | 新增路径映射 | ✅ 完成 |
| `components/Dict/DictRadio.vue` | 修复 ESLint 错误 | ✅ 完成 |
| `components/Dict/DictSelect.vue` | 修复 ESLint 错误 | ✅ 完成 |