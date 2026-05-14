# Pages 目录代码优化方案

## Why

基于 `pages-code-review-plan.md` 的全面审查结果，`frontend/src/pages` 目录（27 个 Vue 文件，10 个功能模块）存在严重的样式重复（~600 行重复 CSS）、`any` 类型滥用、TableSetting 使用方式不统一、乐观更新策略不一致等问题。需分阶段系统性优化以提升代码质量和可维护性。

## What Changes

按优先级分三阶段执行，每阶段控制输出在 8K 上下文窗口内：

### 阶段一（高优先级）：样式提取 + 类型安全
- 提取全屏表格样式为全局 CSS 类，9 个页面统一引用
- 提取搜索卡片/表格容器样式为全局 CSS 类
- 修复 7 处 `any` 类型滥用（`catch (error: any)` → `unknown`，`Record<string, any>` → 精确类型）
- 修复 `h('a-switch')` 字符串标签名 → 导入组件变量

### 阶段二（中优先级）：模式统一 + 工具抽取
- 将 monitor/login、monitor/operation、dict 3 个页面从手动 TableSetting 迁移至 `usePageTable`
- 抽取 CSV 导出逻辑为 `useExport` composable，消除 3 处重复代码
- 统一乐观更新策略：status 切换全部改为先更新 UI 再调 API，失败回滚+提示
- 抽取 `escapeCsvField` 为公共工具函数
- 统一 z-index、弹窗宽度等魔术数字为 CSS 变量和常量
- 增加 768px 响应式断点处理

### 阶段三（低优先级）：性能优化 + 可维护性
- 减少不必要的全量刷新（增/删/改后局部更新 tableData）
- 部门树数据共享（provide/inject 或 composable 缓存）
- 注释质量规范化（移除空方法、统一标签格式）
- 为关键页面添加 `onErrorCaptured` 错误边界
- 修复 `ButtonFormModal.vue` 不安全的双重类型断言
- 修复 `dept/index.vue` 的 `h()` 事件名（Vue 2 风格 → Vue 3 风格）

## Impact

- Affected specs: 无（pages 模块独立优化，不涉及其他 spec）
- Affected code: `frontend/src/pages/` 下 27 个 Vue 文件 + 可能的新增 composables/样式文件

---

## ADDED Requirements

### Requirement: 全屏表格样式统一

系统 SHALL 在全局样式文件中定义 `.fullscreen-table` CSS 类和 `@media (max-width: 480px)` 响应式规则，所有列表页面移除重复的样式块，通过添加/移除 CSS 类控制全屏表格状态。

#### Scenario: 样式复用
- **WHEN** 任意列表页面触发全屏表格模式
- **THEN** 使用全局 `.fullscreen-table` 类
- **AND** 各页面不再包含独立的 fullscreen-table 样式块

### Requirement: 搜索卡片和表格容器样式统一

系统 SHALL 在全局样式文件中定义 `.search-card`、`.s-table-wrapper`、`.s-table-header`、`.table-header-container`、`.table-header-toolbar` 等公共样式类，各列表页面移除对应的重复样式块。

#### Scenario: 容器样式复用
- **WHEN** 任意列表页面渲染搜索区域和表格容器
- **THEN** 使用全局定义的公共样式类
- **AND** 各页面不再重复定义这些样式

### Requirement: 消除 any 类型滥用

系统 SHALL 将 `catch (error: any)` 替换为 `catch (error: unknown)` 并通过类型守卫处理；将 `Record<string, any>` 替换为精确的类型定义；将模板中的 `(e: any)` 替换为正确的 Event 类型。

#### Scenario: 类型安全的错误处理
- **WHEN** API 调用抛出异常
- **THEN** catch 块使用 `unknown` 类型接收错误
- **AND** 通过 `instanceof Error` 或类型守卫安全访问错误信息

### Requirement: h() 渲染函数使用导入组件

系统 SHALL 在 `dept/index.vue` 和 `menu/index.vue` 中将 `h('a-switch', ...)` 替换为 `h(Switch, ...)`，使用已导入的组件变量而非字符串标签名。

