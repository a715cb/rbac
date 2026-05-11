import { watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDebounceFn, useStorage } from '@vueuse/core'
import { AppConfig } from '@/config/app'
import { prefixedKey } from '@/constants/storage'
import { StorageManager } from '@/utils/storage'

interface Tabs {
  title: string
  path: string
  icon: string
  affix?: boolean
  query?: Record<string, any>
}

interface RouteParams {
  path: string
  query?: Record<string, any>
}

const DASHBOARD_TAB: Tabs = {
  title: '仪表盘',
  path: '/dashboard',
  icon: 'ant-design:dashboard-outlined',
  affix: true
}

const STORAGE_KEY_LIST = prefixedKey(AppConfig.tabsListKey)
const STORAGE_KEY_ACTIVE = prefixedKey(AppConfig.tabsActiveKey)

export function clearTabsStorage(): void {
  StorageManager.removeItem('session', AppConfig.tabsListKey)
  StorageManager.removeItem('session', AppConfig.tabsActiveKey)
}

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

export function isTabAllowed(route: { path: string; meta?: Record<string, unknown> }): boolean {
  const meta = route.meta || {}
  if (meta.hidden) return false
  if (meta.requiresAuth === false) return false
  if (!meta.title) return false
  return true
}

export function useTabs() {
  const route = useRoute()
  const router = useRouter()

  const active = useStorage<string>(STORAGE_KEY_ACTIVE, '', sessionStorage)
  const list = useStorage<Array<Tabs>>(STORAGE_KEY_LIST, [], sessionStorage)

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

  watch(
    () => route.path,
    (path) => {
      if (!isTabAllowed(route)) return
      const title = route.meta.title as string
      const icon = route.meta.icon as string
      const query: Record<string, any> = route.query
      const affix = route.meta.affix as boolean | undefined
      create({ title, path, icon, affix, query })
    },
    { immediate: true }
  )

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

  const navigation = (next: RouteParams) => {
    if (route.path !== next.path) {
      router.push(next)
    }
  }

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

  const closeCurrent = () => {
    const currentPath = route.path
    const tab = list.value.find((item) => item.path === currentPath)
    if (tab?.affix) return
    close(currentPath)
  }

  const closeOther = () => {
    const currentTab = list.value.find((item) => item.path === active.value)
    const result: Tabs[] = [DASHBOARD_TAB]
    if (currentTab && currentTab.path !== DASHBOARD_TAB.path) {
      result.push({ ...currentTab })
    }
    list.value = result
  }

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
