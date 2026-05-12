/**
 * @文件: index.ts
 * @用途: composables 模块统一导出入口
 * @描述: 汇总导出 useSetting 和 useHeaderSetting 两个布局状态管理 Hook，
 *        以及相关类型定义，供布局组件和页面组件统一引用
 */
export { useSetting } from './useSetting'
export { useHeaderSetting } from './useHeaderSetting'
export type { LayoutType, ThemeType, TabsType, DeviceType, LayoutConfig } from './useSetting'
