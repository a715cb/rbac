/**
 * @文件: useMenuState.ts
 * @用途: 菜单状态管理 Hook
 * @描述: 管理菜单数据的计算和派生状态，包括菜单列表、侧边栏菜单、
 *        菜单数量、布局模式判断等。使用 createSharedComposable 确保全局单例。
 * @核心逻辑:
 *   1. 合并仪表盘静态菜单和后端动态菜单
 *   2. 根据布局模式和路由计算侧边栏应显示的菜单
 *   3. mix/left 布局下提取一级菜单的子菜单作为侧边栏菜单
 *   4. 判断当前是否为混合布局或 left 布局
 */

import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useStorage, createSharedComposable } from '@vueuse/core'
import { useUserStore } from '@/stores/user'
import { asRouteMenuList, getMatchedMenuPath } from '@/layouts/components/menuUtils'
import type { RouteMenu } from '@/layouts/components/menuUtils'
import { AppConfig } from '@/config/app'
import { prefixedKey } from '@/constants/storage'
import { useDeviceDetection } from './useDeviceDetection'
import type { LayoutType } from './useSetting'

/**
 * 仪表盘静态路由配置
 * 单独定义以便于在左侧菜单中显示，而无需依赖后端返回的动态菜单
 * 使用 ant-design: 前缀确保 SIcon 组件能正确解析 Ant Design 图标
 */
const DASHBOARD_MENU: RouteMenu = {
  path: '/dashboard',
  name: 'Dashboard',
  icon: 'ant-design:dashboard-outlined',
  meta: {
    title: '仪表盘',
    icon: 'ant-design:dashboard-outlined'
  }
}

/**
 * useMenuState - 菜单状态管理 Hook
 * @returns 菜单相关的计算属性和状态
 *
 * 使用 createSharedComposable 确保全局单例，
 * 多个组件调用 useMenuState() 共享同一份状态。
 */
export const useMenuState = createSharedComposable(() => {
  const userStore = useUserStore()
  const route = useRoute()

  /** 当前布局模式，与 useSetting 共享同一 storage key */
  const layout = useStorage<LayoutType>(prefixedKey(AppConfig.layoutKey), 'side')
  /** 是否分割菜单，与 useSetting 共享同一 storage key */
  const splitMenu = useStorage(prefixedKey(AppConfig.splitMenuKey), false)
  /** 侧边栏是否展开，与 useSetting 共享同一 storage key */
  const sidebarOpened = useStorage(prefixedKey(AppConfig.sidebarOpenedKey), true)

  const { isMobile } = useDeviceDetection()

  /**
   * 菜单数据
   * @description 合并仪表盘静态菜单和后端动态菜单，
   *              无动态菜单时仅显示仪表盘
   */
  const menus = computed<RouteMenu[]>(() => {
    const dynamicMenus = asRouteMenuList(userStore.menuRoutes)
    if (dynamicMenus.length === 0) {
      return [DASHBOARD_MENU]
    }
    return [DASHBOARD_MENU, ...dynamicMenus]
  })

  /** 是否为混合布局（mix 或 left） */
  const isMixLayout = computed(() => ['mix', 'left'].includes(layout.value))

  /** 是否为 left 布局且非移动端（控制 LeftNav 的显示） */
  const showLeftNav = computed(() => layout.value === 'left' && !isMobile.value)

  /**
   * 侧边栏菜单数据
   * @description 根据布局模式计算侧边栏应显示的菜单：
   * - mix 布局 + splitMenu：显示当前一级菜单的子菜单
   * - left 布局：显示当前一级菜单的子菜单
   * - 其他：显示完整菜单
   */
  const sideMenus = computed<RouteMenu[]>(() => {
    const isMixOrLeftLayout = (layout.value === 'mix' && splitMenu.value) || layout.value === 'left'
    if (isMixOrLeftLayout && !isMobile.value) {
      const matchedPath = getMatchedMenuPath(route, menus.value)
      const menu = menus.value.find((v) => v.path === matchedPath && !v.hideChildrenInMenu)
      if (menu?.children) {
        sidebarOpened.value = true
        return menu.children
      }
      return []
    }
    return menus.value
  })

  /** 侧边栏菜单数量 */
  const sideMenuCount = computed(() => sideMenus.value.length)

  return {
    menus,
    sideMenus,
    sideMenuCount,
    isMixLayout,
    showLeftNav
  }
})
