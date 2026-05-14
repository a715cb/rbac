/**
 * @文件: useHeaderSetting.ts
 * @用途: 头部布局配置 Hook
 * @描述: 基于 useSetting 派生头部导航栏的布局参数，包括宽度、高度、
 *        是否显示顶部菜单、是否显示面包屑等计算属性。
 *        将头部相关的计算逻辑集中管理，避免 Header 组件中重复计算。
 * @核心逻辑:
 *   1. width: 根据布局模式和侧边栏状态计算头部宽度
 *   2. height: 从 layoutConfig 获取固定的头部高度
 *   3. showTopMenu: top/mix 布局且非移动端时显示顶部菜单
 *   4. sideOrMobile: side 布局或移动端时显示折叠触发器
 *   5. showBreadcrumb: side/left 布局且非移动端时显示面包屑
 */

import { computed } from 'vue'
import { useSetting } from './useSetting'

/**
 * useHeaderSetting - 头部布局配置 Hook
 * @returns 头部布局相关的计算属性
 *
 * 返回值说明：
 * - width: 头部宽度（calc 表达式，扣除侧边栏宽度）
 * - height: 头部高度（px 字符串）
 * - showBreadcrumb: 是否显示面包屑导航
 * - showTopMenu: 是否显示顶部横向菜单
 * - sideOrMobile: 是否为侧边栏布局或移动端
 * - navTheme: 导航栏主题色
 */
export function useHeaderSetting() {
  const {
    layoutConfig,
    showBreadcrumb: globalShowBreadcrumb,
    navTheme,
    layout,
    isMobile,
    reduceWidth
  } = useSetting()

  /**
   * 头部宽度
   * @description 根据布局模式计算：
   * - left 布局：扣除 leftNavWidth
   * - top/mix 布局或移动端：全宽（扣除 0）
   * - 其他：扣除侧边栏宽度
   */
  const width = computed(() => {
    let reduce = reduceWidth.value
    if (layout.value === 'left') reduce = layoutConfig.leftNavWidth
    if (['top', 'mix'].includes(layout.value) || isMobile.value) reduce = 0
    return `calc(100% - ${reduce}px)`
  })

  /** 头部高度，从 layoutConfig 获取固定值 */
  const height = computed(() => {
    return `${layoutConfig.headerHeight}px`
  })

  /**
   * 是否显示顶部横向菜单
   * @description 仅在 top/mix 布局且非移动端时显示
   */
  const showTopMenu = computed(() => {
    return (layout.value === 'top' || layout.value === 'mix') && !isMobile.value
  })

  /**
   * 是否为侧边栏布局或移动端
   * @description 用于控制折叠触发器的显示：
   * side 布局和移动端需要显示折叠触发器
   */
  const sideOrMobile = computed(() => {
    return layout.value === 'side' || isMobile.value
  })

  /**
   * 是否显示面包屑导航
   * @description 移动端不显示面包屑；
   * side/left 布局且全局开启面包屑时显示
   */
  const showBreadcrumb = computed(() => {
    if (isMobile.value) return false
    if (['side', 'left'].includes(layout.value) && globalShowBreadcrumb.value) {
      return true
    }
    return false
  })

  return {
    width,
    height,
    showBreadcrumb,
    showTopMenu,
    sideOrMobile,
    navTheme
  }
}
