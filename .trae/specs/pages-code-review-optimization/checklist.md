# Checklist

## 阶段一验证

- [x] 全局 `common-table.less` 文件已创建，包含 `.fullscreen-table` 类定义
- [x] 全局 `common-table.less` 文件包含 `.search-card`、`.s-table-wrapper`、`.s-table-header`、`.table-header-container`、`.table-header-toolbar` 公共样式类
- [x] 全局样式入口已导入 `common-table.less`
- [x] 9 个列表页面的独立 `.fullscreen-table` 样式块已移除
- [x] 9 个列表页面的独立容器样式块（`.search-card`、`.s-table-wrapper` 等）已移除
- [x] 9 个列表页面模板中使用全局 CSS 类替换原局部类名
- [x] `catch (error: any)` 已全部替换为 `catch (error: unknown)` + 类型守卫
- [x] `Record<string, any>` 已全部替换为精确类型定义
- [x] 模板中的 `(e: any)` 已替换为正确的 Event 类型
- [x] `(searchInput.value as any)` 已替换为正确的 `HTMLInputElement` 类型
- [x] `h('a-switch', ...)` 已替换为 `h(Switch, ...)`
- [x] TypeScript 编译无新增类型错误
- [x] 全屏表格切换功能正常
- [x] 各页面布局显示正常

## 阶段二验证

- [x] `monitor/login/index.vue` 已从手动 TableSetting 迁移至 `usePageTable`
- [x] `monitor/operation/index.vue` 已从手动 TableSetting 迁移至 `usePageTable`
- [x] `system/dict/index.vue` 已从手动 TableSetting 迁移至 `usePageTable`
- [x] `system/dict/index.vue` 空的 `onSearch` 方法已处理
- [x] 3 个迁移页面表格列设置功能正常
- [x] `useExport` composable 已创建，包含 `escapeCsvField`、CSV 生成、下载触发等通用逻辑
- [x] `system/user/index.vue` 已使用 `useExport`，本地重复代码已移除
- [x] `monitor/login/index.vue` 已使用 `useExport`
- [x] `monitor/operation/index.vue` 已使用 `useExport`
- [x] CSV 导出功能正常，文件内容正确
- [x] `system/role/index.vue` 状态切换策略已改为乐观更新（失败回滚 + 提示）
- [x] `system/dept/index.vue` 状态切换策略已改为乐观更新
- [x] `permission/button/index.vue` 状态切换策略已改为乐观更新
- [x] `permission/api/index.vue` 乐观更新失败提示已添加
- [x] 状态切换 UI 即时响应，失败时正确回滚
- [x] CSS 变量 `--z-fullscreen-table` 已定义
- [x] 9 个页面硬编码 `z-index: 9999` 已替换为 `var(--z-fullscreen-table)`
- [x] 弹窗宽度常量已定义并使用
- [x] 768px 和 1024px 响应式断点已添加
- [x] 平板尺寸下布局正常

## 阶段三验证

- [x] `permission/api/index.vue` 增删改后执行局部数据更新
- [x] `permission/button/index.vue` 增删改后执行局部数据更新
- [x] `system/dept/index.vue` 增删改后执行局部数据更新（树形递归）
- [x] `system/dict/index.vue` 增删改后执行局部数据更新
- [x] `system/role/index.vue` 增删改后执行局部数据更新
- [x] `system/user/index.vue` 增删改后执行局部数据更新
- [x] 增删改操作后不触发全量网络请求，数据正确
- [x] 部门树缓存机制已实现（useDeptTree 模块级缓存）
- [x] `system/dept/index.vue` 使用 `getDeptList` API，无需共享
- [x] `DeptTree.vue` 使用共享 `useDeptTree`
- [x] `UserFormModal.vue` 使用共享 `useDeptTree`
- [x] 进入 system 模块时部门树仅请求一次
- [x] `system/dict/index.vue` 不存在空 onSearch 方法
- [x] `system/dept/index.vue` 注释标签已规范化
- [x] `system/role/index.vue` 过长注释已精简
- [x] `login/index.vue` 占位方法已移除
- [x] 各页面注释符合 comment-standards.md 规范
- [x] `permission/api/index.vue` 已添加/收紧错误边界
- [x] `system/user/index.vue` 已添加/收紧错误边界
- [x] `ButtonFormModal.vue` 不安全类型断言已修复
- [x] `system/dept/index.vue` `onUpdate:checked` 已改为 `onChange`
- [x] TypeScript 编译无新增类型错误