import type { RouteLocationNormalized } from 'vue-router'

export interface RouteMenu {
  path: string
  name?: string
  icon?: string
  hidden?: boolean
  hideChildrenInMenu?: boolean
  children?: RouteMenu[]
  visible?: number
  status?: number
  meta?: {
    title?: string
    icon?: string
    hidden?: boolean
  }
}

export const DEFAULT_MENU_ICON = 'appstore-outlined'
export const DEFAULT_MENU_TITLE = '未命名菜单'

export const isMenuVisible = (item: RouteMenu): boolean => {
  if (item.hidden) return false
  if (item.visible !== undefined && item.visible !== 1) return false
  if (item.status !== undefined && item.status !== 1) return false
  if (item.meta?.hidden) return false
  return true
}

export const hasMenuIcon = (item: RouteMenu): boolean => {
  return !!(item.meta?.icon || item.icon)
}

export const getMenuIcon = (item: RouteMenu): string => {
  return item.meta?.icon || item.icon || DEFAULT_MENU_ICON
}

export const getMenuTitle = (item: RouteMenu): string => {
  return item.meta?.title || item.name || DEFAULT_MENU_TITLE
}

export function asRouteMenuList(routes: any[]): RouteMenu[] {
  return routes.filter((item) => item && typeof item === 'object' && 'path' in item) as RouteMenu[]
}

export function getMatchedMenuPath(route: RouteLocationNormalized, menus: RouteMenu[]): string {
  const menuPaths = new Set<string>()
  collectMenuPaths(menus, menuPaths)

  for (const matched of route.matched) {
    if (matched.path === '/') continue
    if (menuPaths.has(matched.path)) return matched.path
  }

  if (route.matched[0]?.path === '/' && menuPaths.has(route.path)) {
    return route.path
  }

  return route.path
}

function collectMenuPaths(menus: RouteMenu[], result: Set<string>): void {
  for (const menu of menus) {
    if (menu.path) result.add(menu.path)
    if (menu.children?.length) collectMenuPaths(menu.children, result)
  }
}
