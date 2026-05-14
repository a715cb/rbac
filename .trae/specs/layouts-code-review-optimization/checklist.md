# 布局目录代码审查优化 - 验证清单

## 阶段一验证

- [x] Menu 组件性能优化
  - [x] `Menu/index.tsx` 中 `menuProps` 已改为 setup 层 `computed`，无 render 函数内 `reactive()` 调用
  - [x] `menuItems` 已改为 `computed` 缓存，依赖 `props.menus`
  - [x] `RouterLink` 已从 `vue-router` 直接 import，无 `resolveComponent('router-link')`
  - [x] 路由 meta 类型通过 `vue-router` 模块扩展定义（`router.d.ts`），无 `any` 断言

- [x] useSetting 模块拆分
  - [x] `useLayoutDimensions.ts` 已创建，包含 sidebarOccupiedWidth、reduceWidth、tabsWidth、siderLeft
  - [x] `useAppearance.ts` 已创建，包含 theme、primaryColor、navTheme、headTheme、DOM 应用
  - [x] `useDeviceDetection.ts` 已创建，包含 isMobile、isTablet、device、resize
  - [x] `useMenuState.ts` 已创建，包含 menus、sideMenus、sideMenuCount、showSidebar
  - [x] `useSetting.ts` 作为聚合入口（238行），组合子模块，对外 API 不变
  - [x] `composables/index.ts` 已更新导出
  - [x] 所有消费方引用正常工作

- [x] 菜单可见性过滤一致
  - [x] `LeftNav.vue` 使用 `isMenuVisible()` 过滤菜单列表

- [x] 注销错误处理完善
  - [x] `UserMenu.vue` catch 块中清除本地认证状态（Token/用户信息/菜单缓存/Tabs/动态路由）
  - [x] 生产环境也输出错误日志

- [x] any 类型消除
  - [x] `SettingDrawer/index.vue` 的 `setTheme` 参数为 `ThemeType`
  - [x] `menuUtils.ts` 的 `asRouteMenuList` 参数为 `unknown[]`，使用类型谓词

## 阶段二验证

- [x] 无意义 computed 移除
  - [x] `Sider.vue` 模板直接使用 `sideMenus` ref
  - [x] `LeftNav.vue` 已移除 `menuList` computed，模板使用 `visibleMenuList`

- [x] 布局联动逻辑提取
  - [x] `useSetting.ts` 中存在 `layoutDefaults` 映射表
  - [x] `watch(layout, ...)` 自动应用布局默认值
  - [x] `SettingDrawer/index.vue` 中无 24 行 `if-else` 联动代码

- [x] provide/inject 类型约束
  - [x] `symbols.ts` 已创建，定义 `OPEN_SETTING_KEY: InjectionKey<() => void>`
  - [x] `DefaultLayout.vue` 使用 `OPEN_SETTING_KEY` provide
  - [x] `UserMenu.vue` 使用 `OPEN_SETTING_KEY` inject

- [x] Trigger.vue emit 统一
  - [x] 模板中无 `$emit('click')`，使用 `@click="handleClick"` 方法

- [x] 魔术数字消除
  - [x] `Sidebar/index.vue` 抽屉宽度引用 `layoutConfig.sidebarWidth`
  - [x] `SettingDrawer/index.vue` 使用 CSS 变量替代 `240px`
  - [x] `Trigger.vue` 使用 `--layout-trigger-size` CSS 变量替代 `40px`

- [x] View Transition API 类型优化
  - [x] `UserMenu.vue` 存在 `supportsViewTransition()` 函数

- [x] useHeaderSetting 变量重命名
  - [x] 解构重命名为 `globalShowBreadcrumb`，无同名影子遮蔽

- [x] 设备检测 matchMedia
  - [x] `useDeviceDetection.ts` 使用 `matchMedia('(max-width: 767px)')`

## 阶段三验证

- [x] TagsView/index.ts 删除
  - [x] 文件已删除
  - [x] `components/index.ts` 已直接引用 `.vue` 文件，无编译错误

- [x] useTabs.ts 工具函数拆分
  - [x] `tabsUtils.ts` 已创建
  - [x] `clearTabsStorage`、`isTabAllowed`、`getSafeStorage` 已迁移

- [x] defineOptions 组件命名
  - [x] `DefaultLayout.vue` 包含 `defineOptions({ name: 'DefaultLayout' })`
  - [x] `Sidebar/index.vue` 包含 `defineOptions({ name: 'LayoutSidebar' })`
  - [x] `TagsView/index.vue` 包含 `defineOptions({ name: 'LayoutTagsView' })`

- [x] 命名优化
  - [x] `stuffWidth` 已重命名为 `sidebarOccupiedWidth`（涉及3个文件9处）
  - [x] `sideLength` 已重命名为 `sideMenuCount`（涉及3个文件9处）
  - [x] `isLeftNotMobile` 已重命名为 `showLeftNav`（涉及4个文件10处）
  - [x] 所有引用处已同步更新

- [x] sessionStorage 可用性检查
  - [x] `tabsUtils.ts` 存在 `getSafeStorage()` 回退机制

- [x] Header.vue style 常量化
  - [x] `headerStyle` 中 `height` 使用 `layoutConfig.headerHeight` 常量

## 集成验证

- [x] TypeScript 类型检查通过（`npx vue-tsc --noEmit`，exit code 0）
- [x] Vite 生产构建通过（exit code 0，3480 modules，40.73s）
- [x] 所有 19 项优化任务完成