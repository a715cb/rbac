# 布局目录代码审查优化 - 任务列表

## 阶段一：P0+P1 关键修复

- [x] Task 1: 修复 `Menu/index.tsx` 性能问题
  - [x] 1.1 将 render 函数内 `reactive(menuProps)` 提升为 setup 层 `computed`
  - [x] 1.2 将 `menuItems = props.menus.map(...)` 改为 `computed` 缓存
  - [x] 1.3 替换 `resolveComponent('router-link')` 为直接 `import { RouterLink } from 'vue-router'`
  - [x] 1.4 消除 `activateMenu` 中的 `any` 类型断言，使用 `vue-router` 的 `RouteMeta` 类型扩展

- [x] Task 2: 拆分 `useSetting.ts` 模块
  - [x] 2.1 创建 `useLayoutDimensions.ts` — 迁移 sideWidth、stuffWidth、reduceWidth、tabsWidth、siderLeft 相关代码
  - [x] 2.2 创建 `useAppearance.ts` — 迁移 theme、primaryColor、navTheme、headTheme 及 DOM 应用逻辑
  - [x] 2.3 创建 `useDeviceDetection.ts` — 迁移 isMobile、isTablet、device、resize 监听
  - [x] 2.4 创建 `useMenuState.ts` — 迁移 menus、sideMenus、sideLength、showSidebar
  - [x] 2.5 重构 `useSetting.ts` 为聚合入口，组合上述子模块并保持对外 API 不变
  - [x] 2.6 更新 `composables/index.ts` 导出新模块
  - [x] 2.7 更新所有消费方引用（DefaultLayout.vue、useHeaderSetting.ts、各子组件）

- [x] Task 3: 统一菜单可见性过滤
  - [x] 3.1 在 `LeftNav.vue` 中引入 `isMenuVisible`，替换仅检查 `hidden` 的过滤逻辑

- [x] Task 4: 修复 `UserMenu.vue` 注销错误处理
  - [x] 4.1 在 `catch` 块中添加本地状态清除逻辑
  - [x] 4.2 生产环境也输出 console.error 便于排查

- [x] Task 5: 消除 `any` 类型
  - [x] 5.1 `SettingDrawer/index.vue`：`setTheme` 参数改为 `ThemeType`
  - [x] 5.2 `menuUtils.ts`：`asRouteMenuList` 参数改为 `unknown[]`，使用类型谓词

## 阶段二：P2 质量提升

- [x] Task 6: 移除无意义 computed
  - [x] 6.1 `Sider.vue`：直接使用 `sideMenus` ref
  - [x] 6.2 `LeftNav.vue`：已在上阶段移除 `menuList` computed

- [x] Task 7: 提取布局联动逻辑
  - [x] 7.1 在 `useSetting.ts` 中创建 `layoutDefaults` 映射表
  - [x] 7.2 添加 `watch(layout, ...)` 自动应用默认值
  - [x] 7.3 清理 `SettingDrawer/index.vue` 中的 24 行 `if-else`

- [x] Task 8: `provide/inject` 类型约束
  - [x] 8.1 创建 `symbols.ts`，定义 `OPEN_SETTING_KEY: InjectionKey<() => void>`
  - [x] 8.2 更新 `DefaultLayout.vue` 的 `provide`
  - [x] 8.3 更新 `UserMenu.vue` 的 `inject`

- [x] Task 9: 统一 `Trigger.vue` emit 方式
  - [x] 9.1 将模板中 `$emit('click')` 替换为调用 `handleClick` 方法

- [x] Task 10: 消除魔术数字
  - [x] 10.1 `Sidebar/index.vue`：侧边栏宽度 `208` 引用 `layoutConfig.sidebarWidth`
  - [x] 10.2 `SettingDrawer/index.vue`：`top: 240px` 提取为 CSS 变量
  - [x] 10.3 `Trigger.vue`：`40px` 提取为 `--layout-trigger-size` CSS 变量

- [x] Task 11: 优化 View Transition API 类型断言
  - [x] 11.1 提取 `supportsViewTransition()` 特性检测函数

- [x] Task 12: 重命名 `useHeaderSetting` 内部变量
  - [x] 12.1 解构时重命名为 `globalShowBreadcrumb`，避免影子遮蔽

- [x] Task 13: 设备检测改用 `matchMedia`
  - [x] 13.1 使用 `window.matchMedia('(max-width: 767px)')` 替代 `innerWidth` 判断

## 阶段三：P3 体验优化

- [x] Task 14: 删除 `TagsView/index.ts` 冗余文件
  - [x] 14.1 删除文件，确保 `components/index.ts` 已直接引用 `.vue`

- [x] Task 15: 拆分 `useTabs.ts` 工具函数
  - [x] 15.1 创建 `tabsUtils.ts`，迁移 `clearTabsStorage`、`isTabAllowed`、`getSafeStorage`

- [x] Task 16: 添加 `defineOptions` 组件命名
  - [x] 16.1 `DefaultLayout.vue`：`defineOptions({ name: 'DefaultLayout' })`
  - [x] 16.2 `Sidebar/index.vue`：`defineOptions({ name: 'LayoutSidebar' })`
  - [x] 16.3 `TagsView/index.vue`：`defineOptions({ name: 'LayoutTagsView' })`

- [x] Task 17: 优化命名
  - [x] 17.1 `stuffWidth` → `sidebarOccupiedWidth`
  - [x] 17.2 `sideLength` → `sideMenuCount`
  - [x] 17.3 `isLeftNotMobile` → `showLeftNav`

- [x] Task 18: `sessionStorage` 可用性检查
  - [x] 18.1 `tabsUtils.ts`：封装 `getSafeStorage()` 回退到内存存储

- [x] Task 19: `Header.vue` style 计算常量化
  - [x] 19.1 `height` 使用 `layoutConfig.headerHeight` 常量替代 computed

# 任务依赖关系

- Task 2（useSetting 拆分）是阶段一的核心重构，**必须先完成**
- Task 7（布局联动提取）依赖 Task 2 完成后的 useSetting 结构
- Task 10（魔术数字）依赖 Task 2 导出的 layoutConfig 常量
- Task 17（命名优化）依赖 Task 2 完成后的模块结构
- 阶段一的其他 Task（1、3、4、5）可与 Task 2 **并行**执行
- 阶段二的所有 Task 在阶段一完成后可**并行**执行
- 阶段三的所有 Task 在阶段二完成后可**并行**执行