/**
 * @文件: useLayoutDimensions.ts
 * @用途: 布局尺寸计算 Hook
 * @描述: 根据布局模式、设备类型和侧边栏状态，计算各类布局尺寸参数。
 *        包括侧边栏宽度、内容区占用宽度、标签页宽度、侧边栏偏移等。
 *        本模块为纯计算模块，不管理任何状态，所有状态通过 deps 参数注入。
 * @核心逻辑:
 *   1. sideWidth: 根据展开/折叠和布局模式计算侧边栏宽度
 *   2. sidebarOccupiedWidth: 计算侧边栏在文档流中占用的总宽度
 *   3. reduceWidth: 计算头部和标签页需要扣除的宽度
 *   4. tabsWidth: 固定标签页时使用 calc 扣除侧边栏宽度
 *   5. siderLeft: left 布局下侧边栏需要偏移 leftNavWidth
 *   6. showSidebar/showSideTrigger: 控制侧边栏和折叠触发器的显示
 */

import { computed, type Ref, type ComputedRef } from 'vue'
import type { LayoutType } from './useSetting'

/** 布局尺寸配置 */
export interface LayoutConfig {
  /** 侧边栏展开宽度 */
  sidebarWidth: number
  /** 侧边栏折叠宽度 */
  collapsedWidth: number
  /** 头部高度 */
  headerHeight: number
  /** 标签页高度 */
  tagsViewHeight: number
  /** 左侧导航栏宽度（left 布局） */
  leftNavWidth: number
  /** 左侧子菜单宽度（left 布局） */
  leftSubWidth: number
}

/** 布局尺寸常量配置 */
export const layoutConfig: LayoutConfig = {
  sidebarWidth: 208,
  collapsedWidth: 48,
  headerHeight: 48,
  tagsViewHeight: 48,
  leftNavWidth: 80,
  leftSubWidth: 160
}

/** useLayoutDimensions 的依赖参数 */
export interface LayoutDimensionsDeps {
  /** 侧边栏是否展开 */
  sidebarOpened: Ref<boolean>
  /** 是否为移动端 */
  isMobile: Ref<boolean>
  /** 当前布局模式 */
  layout: Ref<LayoutType>
  /** 侧边栏菜单数量 */
  sideMenuCount: ComputedRef<number>
  /** 是否固定多标签页 */
  fixedMultiTabs: Ref<boolean>
  /** 是否分割菜单 */
  splitMenu: Ref<boolean>
  /** 导航栏主题色 */
  navTheme: ComputedRef<string>
  /** 是否为 left 布局且非移动端 */
  showLeftNav: ComputedRef<boolean>
}

/**
 * useLayoutDimensions - 布局尺寸计算 Hook
 * @param deps - 依赖的状态 ref 和 computed
 * @returns 布局尺寸相关的计算属性
 * @description 纯计算函数，不创建任何状态，所有依赖通过参数注入
 */
export function useLayoutDimensions(deps: LayoutDimensionsDeps) {
  const {
    sidebarOpened,
    isMobile,
    layout,
    sideMenuCount,
    fixedMultiTabs,
    splitMenu,
    navTheme,
    showLeftNav
  } = deps

  /**
   * 侧边栏宽度
   * @description 根据展开/折叠状态和布局模式计算：
   * - left 布局：使用 leftSubWidth
   * - 其他：展开时使用 sidebarWidth，折叠时使用 collapsedWidth
   */
  const sideWidth = computed(() => {
    const sw = sidebarOpened.value ? layoutConfig.sidebarWidth : layoutConfig.collapsedWidth
    if (showLeftNav.value) return layoutConfig.leftSubWidth
    return sw
  })

  /**
   * 侧边栏占位宽度
   * @description 用于在文档流中占据空间，防止内容被固定定位的侧边栏遮挡：
   * - left 布局：leftNavWidth + sideWidth（双栏）
   * - 其他：sideWidth（单栏）
   */
  const sidebarOccupiedWidth = computed(() => {
    if (layout.value === 'left') {
      if (!sideMenuCount.value) return layoutConfig.leftNavWidth
      return sideWidth.value + layoutConfig.leftNavWidth
    }
    return sideWidth.value
  })

  /**
   * 内容区需要扣除的宽度
   * @description 用于计算头部和标签页的宽度：
   * - left 布局：使用 sidebarOccupiedWidth
   * - top 布局或移动端：0（全宽）
   * - 无子菜单时：left 布局扣除 leftNavWidth，其他扣除 0
   */
  const reduceWidth = computed(() => {
    let reduce = sidebarOpened.value ? layoutConfig.sidebarWidth : layoutConfig.collapsedWidth
    if (layout.value === 'left') reduce = sidebarOccupiedWidth.value
    if (layout.value === 'top' || isMobile.value) reduce = 0
    if (!sideMenuCount.value) {
      reduce = layout.value === 'left' ? layoutConfig.leftNavWidth : 0
    }
    return reduce
  })

  /**
   * 标签页容器宽度
   * @description 固定标签页时使用 calc 扣除侧边栏宽度，非固定时自适应
   */
  const tabsWidth = computed(() => {
    const width = reduceWidth.value
    return fixedMultiTabs.value ? `calc(100% - ${width}px)` : 'auto'
  })

  /**
   * 侧边栏 left 偏移
   * @description left 布局下侧边栏需要偏移 leftNavWidth，
   *              亮色主题下设置白色背景
   */
  const siderLeft = computed(() => {
    const left = showLeftNav.value ? `${layoutConfig.leftNavWidth}px` : '0px'
    const background = navTheme.value === 'light' ? '#ffffff' : undefined
    return { left, background }
  })

  /**
   * 是否显示侧边栏
   * @description 移动端始终显示（抽屉模式），
   *              top 布局不显示，mix 布局无子菜单时不显示
   */
  const showSidebar = computed(() => {
    if (isMobile.value) return true
    if (layout.value === 'top') return false
    if (layout.value === 'mix' && !sideMenuCount.value) return false
    return true
  })

  /**
   * 是否显示侧边栏折叠触发器
   * @description 仅 mix 布局未开启 splitMenu 时显示
   */
  const showSideTrigger = computed(() => {
    return layout.value === 'mix' && !splitMenu.value
  })

  return {
    sideWidth,
    sidebarOccupiedWidth,
    reduceWidth,
    tabsWidth,
    siderLeft,
    showSidebar,
    showSideTrigger
  }
}
