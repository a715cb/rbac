/**
 * @文件: index.ts
 * @用途: 布局子组件统一导出入口
 * @描述: 汇总导出所有布局子组件，包括 Header、Sidebar、Footer、TagsView 和 SettingDrawer，
 *        外部模块（如 DefaultLayout）从此处导入即可使用所有布局组件
 */
export { default as LayoutHeader } from './Header.vue'
export { default as LayoutSidebar } from './Sidebar/index.vue'
export { default as LayoutFooter } from './Footer.vue'
export { default as LayoutTabs } from './TagsView/index.vue'
export { default as SettingDrawer } from './SettingDrawer/index.vue'
