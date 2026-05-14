# 布局目录代码审查优化方案

## Why

基于 `layouts-code-review-report.md` 的代码审查结果，`frontend/src/layouts` 目录（17 个源文件）存在性能反模式、类型安全缺陷、职责过重等 23 项问题，综合评分 6.7/10。需系统性优化以提升代码质量，降低维护成本。

## What Changes

按优先级分三阶段执行，每阶段控制输出在 8K 上下文窗口内：

### 阶段一（P0+P1）：关键修复
- 修复 `Menu/index.tsx` render 函数内 `reactive()` 性能反模式
- 为 `menuItems` 添加 `computed` 缓存
- 拆分 `useSetting.ts` 为多个职责单一的 composable
- 统一菜单可见性过滤逻辑（`LeftNav.vue` 与 `Header.vue`）
- 替换 `resolveComponent('router-link')` 为直接 import
- 修复 `UserMenu.vue` 注销失败静默吞错
- 消除 `SettingDrawer/index.vue`、`menuUtils.ts`、`Menu/index.tsx` 中的 `any` 类型

### 阶段二（P2）：质量提升
- 移除 `Sider.vue`/`LeftNav.vue` 无意义 computed 包装
- 提取布局联动逻辑为纯函数
- 使用 `InjectionKey` 约束 `provide/inject` 类型
- 统一 `Trigger.vue` emit 方式
- 消除魔术数字，引用 `layoutConfig` 常量
- 优化 View Transition API 类型断言
- 重命名 `useHeaderSetting` 内部变量
- 设备检测改用 `matchMedia`

### 阶段三（P3）：体验优化
- 删除 `TagsView/index.ts` 冗余文件
- 拆分 `useTabs.ts` 中的纯工具函数
- 为组件添加 `defineOptions` 命名
- 优化命名（`stuffWidth`、`sideLength`、`isLeftNotMobile`）
- 添加 `sessionStorage` 可用性检查
- `Header.vue` style 计算常量化

## Impact

- Affected specs: 无（布局模块独立，不涉及其他 spec）
- Affected code: `frontend/src/layouts/` 下全部 17 个源文件

---

## ADDED Requirements

### Requirement: Menu 组件性能优化

系统 SHALL 将 `Menu/index.tsx` 中的 `menuProps` 和 `menuItems` 从 render 函数提升为 `computed`，避免每次重渲染创建 reactive 代理和重新计算菜单项。

#### Scenario: 菜单渲染性能
- **WHEN** 组件因 props 或 state 变化重渲染
- **THEN** `menuProps` 作为 computed 缓存，仅依赖变化时重建
- **AND** `menuItems` 仅当 `props.menus` 变化时重新计算

### Requirement: useSetting 模块拆分

系统 SHALL 将 `useSetting.ts`（537 行）拆分为 `useLayoutDimensions`、`useAppearance`、`useDeviceDetection`、`useMenuState` 四个子模块，保留 `useSetting.ts` 作为聚合入口。

#### Scenario: 职责分离
- **WHEN** 开发者修改布局尺寸计算逻辑
- **THEN** 仅需修改 `useLayoutDimensions.ts`
- **AND** 其他模块（外观、设备检测、菜单状态）不受影响

### Requirement: 菜单可见性过滤一致

系统 SHALL 在 `LeftNav.vue` 中复用 `isMenuVisible()` 函数进行菜单过滤，确保与 `Header.vue` 行为一致。

#### Scenario: left 布局菜单过滤
- **WHEN** 菜单项 `visible: 0` 或 `status: 0`
- **THEN** 该菜单项在 left 布局一级导航中不可见
- **AND** 行为与顶部菜单过滤完全一致

### Requirement: 路由链接直接导入

系统 SHALL 在 `Menu/index.tsx` 中直接 import `RouterLink` from `vue-router`，替换 `resolveComponent('router-link')`。

#### Scenario: 路由组件解析
- **WHEN** 菜单项需要渲染为路由链接
- **THEN** 使用直接 import 的 `RouterLink` 组件
- **AND** 不依赖全局组件注册

### Requirement: 注销错误处理完善

系统 SHALL 在 `UserMenu.vue` 的注销逻辑中，API 失败时仍清除本地认证状态，避免"假登出"。

#### Scenario: 注销 API 失败
- **WHEN** 注销 API 调用失败（网络异常等）
- **THEN** 清除本地认证状态（Token、用户信息）
- **AND** 跳转到登录页

### Requirement: 消除 any 类型

系统 SHALL 将 `SettingDrawer/index.vue` 的 `setTheme` 参数类型改为 `ThemeType`，将 `asRouteMenuList` 参数改为 `unknown[]`，将 `Menu/index.tsx` 的路由 meta 访问改为类型扩展。

#### Scenario: 类型安全
- **WHEN** 调用 `setTheme`、`asRouteMenuList` 或访问路由 meta
- **THEN** 编译期类型检查生效
- **AND** 不存在 `any` 类型绕过

### Requirement: 移除无意义 computed

系统 SHALL 在 `Sider.vue` 和 `LeftNav.vue` 中直接使用 `sideMenus`/`menus` ref，移除仅解包 ref 的 computed 包装。

#### Scenario: 减少响应式开销
- **WHEN** 模板引用菜单数据
- **THEN** 直接使用 `sideMenus`/`menus` ref
- **AND** 无额外 computed 层

### Requirement: 布局联动逻辑提取

系统 SHALL 将 `SettingDrawer/index.vue` 中的 24 行 `if-else` 联动逻辑提取为纯函数，使用数据驱动方式。

#### Scenario: 布局切换
- **WHEN** 用户切换布局类型
- **THEN** `splitMenu` 和 `showBreadcrumb` 根据布局默认值表自动设置
- **AND** 逻辑位于设置管理层而非 UI 组件内

### Requirement: provide/inject 类型约束

系统 SHALL 使用 `InjectionKey` 定义 `openSetting` 的 provide/inject key，替换裸字符串。

#### Scenario: 类型安全注入
- **WHEN** 开发者使用 `provide`/`inject`
- **THEN** key 拼写错误在编译期被捕获
- **AND** 注入值的类型被正确推断

### Requirement: 魔术数字消除

系统 SHALL 将分散的魔术数字（208、240px、40px）替换为 `layoutConfig` 常量和 CSS 变量引用。

#### Scenario: 尺寸统一调整
- **WHEN** 需要调整侧边栏或触发器尺寸
- **THEN** 仅修改 `layoutConfig` 常量和对应的 CSS 变量
- **AND** 所有引用处自动生效

---

## MODIFIED Requirements

无。

## REMOVED Requirements

无。此次优化不删除任何现有需求，仅提升实现质量。