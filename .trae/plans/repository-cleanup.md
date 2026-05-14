# 计划: RBAC 项目仓库冗余清理

## 概述
对 RBAC 权限管理系统项目执行仓库级别的冗余清理，移除一次性脚本、死代码、未使用的模块和运行时产物，确保项目开发、构建、测试流程不受影响。

## 背景
项目为前后端分离架构：前端 Vue3 + Vite + TypeScript + Ant Design Vue，后端 ThinkPHP 8 + PHP。经过全面代码审计，发现以下类别的冗余内容：

- 后端根目录存在两个一次性诊断/测试脚本
- 后端存在未被任何控制器调用的 Service 类（死代码）
- 前端存在未被任何组件导入的工具函数和类型定义
- 运行时产物（日志、缓存、构建输出）残留在磁盘上
- PHPUnit 缓存文件未被 .gitignore 有效排除

## 目标
- 移除后端根目录的一次性诊断和测试脚本
- 移除后端未被使用的 Service 死代码
- 移除前端未被导入的工具函数、类型定义和常量
- 清理磁盘上的运行时产物（日志、缓存、构建输出）
- 清理相关的导出引用，保持代码一致性
- 验证项目构建和功能完整性不受影响

## 非目标
- 不删除开发依赖（devDependencies）— 保留 eslint, vitest, prettier, typescript 等
- 不删除测试文件（tests/ 目录和 *.test.ts）— 保留 CI 流水线完整性
- 不删除符合 comment-standards.md 规范的注释 — 仅删除明显过时/无意义的注释
- 不修改业务逻辑代码
- 不删除 .trae/、docs/、.github/ 等开发文档目录
- 不删除 node_modules/、vendor/ 等依赖目录

## 任务列表

### 任务 1: 删除后端一次性诊断脚本
- **描述**: 删除 `backend/check_user_dept_consistency.php`（用户-部门一致性校验脚本）和 `backend/test_status_api.php`（角色状态 API 测试脚本，含硬编码凭据 admin/123456）
- **文件**: 
  - `backend/check_user_dept_consistency.php`
  - `backend/test_status_api.php`
- **验收标准**: 两个文件已删除，后端路由和控制器无引用这些脚本
- **估计工作量**: Quick

### 任务 2: 删除后端未使用的 AdminService 死代码
- **描述**: `AdminService` 是 `AdminAuth` 和 `JwtToken` 的单例封装类，但没有任何控制器或中间件通过 `AdminService::getInstance()` 调用它。所有控制器直接使用 `AdminAuth::instance()` 和 `JwtToken::generate()/parse()`。该类属于冗余的中间层。
- **文件**: `backend/app/admin/service/AdminService.php`
- **验收标准**: 文件已删除，全局搜索无 `AdminService` 引用（除自身定义外）
- **估计工作量**: Quick

### 任务 3: 删除后端未使用的 JwtService 死代码
- **描述**: `JwtService` 是 ThinkPHP Service 类，将 `JwtToken` 绑定到容器，但没有任何代码通过容器解析 `jwt.token`。所有控制器直接使用 `JwtToken` 静态方法。此外 `JwtService::boot()` 中的密钥验证逻辑与 `JwtToken::validateSecret()` 重复。
- **文件**: `backend/app/service/JwtService.php`
- **验收标准**: 文件已删除，确认 `provider.php` 中无注册引用，全局搜索无 `JwtService` 引用
- **估计工作量**: Quick

### 任务 4: 删除前端未使用的 constants/storage.ts
- **描述**: `prefixedKey()` 函数导出至 `config/index.ts`，但没有任何组件或模块导入使用它。`StorageManager` 已内置前缀逻辑（`addPrefix` 函数），`prefixedKey` 是冗余的替代方案。
- **文件**: `frontend/src/constants/storage.ts`
- **验收标准**: 文件已删除，`config/index.ts` 中移除对应的 re-export 行，全局搜索无 `prefixedKey` 引用
- **估计工作量**: Quick

