import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import type { RouteLocationNormalized, NavigationGuardNext } from 'vue-router'
import NProgress from 'nprogress'
import 'nprogress/nprogress.css'
import { AppConfig } from '@/config/app'
import { StorageManager } from '@/utils/storage'
import { useUserStore } from '@/stores/user'
import { cancelPendingRequests } from '@/utils/request'
import type { MenuRoute } from '@/router/dynamic'

NProgress.configure({
  showSpinner: false,
  trickleSpeed: 200,
  minimum: 0.1
})

const staticRoutes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/pages/login/index.vue'),
    meta: {
      title: '登录',
      requiresAuth: false
    }
  },
  {
    path: '/',
    component: () => import('@/layouts/DefaultLayout.vue'),
    redirect: '/dashboard',
    children: [
      {
        path: '/dashboard',
        name: 'Dashboard',
        component: () => import('@/pages/dashboard/index.vue'),
        meta: {
          title: '仪表盘',
          requiresAuth: true,
          affix: true
        }
      }
    ]
  },
  {
    path: '/403',
    name: 'Forbidden',
    component: () => import('@/pages/error/403.vue'),
    meta: {
      title: '无权限',
      requiresAuth: false
    }
  },
  {
    path: '/404',
    name: 'NotFound',
    component: () => import('@/pages/error/404.vue'),
    meta: {
      title: '页面不存在',
      requiresAuth: false
    }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'CatchAll',
    component: () => import('@/pages/error/404.vue'),
    meta: {
      title: '页面不存在',
      requiresAuth: false
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes: staticRoutes,
  scrollBehavior() {
    return { top: 0 }
  }
})

const whiteList = ['/login', '/403', '/404']

function hasAccessPermission(
  to: RouteLocationNormalized,
  userStore: ReturnType<typeof useUserStore>
): boolean {
  const meta = to.meta || {}
  const requireRoles = meta.roles as string[] | undefined
  const requirePerms = meta.permissions as string[] | undefined

  if (!requireRoles && !requirePerms) return true

  if (requireRoles?.length) {
    if (!userStore.hasAnyRoles(requireRoles)) return false
  }

  if (requirePerms?.length) {
    if (!userStore.hasAnyPermission(requirePerms)) return false
  }

  return true
}

function isRouteUnmatched(to: {
  matched: { length: number }
  name: string | symbol | null | undefined
}): boolean {
  return to.matched.length === 0 || to.name === 'CatchAll'
}

async function loadAndApplyDynamicRoutes(
  userStore: ReturnType<typeof useUserStore>,
  to: RouteLocationNormalized,
  next: NavigationGuardNext
): Promise<void> {
  const storedMenus = StorageManager.getItem('session', AppConfig.menusKey)
  if (storedMenus) {
    const menuData = JSON.parse(storedMenus) as MenuRoute[]
    const { addDynamicRoutes, transformMenusToRoutes } = await import('./dynamic')
    const routes = transformMenusToRoutes(menuData)
    userStore.setMenuRoutes(routes)
    addDynamicRoutes(routes)
    userStore.dynamicRoutesAdded = true
    if (hasAccessPermission(to, userStore)) {
      next({ path: to.path, query: to.query, hash: to.hash, replace: true })
    } else {
      next({ name: 'Forbidden', replace: true })
    }
    return
  }

  try {
    await userStore.fetchUserInfo()
  } catch {
    userStore.logout()
    next({ path: '/login', replace: true })
    return
  }

  if (!userStore.menus || userStore.menus.length === 0) {
    userStore.logout()
    next({ path: '/login', replace: true })
    return
  }

  const { addDynamicRoutes, transformMenusToRoutes } = await import('./dynamic')
  const menuData = userStore.menus as unknown as MenuRoute[]
  const routes = transformMenusToRoutes(menuData)
  userStore.setMenuRoutes(routes)
  addDynamicRoutes(routes)
  userStore.dynamicRoutesAdded = true

  if (hasAccessPermission(to, userStore)) {
    next({ path: to.path, query: to.query, hash: to.hash, replace: true })
  } else {
    next({ name: 'Forbidden', replace: true })
  }
}

router.beforeEach(async (to, _from, next) => {
  cancelPendingRequests('路由切换')

  try {
    const openNProgress = StorageManager.getItem('local', AppConfig.nprogressKey) ?? 'true'
    if (openNProgress === 'true') {
      try {
        NProgress.start()
      } catch (error) {
        if (import.meta.env.DEV) console.error('[Router] NProgress start failed:', error)
      }
    }
  } catch (error) {
    if (import.meta.env.DEV) console.error('[Router] BeforeEach outer failed:', error)
  }

  const token = StorageManager.getItem('session', AppConfig.tokenKey)

  document.title = (to.meta.title as string)
    ? `${to.meta.title} - ${import.meta.env.VITE_APP_TITLE || 'RBAC 权限管理系统'}`
    : 'RBAC 权限管理系统'

  if (whiteList.includes(to.path)) {
    if (to.path === '/login' && token) {
      next({ path: '/dashboard', replace: true })
      return
    }
    next()
    return
  }

  if (!token) {
    next({ path: '/login', query: { redirect: to.fullPath } })
    return
  }

  const userStore = useUserStore()

  if (!userStore.token) {
    userStore.loadFromStorage()
  }

  if (!userStore.token) {
    userStore.logout()
    next({ path: '/login', query: { redirect: to.fullPath } })
    return
  }

  const hasDynamicRoutes =
    router.getRoutes().some((r) => r.meta?.menu_type) || userStore.dynamicRoutesAdded

  if (!hasDynamicRoutes) {
    try {
      await loadAndApplyDynamicRoutes(userStore, to, next)
    } catch (error) {
      if (import.meta.env.DEV) console.error('[Router] Load dynamic routes failed:', error)
      userStore.logout()
      next({ path: '/login', query: { redirect: to.fullPath } })
    }
    return
  }

  if (isRouteUnmatched(to)) {
    if (hasAccessPermission(to, userStore)) {
      next({ name: 'NotFound', replace: true })
    } else {
      next({ name: 'Forbidden', replace: true })
    }
    return
  }

  if (!hasAccessPermission(to, userStore)) {
    next({ name: 'Forbidden', replace: true })
    return
  }

  next()
})

router.afterEach(() => {
  try {
    NProgress.done()
  } catch (error) {
    if (import.meta.env.DEV) console.error('[Router] NProgress done failed:', error)
  }
  window.scrollTo(0, 0)
})

export default router
