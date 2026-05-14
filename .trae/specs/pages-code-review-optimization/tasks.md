# Tasks

## 阶段一：样式提取 + 类型安全（高优先级）

- [x] Task 1: 提取全屏表格样式为全局 CSS 类
  - [x] 1.1 在 `frontend/src/styles/global/` 下创建 `common-table.less`，定义 `.fullscreen-table` 类和 `@media (max-width: 480px)` 规则
  - [x] 1.2 在全局样式入口 `index.less` 中导入 `common-table.less`
  - [x] 1.3 修改 9 个页面（monitor/login、monitor/operation、permission/api、permission/button、permission/menu、system/dept、system/dict、system/role、system/user），移除各自的 fullscreen-table 样式块，模板中直接使用全局 CSS 类
  - [x] 1.4 验证：检查 9 个页面的样式块是否已移除，全屏切换功能正常

- [x] Task 2: 提取搜索卡片/表格容器样式为全局 CSS 类
  - [x] 2.1 在 `common-table.less` 中追加 `.search-card`、`.s-table-wrapper`、`.s-table-header`、`.table-header-container`、`.table-header-toolbar`、`.table-header__toolbar-desktop` 公共样式
  - [x] 2.2 修改 9 个页面，移除各自的容器样式块，使用全局 CSS 类
  - [x] 2.3 验证：检查 9 个页面的容器样式是否已移除，布局正常

- [x] Task 3: 修复 any 类型滥用
  - [x] 3.1 修改 `monitor/login/index.vue` L420：`catch (error: any)` → `catch (error: unknown)` + 类型守卫
  - [x] 3.2 修改 `monitor/operation/index.vue` L459、L487：同上
  - [x] 3.3 修改 `monitor/operation/index.vue` L358：`(u: any)` → 精确类型
  - [x] 3.4 修改 `system/dept/index.vue` L324：`Record<string, any>` → 精确类型
  - [x] 3.5 修改 `permission/menu/index.vue` L265：`Record<string, any>` → 精确类型
  - [x] 3.6 修改 `permission/button/index.vue` L226：`Record<string, any>` → 精确类型
  - [x] 3.7 修改 `permission/menu/index.vue` L63、`system/dept/index.vue` L70：`(e: any)` → 正确的 Event 类型
  - [x] 3.8 修改 `permission/menu/index.vue` L272、`system/dept/index.vue` L330：`(searchInput.value as any)` → 正确的 `HTMLInputElement` 类型
  - [x] 3.9 验证：TypeScript 编译无新增类型错误

- [x] Task 4: 修复 h() 渲染函数使用字符串标签名
  - [x] 4.1 修改 `system/dept/index.vue`：确认 `Switch` 已导入，将 `h('a-switch', ...)` 替换为 `h(Switch, ...)`
  - [x] 4.2 menu 页面未使用 `h('a-switch')`（状态渲染为 span 文本），无需修改
  - [x] 4.3 验证：构建不报错，Switch 组件渲染正常

## 阶段二：模式统一 + 工具抽取（中优先级）

- [x] Task 5: 统一 TableSetting 使用方式
  - [x] 5.1 分析 `usePageTable` 的接口，确认 monitor/login、monitor/operation、dict 三个页面的表格列配置兼容
  - [x] 5.2 修改 `monitor/login/index.vue`：替换手动 TableSetting 为 `usePageTable`
  - [x] 5.3 修改 `monitor/operation/index.vue`：同上
  - [x] 5.4 修改 `system/dict/index.vue`：同上，并处理空的 `onSearch` 方法
  - [x] 5.5 验证：3 个页面表格列设置功能正常

- [x] Task 6: 抽取 CSV 导出逻辑
  - [x] 6.1 在 `frontend/src/composables/` 下创建 `useExport.ts`，封装 `escapeCsvField`、`downloadCsv` 等通用逻辑
  - [x] 6.2 修改 `system/user/index.vue`：使用 `useExport`，移除本地 `escapeCsvField` 和导出逻辑
  - [x] 6.3 修改 `monitor/login/index.vue`：使用 `useExport`，替换本地 CSV 拼接逻辑
  - [x] 6.4 修改 `monitor/operation/index.vue`：同上
  - [x] 6.5 验证：3 个页面 CSV 导出功能正常，文件内容正确

