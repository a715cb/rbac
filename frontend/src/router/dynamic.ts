import type { RouteRecordRaw } from 'vue-router'
import router from '@/router'

const modules = import.meta.glob('@/pages/**/*.vue')

interface MenuRoute {
  id: number
  parent_id: number
  name: string
  code: string
  path: string
  component: string
  icon: string
  menu_type: number
  sort: number
  visible: number
  status: number
  children?: MenuRoute[]
}

const layoutModules: Record<string, () => Promise<Record<string, unknown>>> = {
  DefaultLayout: () => import('@/layouts/DefaultLayout.vue')
}

function getErrorComponent() {
  return () => import('@/pages/error/404.vue')
}

function sanitizeRoutePath(path: string): string {
  if (!path) return path

  let sanitized = path

  sanitized = sanitized.replace(/\/\*+$/, '')

  sanitized = sanitized.replace(/\*/g, '')

  sanitized = sanitized.replace(/\/+/g, '/')

  if (sanitized === '/') return sanitized

  sanitized = sanitized.replace(/\/$/, '')

  return sanitized || '/'
}

function getComponent(component: string) {
  if (component.startsWith('layouts/')) {
    const layoutName = component.replace('layouts/', '')
    return layoutModules[layoutName]
  }

  let normalizedComponent = component
  if (normalizedComponent.startsWith('@/')) {
    normalizedComponent = normalizedComponent.replace('@/', '/src/')
  } else if (!normalizedComponent.startsWith('/')) {
    normalizedComponent = `/src/pages/${normalizedComponent}`
  }

  const viewPath = `${normalizedComponent}.vue`
  const mod = modules[viewPath]
  if (mod) return mod

  if (import.meta.env.DEV)
    console.warn(`[Router] Component not found: ${component}, tried path: ${viewPath}`)
  return getErrorComponent()
}

function transformMenusToRoutes(menus: MenuRoute[]): RouteRecordRaw[] {
  const routes: RouteRecordRaw[] = []

  for (const menu of menus) {
    if (menu.menu_type === 3) continue

    if (menu.menu_type === 1) {
      const childRoutes: RouteRecordRaw[] = []
      if (menu.children) {
        for (const child of menu.children) {
          if (child.menu_type === 3) continue
          childRoutes.push(createRoute(child))
        }
      }

      const sanitizedPath = sanitizeRoutePath(menu.path) || '/'
      const firstChildPath =
        childRoutes.length > 0 ? sanitizeRoutePath(childRoutes[0].path) : undefined

      routes.push({
        path: sanitizedPath,
        name: menu.code,
        component: layoutModules.DefaultLayout,
        redirect: firstChildPath || undefined,
        meta: {
          title: menu.name,
          icon: menu.icon,
          menu_type: menu.menu_type,
          visible: menu.visible,
          sort: menu.sort
        },
        children: childRoutes
      })
    } else if (menu.menu_type === 2) {
      routes.push(createRoute(menu))
    }
  }

  return routes
}

function createRoute(menu: MenuRoute): RouteRecordRaw {
  const sanitizedPath = sanitizeRoutePath(menu.path)
  const component = menu.component ? getComponent(menu.component) : undefined
  const children = menu.children
    ?.filter((child) => child.menu_type !== 3)
    .map((child) => createRoute(child))

  if (!component && children && children.length > 0) {
    const firstChildPath = sanitizeRoutePath(children[0].path)
    return {
      path: sanitizedPath,
      name: menu.code,
      redirect: firstChildPath || undefined,
      meta: {
        title: menu.name,
        icon: menu.icon,
        menu_type: menu.menu_type,
        visible: menu.visible,
        sort: menu.sort,
        keep_alive: true
      },
      children
    }
  }

  if (children && children.length > 0) {
    return {
      path: sanitizedPath,
      name: menu.code,
      component: component || getErrorComponent(),
      meta: {
        title: menu.name,
        icon: menu.icon,
        menu_type: menu.menu_type,
        visible: menu.visible,
        sort: menu.sort,
        keep_alive: true
      },
      children
    }
  }

  return {
    path: sanitizedPath,
    name: menu.code,
    component: component || getErrorComponent(),
    meta: {
      title: menu.name,
      icon: menu.icon,
      menu_type: menu.menu_type,
      visible: menu.visible,
      sort: menu.sort,
      keep_alive: true
    }
  }
}

export function addDynamicRoutes(routes: RouteRecordRaw[]): void {
  routes.forEach((route) => {
    const existingRoute = router.getRoutes().find((r) => r.name === route.name)
    if (!existingRoute) {
      router.addRoute(route)
    }
  })
}

export function removeDynamicRoutes(): void {
  const currentRoutes = router.getRoutes()
  currentRoutes.forEach((route) => {
    if (route.meta?.menu_type) {
      router.removeRoute(route.name as string)
    }
  })
}

export { transformMenusToRoutes, type MenuRoute }
