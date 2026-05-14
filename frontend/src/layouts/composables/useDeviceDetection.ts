/**
 * @文件: useDeviceDetection.ts
 * @用途: 设备类型检测 Hook
 * @描述: 监听窗口尺寸变化，自动检测当前设备类型（desktop/tablet/mobile），
 *        移动端自动收起侧边栏。使用 createSharedComposable 确保全局单例，
 *        通过引用计数管理 resize 事件监听器的注册和注销。
 * @核心逻辑:
 *   1. 使用断点配置（768/1024/1366）判断设备类型
 *   2. 移动端自动将 sidebarOpened 设为 false
 *   3. 使用引用计数确保全局只注册一个 resize 监听器
 *   4. 防抖处理（100ms）避免 resize 事件频繁触发
 */

import { ref, onMounted, onUnmounted } from 'vue'
import { useStorage, useDebounceFn, createSharedComposable } from '@vueuse/core'
import { AppConfig } from '@/config/app'
import { prefixedKey } from '@/constants/storage'

/** 设备类型 */
export type DeviceType = 'desktop' | 'tablet' | 'mobile'

/** 响应式媒体查询 */
const mobileQuery = window.matchMedia('(max-width: 767px)')
const tabletQuery = window.matchMedia('(min-width: 768px) and (max-width: 1365px)')

/** resize 事件监听器引用计数，用于正确管理事件监听器的注册和注销 */
let resizeListenerCount = 0

/**
 * useDeviceDetection - 设备类型检测 Hook
 * @returns 设备检测相关的状态
 *
 * 使用 createSharedComposable 确保全局单例，
 * 多个组件调用 useDeviceDetection() 共享同一份状态。
 */
export const useDeviceDetection = createSharedComposable(() => {
  /** 是否为移动端设备 */
  const isMobile = ref(false)
  /** 是否为平板设备 */
  const isTablet = ref(false)
  /** 当前设备类型，持久化到 localStorage */
  const device = useStorage<DeviceType>(prefixedKey(AppConfig.deviceKey), 'desktop')

  /**
   * 检测当前设备类型
   * @description 根据 matchMedia 查询结果判断设备类型，移动端自动收起侧边栏
   */
  const checkDevice = () => {
    const newIsMobile = mobileQuery.matches
    const newIsTablet = tabletQuery.matches

    isMobile.value = newIsMobile
    isTablet.value = newIsTablet

    if (newIsMobile) {
      device.value = 'mobile'
    } else if (newIsTablet) {
      device.value = 'tablet'
    } else {
      device.value = 'desktop'
    }
  }

  /** 防抖的设备检测函数，避免 resize 事件频繁触发 */
  const debouncedCheckDevice = useDebounceFn(checkDevice, 100)

  /**
   * 组件挂载时注册 resize 监听器
   * @description 使用引用计数确保全局只注册一个 resize 监听器
   */
  onMounted(() => {
    if (resizeListenerCount === 0) {
      checkDevice()
      mobileQuery.addEventListener('change', debouncedCheckDevice)
    }
    resizeListenerCount++
  })

  /**
   * 组件卸载时注销 resize 监听器
   * @description 引用计数归零时移除监听器，避免内存泄漏
   */
  onUnmounted(() => {
    resizeListenerCount--
    if (resizeListenerCount === 0) {
      mobileQuery.removeEventListener('change', debouncedCheckDevice)
    }
  })

  return {
    isMobile,
    isTablet,
    device
  }
})
