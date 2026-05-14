/**
 * @文件: useSetting.ts
 * @用途: 全局布局设置状态管理聚合入口
 * @描述: 作为布局设置模块的聚合入口，组合 useLayoutDimensions、useAppearance、
 *        useDeviceDetection、useMenuState 四个子模块，提供统一的对外 API。
 *        管理核心设置项（布局模式、功能开关、样式配置）的持久化和变更。
 *        使用 createSharedComposable 确保全局单例。
 * @核心逻辑:
 *   1. 使用 useStorage 持久化核心设置项到 localStorage
 *   2. 组合调用四个子模块：设备检测、菜单状态、主题外观、布局尺寸
 *   3. 通过 settingMap 统一管理设置项的 ref 和副作用函数
 *   4. 对外返回与原 useSetting 完全一致的 API 接口
 *   5. 子模块各自使用 createSharedComposable 保证单例
 */

import { useRouter } from 'vue-router'
import { useStorage, createSharedComposable } from '@vueuse/core'
import { AppConfig } from '@/config/app'
import { prefixedKey } from '@/constants/storage'
import { useDeviceDetection } from './useDeviceDetection'
import { useMenuState } from './useMenuState'
import { useAppearance } from './useAppearance'
import { useLayoutDimensions, layoutConfig } from './useLayoutDimensions'

// ==================== 类型定义 ====================

/** 布局模式类型 */
export type LayoutType = 'side' | 'top' | 'mix' | 'left'

// 以下类型从子模块导入并重新导出（由 index.ts 统一管理导出）
export type { ThemeType, TabsType } from './useAppearance'
export type { DeviceType } from './useDeviceDetection'
export type { LayoutConfig } from './useLayoutDimensions'

// ==================== 持久化状态定义 ====================
// 以下设置项由 useSetting 直接管理，通过 useStorage 持久化

/** 侧边栏是否展开 */
const sidebarOpened = useStorage(prefixedKey(AppConfig.sidebarOpenedKey), true)
/** 布局模式 */
const layout = useStorage<LayoutType>(prefixedKey(AppConfig.layoutKey), 'side')

/** 是否显示多标签页 */
const showMultiTabs = useStorage(prefixedKey(AppConfig.showMultiTabsKey), true)
/** 是否固定多标签页 */
const fixedMultiTabs = useStorage(prefixedKey(AppConfig.fixedMultiTabsKey), true)
/** 是否显示面包屑 */
const showBreadcrumb = useStorage(prefixedKey(AppConfig.showBreadcrumbKey), true)
/** 是否显示底部栏 */
const showFooter = useStorage(prefixedKey(AppConfig.showFooterKey), true)
/** 是否分割菜单（mix/left 布局下将一级菜单放顶部，子菜单放侧边栏） */
const splitMenu = useStorage(prefixedKey(AppConfig.splitMenuKey), false)

/** 标签页样式类型 */
const tabsType = useStorage<'smooth-tab' | 'smart-tab'>(
  prefixedKey(AppConfig.tabsTypeKey),
  'smart-tab'
)
/** 是否开启路由动画 */
const openAnimation = useStorage(prefixedKey(AppConfig.openAnimationKey), false)
/** 路由动画类型 */
const animation = useStorage(prefixedKey(AppConfig.animationKey), 'fade-slide')
/** 是否开启页面加载进度条 */
const openNProgress = useStorage(prefixedKey(AppConfig.nprogressKey), true)
/** 圆角大小（px） */
const borderRadius = useStorage(prefixedKey(AppConfig.borderRadiusKey), 4)
/** 设置入口位置（header=头部按钮, fixed=右侧固定把手） */
const setPosition = useStorage<'header' | 'fixed'>(prefixedKey(AppConfig.setPositionKey), 'header')

// ==================== 设置项键名类型 ====================

/** 设置项键名联合类型，用于 changeSetting 方法的类型约束 */
type SettingKey =
  | 'sidebarOpened'
  | 'theme'
  | 'primaryColor'
  | 'layout'
  | 'showMultiTabs'
  | 'fixedMultiTabs'
  | 'showBreadcrumb'
  | 'showFooter'
  | 'splitMenu'
  | 'tabsType'
  | 'openAnimation'
  | 'animation'
  | 'openNProgress'
  | 'borderRadius'
  | 'setPosition'

// ==================== useSetting 聚合入口 ====================

/**
 * useSetting - 全局布局设置状态管理 Hook
 * @returns 所有布局设置的状态和操作方法
 *
 * 使用 createSharedComposable 确保全局单例，
 * 多个组件调用 useSetting() 共享同一份状态。
 */