### 任务 5: 删除前端未使用的 types/global.ts
- **描述**: `Option` 接口从 `types/index.ts` re-export，但没有任何文件导入使用。项目中使用的是 `composables/useDict.ts` 中定义的 `DictOption` 类型。
- **文件**: `frontend/src/types/global.ts`
- **验收标准**: 文件已删除，`types/index.ts` 中移除 `export type { Option } from './global'` 行，全局搜索无 `from '@/types/global'` 引用
- **估计工作量**: Quick

### 任务 6: 清理前端 config/index.ts 中的死导出
- **描述**: 移除 `config/index.ts` 中对已删除模块的 re-export：`prefixedKey`（来自 storage.ts）和 `migrateUnprefixedKeys`（来自 migration.ts）。注意：`migrateUnprefixedKeys` 仍在 `main.ts` 中被调用，因此 `migration.ts` 文件本身需保留，但 re-export 可清理。
- **文件**: `frontend/src/config/index.ts`
- **验收标准**: 移除 `prefixedKey` 的 re-export 行，确认 `migration.ts` 和 `main.ts` 的调用链不受影响
- **估计工作量**: Quick

### 任务 7: 清理后端运行时产物
- **描述**: 删除 `runtime/log/` 下的日志文件、`runtime/cache/` 下的缓存文件、`runtime/data/` 空目录、以及 `.phpunit.result.cache`。这些文件已在 .gitignore 中但残留在磁盘上。
- **文件**: 
  - `backend/runtime/log/*.log`（7 个文件）
  - `backend/runtime/cache/*.cache`（4 个文件）
  - `backend/runtime/data/`（空目录）
  - `backend/.phpunit.result.cache`
- **验收标准**: runtime 目录下仅保留空目录结构（log/、cache/、data/），.phpunit.result.cache 已删除
- **估计工作量**: Quick

### 任务 8: 清理前端构建产物
- **描述**: 删除 `frontend/dist/` 目录。这是 Vite 构建输出，已在 .gitignore 中但残留在磁盘上。
- **文件**: `frontend/dist/`（整个目录）
- **验收标准**: dist 目录已删除
- **估计工作量**: Quick

### 任务 9: 验证项目完整性
- **描述**: 执行前端构建（`npm run build`）和后端语法检查，确保清理操作未破坏任何功能。运行前端 lint 检查。
- **文件**: 无文件修改
- **验收标准**: 
  - 前端 `npm run build` 成功
  - 前端 `npm run lint` 无新增错误
  - 后端 PHP 语法检查通过
- **估计工作量**: Short

## 依赖关系
- 任务 4 → 任务 6（先删除 storage.ts，再清理 index.ts 的 re-export）
- 任务 5 → 任务 6（先删除 global.ts，再清理 index.ts 的 re-export）
- 任务 1-8 → 任务 9（所有清理完成后执行验证）

## 风险
- **AdminService 可能被动态调用**: 缓解 — 全局搜索确认无 `AdminService::getInstance()` 或 `new AdminService()` 调用
- **JwtService 可能通过 ThinkPHP 容器自动注册**: 缓解 — 检查 `provider.php` 确认未注册，ThinkPHP Service 需显式注册才会 boot
- **migration.ts 的 migrateUnprefixedKeys 仍在 main.ts 中调用**: 缓解 — 保留 migration.ts 文件，仅清理 config/index.ts 中不必要的 re-export
- **删除运行时产物后首次请求可能稍慢**: 缓解 — 运行时缓存会自动重建，影响可忽略

## 验证
- [ ] 前端 `npm run build` 构建成功
- [ ] 前端 `npm run lint` 无新增错误
- [ ] 后端 PHP 语法检查通过
- [ ] 全局搜索确认已删除文件无残留引用
- [ ] Git diff 确认变更范围与计划一致

