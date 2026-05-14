/**
 * @文件: index.ts
 * @用途: composables 模块统一导出入口
 * @描述: 汇总导出所有布局状态管理 Hook（useSetting、useHeaderSetting、
 *        useLayoutDimensions、useAppearance、useDeviceDetection、useMenuState）
 *        以及相关类型定义，供布局组件和页面组件统一引用
 */
export { useSetting } from './useSetting'
export { useHeaderSetting } from './useHeaderSetting'
export { useLayoutDimensions } from './useLayoutDimensions'
export { useAppearance } from './useAppearance'
export { useDeviceDetection } from './useDeviceDetection'
export { useMenuState } from './useMenuState'
export type { LayoutType } from './useSetting'
export type { LayoutConfig } from './useLayoutDimensions'
export type { ThemeType, TabsType } from './useAppearance'
export type { DeviceType } from './useDeviceDetection'