export const useSetting = createSharedComposable(() => {
  const router = useRouter()

  // 组合子模块
  const deviceDetection = useDeviceDetection()
  const menuState = useMenuState()
  const appearance = useAppearance()
  const layoutDimensions = useLayoutDimensions({
    sidebarOpened,
    isMobile: deviceDetection.isMobile,
    layout,
    sideMenuCount: menuState.sideMenuCount,
    fixedMultiTabs,
    splitMenu,
    navTheme: appearance.navTheme,
    showLeftNav: menuState.showLeftNav
  })

  /** 设置项映射表 */
  const settingMap: Record<SettingKey, { ref: any; apply?: (val: any) => void }> = {
    sidebarOpened: { ref: sidebarOpened },
    theme: { ref: appearance.theme, apply: appearance.applyTheme },
    primaryColor: { ref: appearance.primaryColor, apply: appearance.applyPrimaryColor },
    layout: { ref: layout },
    showMultiTabs: { ref: showMultiTabs },
    fixedMultiTabs: { ref: fixedMultiTabs },
    showBreadcrumb: { ref: showBreadcrumb },
    showFooter: { ref: showFooter },
    splitMenu: { ref: splitMenu },
    tabsType: { ref: tabsType },
    openAnimation: { ref: openAnimation },
    animation: { ref: animation },
    openNProgress: { ref: openNProgress },
    borderRadius: { ref: borderRadius },
    setPosition: { ref: setPosition }
  }

  // ==================== 设置变更方法 ====================

  /**
   * 通用设置变更方法
   * @param key - 设置项键名
   * @param value - 新值
   * @description 通过 settingMap 查找对应的 ref 和副作用函数，
   *              更新值并执行副作用（如 applyTheme、applyPrimaryColor）
   */
  const changeSetting = (key: SettingKey, value: any) => {
    if (key === 'theme' && appearance.theme.value !== 'realDark') {
      appearance.prevTheme.value = appearance.theme.value
    }
    const entry = settingMap[key]
    if (entry) {
      entry.ref.value = value
      entry.apply?.(value)
    }
  }

  /** 切换侧边栏展开/折叠状态 */
  const toggleSidebar = () => {
    sidebarOpened.value = !sidebarOpened.value
  }

  /**
   * 切换布局模式
   * @param newLayout - 新的布局模式
   */
  const changeLayout = (newLayout: LayoutType) => {
    layout.value = newLayout
  }

  /**
   * 路由跳转（从菜单点击触发）
   * @param data - 菜单点击数据，包含 key 或 path 属性
   * @description 仅处理以 '/' 开头的路径，执行 router.push 跳转
   */
  const routerTo = (data: Record<string, any>) => {
    const path = data.key || data.path
    if (path && String(path).startsWith('/')) {
      router.push({ path: String(path) })
    }
  }

  // ==================== 返回聚合 API ====================

  return {
    // 菜单相关（来自 useMenuState）
    menus: menuState.menus,
    sideMenus: menuState.sideMenus,
    sideMenuCount: menuState.sideMenuCount,
    isMixLayout: menuState.isMixLayout,
    showLeftNav: menuState.showLeftNav,

    // 设备检测相关（来自 useDeviceDetection）
    isMobile: deviceDetection.isMobile,
    isTablet: deviceDetection.isTablet,
    device: deviceDetection.device,

    // 主题外观相关（来自 useAppearance）
    theme: appearance.theme,
    prevTheme: appearance.prevTheme,
    primaryColor: appearance.primaryColor,
    navTheme: appearance.navTheme,
    headTheme: appearance.headTheme,
    changeTheme: appearance.changeTheme,
    changePrimaryColor: appearance.changePrimaryColor,

    // 布局尺寸相关（来自 useLayoutDimensions）
    sideWidth: layoutDimensions.sideWidth,
    sidebarOccupiedWidth: layoutDimensions.sidebarOccupiedWidth,
    reduceWidth: layoutDimensions.reduceWidth,
    tabsWidth: layoutDimensions.tabsWidth,
    siderLeft: layoutDimensions.siderLeft,
    showSidebar: layoutDimensions.showSidebar,
    showSideTrigger: layoutDimensions.showSideTrigger,

    // 核心设置项（useSetting 自身管理）
    sidebarOpened,
    layout,
    showMultiTabs,
    fixedMultiTabs,
    showBreadcrumb,
    showFooter,
    splitMenu,
    tabsType,
    openAnimation,
    animation,
    openNProgress,
    borderRadius,
    setPosition,

    // 设置变更方法
    changeSetting,
    toggleSidebar,
    changeLayout,
    routerTo,

    // 布局常量
    layoutConfig
  }
})
