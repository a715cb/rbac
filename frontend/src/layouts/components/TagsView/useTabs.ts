/**
 * @文件: useTabs.ts
 * @用途: 多标签页状态管理 Hook
 * @描述: 管理多标签页的创建、激活、关闭等操作，标签页数据持久化到 sessionStorage。
 *        监听路由变化自动创建标签页，确保仪表盘标签始终固定在首位，
 *        关闭标签页时自动跳转到相邻标签页。
 * @核心逻辑:
 *   1. 路由变化时自动创建标签页（通过 watch route.path）
 *   2. 仪表盘标签始终固定在首位且不可关闭（affix: true）
 *   3. 关闭标签页时自动跳转到前一个或首个标签页
 *   4. 标签页数据通过 useStorage 持久化到 sessionStorage
 */

import { watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDebounceFn, useStorage } from '@vueuse/core'
import { AppConfig } from '@/config/app'
import { prefixedKey } from '@/constants/storage'
import { isTabAllowed, getSafeStorage } from './tabsUtils'

export { clearTabsStorage, isTabAllowed, getSafeStorage } from './tabsUtils'

/** 标签页数据结构 */
interface Tabs {
  /** 标签页标题 */
  title: string
  /** 标签页路径，作为唯一标识 */
  path: string
  /** 标签页图标标识 */
  icon: string
  /** 是否固定标签页（固定标签不可关闭） */
  affix?: boolean
  /** 路由查询参数，跳转时保留原始 query */
  query?: Record<string, unknown>
}

/** 路由跳转参数 */
interface RouteParams {
  /** 目标路径 */
  path: string
  /** 路由查询参数 */
  query?: Record<string, unknown>
}

/** 仪表盘固定标签配置，始终显示在标签页首位 */
const DASHBOARD_TAB: Tabs = {
  title: '仪表盘',
  path: '/dashboard',
  icon: 'ant-design:dashboard-outlined',
  affix: true
}

/** sessionStorage 存储键名 */
const STORAGE_KEY_LIST = prefixedKey(AppConfig.tabsListKey)
const STORAGE_KEY_ACTIVE = prefixedKey(AppConfig.tabsActiveKey)

/**
 * 确保仪表盘标签始终位于列表首位
 * @param list - 当前标签页列表
 * @returns 调整后的标签页列表
 * @description 如果仪表盘标签不存在则插入到首位，
 *              如果存在但不在首位则移动到首位，并强制设置 affix: true
 */
function ensureDashboardFirst(list: Tabs[]): Tabs[] {
  const dashboardIndex = list.findIndex((item) => item.path === DASHBOARD_TAB.path)
  if (dashboardIndex === -1) {
    return [DASHBOARD_TAB, ...list]
  }
  if (dashboardIndex === 0) {
    list[0] = { ...DASHBOARD_TAB, ...list[0], affix: true }
    return list
  }
  const [dashboard] = list.splice(dashboardIndex, 1)
  list.unshift({ ...dashboard, affix: true })
  return list
}

/**
 * useTabs - 多标签页状态管理 Hook
 * @returns 标签页状态和操作方法
 *
 * 返回值说明：
 * - active: 当前激活的标签页路径
 * - list: 标签页列表
 * - create: 创建标签页
 * - navigation: 路由跳转（保留 query 参数）
 * - close: 关闭指定标签页
 * - closeAll: 关闭所有标签页（保留仪表盘）
 * - closeOther: 关闭其他标签页（保留当前和仪表盘）
 * - closeCurrent: 关闭当前激活的标签页
 */
