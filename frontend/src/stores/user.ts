import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { getCurrentUser } from '@/api/auth'
import { logout as logoutApi } from '@/api/auth'
import type { UserInfo } from './types'
import { AppConfig } from '@/config/app'
import { TokenManager } from '@/utils/token'
import { StorageManager } from '@/utils/storage'
import { clearTabsStorage } from '@/layouts/components/TagsView/useTabs'
import { removeDynamicRoutes } from '@/router/dynamic'
import type { RouteRecordRaw } from 'vue-router'
import type { MenuRoute } from '@/router/dynamic'

interface RoleInfo {
  id: number
  name: string
  code: string
}

export const useUserStore = defineStore('user', () => {
  const token = ref<string>('')
  const userInfo = ref<UserInfo | null>(null)
  const menus = ref<MenuRoute[]>([])
  const menuRoutes = ref<RouteRecordRaw[]>([])
  const permissions = ref<string[]>([])
  const roleList = ref<RoleInfo[]>([])
  const dynamicRoutesAdded = ref(false)

  const isLoggedIn = computed(() => !!token.value)
  const username = computed(() => userInfo.value?.username || '')
  const avatar = computed(() => userInfo.value?.avatar || '')
  const roles = computed(() => userInfo.value?.roles || [])
  const roleCodes = computed(() => roleList.value.map((r) => r.code))

  function setToken(newToken: string): void {
    token.value = newToken
    StorageManager.setItem('session', AppConfig.tokenKey, newToken)
  }

  function setTokenWithExpiry(accessToken: string, refreshToken: string, expiresIn: number): void {
    const expiresAt = Math.floor(Date.now() / 1000) + expiresIn
    token.value = accessToken
    StorageManager.setItem('session', AppConfig.tokenKey, accessToken)
    StorageManager.setItem('local', AppConfig.refreshTokenKey, refreshToken)
    StorageManager.setItem('local', AppConfig.tokenExpiresKey, String(expiresAt))
  }

  function setUserInfo(info: UserInfo): void {
    userInfo.value = info
    StorageManager.setObject('session', AppConfig.userInfoKey, info)
  }

  function setMenus(menuList: MenuRoute[]): void {
    menus.value = menuList
    StorageManager.setObject('session', AppConfig.menusKey, menuList)
  }

  function setMenuRoutes(routes: RouteRecordRaw[]): void {
    menuRoutes.value = routes
  }

  function setPermissions(perms: string[]): void {
    permissions.value = perms
  }

  function setRoles(rolesData: RoleInfo[]): void {
    roleList.value = rolesData
  }

  function loadFromStorage(): void {
    const storedToken = StorageManager.getItem('session', AppConfig.tokenKey)
    const storedUserInfo = StorageManager.getObject<UserInfo>('session', AppConfig.userInfoKey)
    const storedMenus = StorageManager.getObject<MenuRoute[]>('session', AppConfig.menusKey)

    if (storedToken) {
      token.value = storedToken
    }

    if (storedUserInfo) {
      userInfo.value = storedUserInfo
    }

    if (storedMenus) {
      menus.value = storedMenus
    }
  }

  async function fetchUserInfo(): Promise<void> {
    try {
      const res = await getCurrentUser()
      const data = res.data

      const info: UserInfo = {
        id: Number(data.id),
        username: data.username,
        email: data.email,
        avatar: data.avatar,
        roles: data.roles?.map((r: RoleInfo) => r.code) || [],
        permissions: data.permissions || []
      }

      setUserInfo(info)
      setMenus((data.menus || []) as unknown as MenuRoute[])
      setPermissions([...(data.permissions || []), ...(data.button_codes || [])])
      setRoles((data.roles || []).map((r: RoleInfo) => ({ ...r, id: Number(r.id) })))
    } catch (error) {
      if (import.meta.env.DEV) console.error('[UserStore] Fetch user info failed:', error)
      // error handled by request interceptor
    }
  }

  function hasPermission(permission: string): boolean {
    if (roleCodes.value.includes('super_admin')) return true
    return permissions.value.includes(permission)
  }

  function hasRole(roleCode: string): boolean {
    if (roleCodes.value.includes('super_admin')) return true
    return roleCodes.value.includes(roleCode)
  }

  function hasAnyPermission(perms: string[]): boolean {
    if (roleCodes.value.includes('super_admin')) return true
    return perms.some((p) => permissions.value.includes(p))
  }

  function hasAnyRoles(roleCodesList: string[]): boolean {
    if (roleCodes.value.includes('super_admin')) return true
    return roleCodesList.some((r) => roleCodes.value.includes(r))
  }

  async function logout(): Promise<void> {
    try {
      if (TokenManager.hasAccessToken()) {
        await logoutApi()
      }
    } catch (error) {
      if (import.meta.env.DEV) console.error('[UserStore] Logout API failed:', error)
      // 服务端登出失败时静默忽略，继续清理本地状态
    }

    token.value = ''
    userInfo.value = null
    menus.value = []
    menuRoutes.value = []
    permissions.value = []
    roleList.value = []
    dynamicRoutesAdded.value = false

    TokenManager.clearToken()
    StorageManager.removeItem('session', AppConfig.userInfoKey)
    StorageManager.removeItem('session', AppConfig.menusKey)
    clearTabsStorage()
    removeDynamicRoutes()
  }

  return {
    token,
    userInfo,
    menus,
    menuRoutes,
    permissions,
    roleList,
    dynamicRoutesAdded,
    isLoggedIn,
    username,
    avatar,
    roles,
    roleCodes,
    setToken,
    setTokenWithExpiry,
    setUserInfo,
    setMenus,
    setMenuRoutes,
    setPermissions,
    setRoles,
    loadFromStorage,
    fetchUserInfo,
    hasPermission,
    hasRole,
    hasAnyPermission,
    hasAnyRoles,
    logout
  }
})
