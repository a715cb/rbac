/**
 * @文件: tabsUtils.ts
 * @用途: 标签页纯工具函数
 * @描述: 提供标签页相关的纯函数工具，不依赖 Vue 上下文，可在任何地方调用。
 *        包括标签页存储清理、标签页创建权限判断、安全存储获取。
 */
import { AppConfig } from '@/config/app'
import { StorageManager } from '@/utils/storage'

/**
 * 清除 sessionStorage 中的标签页数据
 * @description 用于退出登录时清理标签页状态
 */
export function clearTabsStorage(): void {
  StorageManager.removeItem('session', AppConfig.tabsListKey)
  StorageManager.removeItem('session', AppConfig.tabsActiveKey)
}

/**
 * 判断路由是否允许创建标签页
 * @param route - 路由对象
 * @returns 是否允许创建标签页
 * @description 隐藏路由、无需认证的路由和无标题路由不创建标签页
 */
export function isTabAllowed(route: { path: string; meta?: Record<string, unknown> }): boolean {
  const meta = route.meta || {}
  if (meta.hidden) return false
  if (meta.requiresAuth === false) return false
  if (!meta.title) return false
  return true
}

/**
 * 安全获取 sessionStorage
 * @description 检测 sessionStorage 是否可用（隐私模式、存储满等情况可能不可用），
 *              不可用时回退到内存存储
 */
export function getSafeStorage(): Storage {
  try {
    const testKey = '__storage_test__'
    sessionStorage.setItem(testKey, '1')
    sessionStorage.removeItem(testKey)
    return sessionStorage
  } catch {
    return {
      getItem: () => null,
      setItem: () => {},
      removeItem: () => {},
      clear: () => {},
      key: () => null,
      length: 0
    }
  }
}
