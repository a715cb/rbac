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

---

## 任务列表

### 任务 1: 删除后端一次性诊断脚本
- **文件**: 
  - `backend/check_user_dept_consistency.php`
  - `backend/test_status_api.php`
- **描述**: 删除多余的用户-部门一致性校验脚本和角色状态 API 测试脚本（含硬编码凭据）
- **验收标准**: 两个文件已删除

### 任务 2: 删除后端未使用的 AdminService 死代码
- **文件**: `backend/app/admin/service/AdminService.php`
- **描述**: `AdminService` 无任何控制器通过 `AdminService::getInstance()` 调用，属于冗余中间层
- **验收标准**: 文件已删除，全局搜索无 AdminService 引用

### 任务 3: 删除后端未使用的 JwtService 死代码
- **文件**: `backend/app/service/JwtService.php`
- **描述**: `JwtService` 未在 provider.php 注册，无代码通过容器解析 `jwt.token`，且密钥验证与 JwtToken 重复
- **验收标准**: 文件已删除

### 任务 4: 删除前端未使用的 constants/storage.ts
- **文件**: `frontend/src/constants/storage.ts`
- **描述**: `prefixedKey()` 无任何导入使用，StorageManager 已内置前缀逻辑
- **验收标准**: 文件已删除

### 任务 5: 删除前端未使用的 types/global.ts
- **文件**: `frontend/src/types/global.ts`
- **描述**: `Option` 接口无任何导入使用，项目使用 DictOption
- **验收标准**: 文件已删除

### 任务 6: 清理前端 config/index.ts 和 types/index.ts 死导出
- **文件**: 
  - `frontend/src/config/index.ts` — 移除 `prefixedKey` re-export 行
  - `frontend/src/types/index.ts` — 移除 `Option` re-export 行
- **描述**: 清理对已删除模块的 re-export 引用
- **验收标准**: config/index.ts 和 types/index.ts 编译无误

### 任务 7: 清理后端运行时产物
- **文件**: 
  - `backend/runtime/log/*.log`（7 个文件）
  - `backend/runtime/cache/*.cache`（4 个文件）
  - `backend/.phpunit.result.cache`
- **描述**: 删除磁盘上残留的日志、缓存和 PHPUnit 缓存文件
- **验收标准**: runtime 目录仅保留空目录结构

### 任务 8: 清理前端构建产物
- **文件**: `frontend/dist/`（整个目录）
- **描述**: 删除 Vite 构建输出
- **验收标准**: dist 目录已删除

### 任务 9: 验证项目完整性
- **描述**: 执行前端构建 + lint + 后端语法检查
- **验收标准**: 
  - 前端 `npm run build` 成功
  - 前端 `npm run lint` 无新增错误
  - 后端 PHP 语法检查通过

---

## 依赖关系
- 任务 4 → 任务 6（先删除 storage.ts，再清理 re-export）
- 任务 5 → 任务 6（先删除 global.ts，再清理 re-export）
- 任务 1-8 → 任务 9（所有清理完成后执行验证）

## 风险
- **AdminService/JwtService 可能被动态调用**: 已全局搜索确认无引用
- **migration.ts 的 migrateUnprefixedKeys 仍在 main.ts 中调用**: 保留 migration.ts，仅清理 re-export
- **运行时产物自动重建**: 清理后首次请求缓存自动重建，影响可忽略

## 验证清单
- [ ] 前端 `npm run build` 构建成功
- [ ] 前端 `npm run lint` 无新增错误
- [ ] 后端 PHP 语法检查通过
- [ ] 全局搜索确认已删除文件无残留引用