#### Scenario: 组件引用规范
- **WHEN** 表格列中使用 h() 渲染 Switch 组件
- **THEN** 使用导入的 `Switch` 组件变量
- **AND** 构建工具能正确解析组件依赖

### Requirement: TableSetting 使用方式统一

系统 SHALL 将 `monitor/login/index.vue`、`monitor/operation/index.vue`、`system/dict/index.vue` 中的手动 `useTableSetting` + `createTableSettingContext` 模式迁移至 `usePageTable` 封装方式。

#### Scenario: 统一 TableSetting
- **WHEN** 任意列表页面配置表格列设置
- **THEN** 使用 `usePageTable` composable
- **AND** 代码量从 ~30 行减少至 ~5 行

### Requirement: CSV 导出逻辑统一

系统 SHALL 创建 `useExport` composable，封装 BOM 头添加、Blob 创建、CSV 字段转义、下载触发等通用逻辑，供 `monitor/login`、`monitor/operation`、`system/user` 3 个页面复用。

#### Scenario: CSV 导出复用
- **WHEN** 任意页面触发 CSV 导出
- **THEN** 调用 `useExport` composable 的 `exportCSV` 方法
- **AND** 不再各自实现导出逻辑

### Requirement: 乐观更新策略统一

系统 SHALL 将所有状态切换操作（`handleStatusChange`）统一为乐观更新策略：先更新本地 UI 状态，再调用 API，API 失败时回滚状态并显示 `message.error` 错误提示。

#### Scenario: 状态切换成功
- **WHEN** 用户切换状态开关
- **THEN** UI 立即响应更新
- **AND** 后台调用 API 确认变更

#### Scenario: 状态切换失败
- **WHEN** API 调用失败
- **THEN** UI 回滚到原始状态
- **AND** 显示错误提示消息

### Requirement: 魔术数字统一

系统 SHALL 将 `z-index: 9999` 定义为 CSS 变量 `--z-fullscreen-table`，将弹窗宽度（600/700/800）定义为常量，将防抖延迟 300ms 统一使用全局配置。

#### Scenario: 层级管理
- **WHEN** 需要全屏表格层级
- **THEN** 引用 CSS 变量 `var(--z-fullscreen-table)`
- **AND** 不再硬编码 `9999`

### Requirement: 响应式断点完善

系统 SHALL 为列表页面增加 `768px` 和 `1024px` 断点的响应式处理，确保搜索表单在平板尺寸下正常换行展示。

#### Scenario: 平板适配
- **WHEN** 视口宽度 ≤ 768px
- **THEN** 搜索表单项换行展示
- **AND** 表格水平可滚动

### Requirement: 局部数据更新

系统 SHALL 优化列表页面的数据更新策略：新增成功后直接插入 `tableData` 头部，编辑成功后更新对应行，删除成功后移除对应行，仅在搜索/分页/排序变化时全量请求。

#### Scenario: 新增记录
- **WHEN** 用户新增一条记录并提交成功
- **THEN** 新记录直接插入表格数据头部
- **AND** 不触发全量列表刷新

### Requirement: 部门树数据共享

系统 SHALL 通过 provide/inject 或 composable 缓存机制共享部门树数据，避免 `dept/index.vue`、`DeptTree.vue`、`UserFormModal.vue` 重复获取。

#### Scenario: 部门树复用
- **WHEN** 用户页面和部门页面需要部门树数据
- **THEN** 优先使用缓存数据
- **AND** 减少重复 API 请求

### Requirement: 错误边界

系统 SHALL 为关键列表页面添加 `onErrorCaptured` 钩子，捕获子组件渲染错误，防止整个页面白屏。

#### Scenario: 子组件渲染错误
- **WHEN** 子组件渲染抛出异常
- **THEN** `onErrorCaptured` 捕获错误并显示降级 UI
- **AND** 页面其他部分正常渲染

---

## MODIFIED Requirements

无。

## REMOVED Requirements

无。此次优化不删除任何现有需求，仅提升实现质量。