export function useTabs() {
  const route = useRoute()
  const router = useRouter()

  const safeStorage = getSafeStorage()

  /** 当前激活标签页路径，持久化到 sessionStorage */
  const active = useStorage<string>(STORAGE_KEY_ACTIVE, '', safeStorage)
  /** 标签页列表，持久化到 sessionStorage */
  const list = useStorage<Array<Tabs>>(STORAGE_KEY_LIST, [], safeStorage)

  /**
   * 创建或更新标签页
   * @param tab - 标签页数据
   * @description 如果标签页已存在则更新标题、图标和 query，否则添加新标签页
   */
  const create = (tab: Tabs) => {
    if (!tab.path || !tab.title) return
    const has = list.value.find((item: Tabs) => item.path === tab.path)
    if (!has) {
      list.value.push(tab)
    } else {
      has.title = tab.title
      has.icon = tab.icon
      has.query = tab.query
    }
    active.value = tab.path
  }

  /**
   * 监听路由变化，自动创建标签页
   * @description 路由变化时提取 meta 中的标题、图标和 affix 属性，
   *              连同当前 query 参数一起创建标签页
   */
  watch(
    () => route.path,
    (path) => {
      if (!isTabAllowed(route)) return
      const title = route.meta.title as string
      const icon = route.meta.icon as string
      const query: Record<string, unknown> = route.query
      const affix = route.meta.affix as boolean | undefined
      create({ title, path, icon, affix, query })
    },
    { immediate: true }
  )

  /**
   * 监听标签页列表变化，确保仪表盘始终在首位
   * @description 使用防抖（50ms）避免频繁操作，检查首个标签是否为仪表盘，
   *              若不是则调用 ensureDashboardFirst 进行调整
   */
  watch(
    list,
    useDebounceFn(() => {
      const first = list.value[0]
      if (!first || first.path !== DASHBOARD_TAB.path || !first.affix) {
        list.value = ensureDashboardFirst([...list.value])
      }
    }, 50),
    { immediate: true, deep: true }
  )

  /**
   * 路由跳转，保留原始 query 参数
   * @param next - 跳转目标（path + query）
   * @description 仅在目标路径与当前路径不同时执行跳转，避免重复导航
   */
  const navigation = (next: RouteParams) => {
    if (route.path !== next.path) {
      router.push(next)
    }
  }

  /**
   * 关闭指定标签页
   * @param path - 要关闭的标签页路径
   * @description 固定标签页（affix: true）不可关闭。
   *              关闭当前激活的标签页时，自动跳转到前一个标签页或首个标签页
   */
  const close = (path: string) => {
    const tab = list.value.find((item) => item.path === path)
    if (tab?.affix) return

    const index = list.value.findIndex((item) => item.path === path)
    list.value = list.value.filter((item) => item.path !== path)
    if (route.path === path && list.value.length) {
      if (index >= 1) {
        const routes = list.value[index - 1]
        navigation({ path: routes.path, query: routes.query })
      } else {
        const routes = list.value[0]
        navigation({ path: routes.path, query: routes.query })
      }
    }
  }

  /** 关闭当前激活的标签页 */
  const closeCurrent = () => {
    const currentPath = route.path
    const tab = list.value.find((item) => item.path === currentPath)
    if (tab?.affix) return
    close(currentPath)
  }

  /**
   * 关闭除当前激活标签页外的其他标签页
   * @description 始终保留仪表盘标签页，若当前标签不是仪表盘则同时保留当前标签
   */
  const closeOther = () => {
    const currentTab = list.value.find((item) => item.path === active.value)
    const result: Tabs[] = [DASHBOARD_TAB]
    if (currentTab && currentTab.path !== DASHBOARD_TAB.path) {
      result.push({ ...currentTab })
    }
    list.value = result
  }

  /**
   * 关闭所有标签页，仅保留仪表盘
   * @description 关闭后自动跳转到仪表盘页面
   */
  const closeAll = () => {
    list.value = [DASHBOARD_TAB]
    active.value = DASHBOARD_TAB.path
    if (route.path !== DASHBOARD_TAB.path) {
      router.push({ path: DASHBOARD_TAB.path })
    }
  }

  return {
    active,
    list,
    create,
    navigation,
    close,
    closeAll,
    closeOther,
    closeCurrent
  }
}