- [x] Task 7: 统一乐观更新策略
  - [x] 7.1 修改 `system/role/index.vue`：悲观更新 → 乐观更新，失败回滚 + `message.error`
  - [x] 7.2 修改 `system/dept/index.vue`：同上
  - [x] 7.3 修改 `permission/button/index.vue`：同上
  - [x] 7.4 修改 `permission/api/index.vue`：乐观更新但失败无提示 → 添加 `message.error`
  - [x] 7.5 验证：各页面状态切换 UI 即时响应，失败时正确回滚并提示

- [x] Task 8: 统一魔术数字
  - [x] 8.1 在全局 CSS 变量中定义 `--z-fullscreen-table: 9999`
  - [x] 8.2 修改 9 个页面：将硬编码 `z-index: 9999` 替换为 `var(--z-fullscreen-table)`
  - [x] 8.3 弹窗宽度常量已通过 CSS 变量统一
  - [x] 8.4 验证：全屏表格层级正常

- [x] Task 9: 增加响应式断点
  - [x] 9.1 在 `common-table.less` 中添加 `@media (max-width: 768px)` 和 `@media (max-width: 1024px)` 规则
  - [x] 9.2 为搜索表单区域添加平板尺寸下的换行处理
  - [x] 9.3 验证：768px 和 1024px 视口下布局正常

## 阶段三：性能优化 + 可维护性（低优先级）

- [x] Task 10: 局部数据更新优化
  - [x] 10.1 修改 `permission/api/index.vue`：新增/编辑/删除后局部更新 tableData
  - [x] 10.2 修改 `permission/button/index.vue`：同上
  - [x] 10.3 修改 `system/dept/index.vue`：同上（树形数据递归更新）
  - [x] 10.4 修改 `system/dict/index.vue`：同上
  - [x] 10.5 修改 `system/role/index.vue`：同上
  - [x] 10.6 修改 `system/user/index.vue`：同上
  - [x] 10.7 验证：增删改操作后列表不触发全量网络请求，数据正确

- [x] Task 11: 部门树数据共享
  - [x] 11.1 `useDeptTree` composable 已有模块级缓存，直接复用
  - [x] 11.2 `system/dept/index.vue` 使用 `getDeptList` API（非 `getDeptTree`），无需共享
  - [x] 11.3 修改 `system/user/components/DeptTree.vue`：使用 `useDeptTree` 替代本地 `fetchDeptTree`
  - [x] 11.4 修改 `system/user/components/UserFormModal.vue`：使用 `useDeptTree` 替代本地 `fetchDepts`
  - [x] 11.5 验证：进入 system 模块时部门树仅请求一次，各组件正常获取

- [x] Task 12: 注释质量规范化
  - [x] 12.1 `system/dict/index.vue`：不存在空 `onSearch` 方法，无需修改
  - [x] 12.2 `system/dept/index.vue`：`@主要组件` 非标准标签已合并到 `@描述` 中
  - [x] 12.3 `system/role/index.vue`：`dataScopeDict` 注释已精简
  - [x] 12.4 `login/index.vue`：`handleForgotPassword` 和 `handleRegister` 占位实现已移除
  - [x] 12.5 验证：各页面注释符合 comment-standards.md 规范

- [x] Task 13: 错误边界与类型安全修复
  - [x] 13.1 为 `permission/api/index.vue` 添加 `onErrorCaptured` 错误边界（已存在，类型已收紧）
  - [x] 13.2 为 `system/user/index.vue` 添加 `onErrorCaptured` 错误边界（已存在，类型已收紧）
  - [x] 13.3 `ButtonFormModal.vue` 类型已修复：`menu_id` 已为 `number | undefined`，无双断言
  - [x] 13.4 `system/dept/index.vue`：`onUpdate:checked` → `onChange`（已修复）
  - [x] 13.5 验证：类型断言安全，事件名正确，错误边界生效