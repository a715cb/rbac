/**
 * @文件: useAppearance.ts
 * @用途: 主题外观管理 Hook
 * @描述: 管理应用的主题风格、主题色等外观配置。包括亮/暗/深暗主题切换、
 *        主题色自定义、导航栏和头部的主题派生计算。
 *        使用 createSharedComposable 确保全局单例，
 *        主题和主题色通过 useStorage 持久化到 localStorage。
 * @核心逻辑:
 *   1. 主题切换时记住之前的主题（非 realDark），用于暗黑模式恢复
 *   2. applyTheme 将 data-theme 属性设置到 html 元素
 *   3. applyPrimaryColor 设置 CSS 变量 --ant-color-primary
 *   4. navTheme/headTheme 根据布局模式和当前主题计算导航栏/头部主题
 */

import { computed } from 'vue'
import { useStorage, createSharedComposable } from '@vueuse/core'
import { AppConfig } from '@/config/app'
import { prefixedKey } from '@/constants/storage'
import { useMenuState } from './useMenuState'
import type { LayoutType } from './useSetting'

/** 主题风格类型 */
export type ThemeType = 'light' | 'dark' | 'realDark'

/** 标签页样式类型 */
export type TabsType = 'smooth-tab' | 'smart-tab'

/**
 * useAppearance - 主题外观管理 Hook
 * @returns 主题外观相关的状态和操作方法
 *
 * 使用 createSharedComposable 确保全局单例。
 */
export const useAppearance = createSharedComposable(() => {
  /** 当前主题风格 */
  const theme = useStorage<ThemeType>(prefixedKey(AppConfig.themeKey), 'light')
  /** 切换暗黑模式前的主题，用于切换回非暗黑模式时恢复 */
  const prevTheme = useStorage<ThemeType>(prefixedKey(AppConfig.prevThemeKey), 'light')
  /** 主题色 */
  const primaryColor = useStorage(prefixedKey(AppConfig.primaryColorKey), '#4073fa')
  /** 当前布局模式，与 useSetting 共享同一 storage key */
  const layout = useStorage<LayoutType>(prefixedKey(AppConfig.layoutKey), 'side')

  const { isMixLayout } = useMenuState()

  /**
   * 应用主题到 DOM
   * @param newTheme - 新的主题值
   * @description 将 data-theme 属性设置到 html 元素，
   *              realDark 和 dark 均映射为 'dark'，light 映射为 'light'
   */
  const applyTheme = (newTheme: ThemeType) => {
    const html = document.documentElement
    if (newTheme === 'realDark') {
      html.setAttribute('data-theme', 'dark')
    } else {
      html.setAttribute('data-theme', 'light')
    }
  }

  /**
   * 应用主题色到 DOM
   * @param color - 主题色值
   * @description 设置 CSS 变量 --ant-color-primary，供全局样式引用
   */
  const applyPrimaryColor = (color: string) => {
    const style = document.documentElement.style
    style.setProperty('--ant-color-primary', color)
  }

  /**
   * 切换主题风格
   * @param newTheme - 新的主题值
   * @description 记住切换前的主题（用于暗黑模式切换回恢复），
   *              更新 theme 状态并应用到 DOM
   */
  const changeTheme = (newTheme: ThemeType) => {
    if (theme.value !== 'realDark') {
      prevTheme.value = theme.value
    }
    theme.value = newTheme
    applyTheme(newTheme)
  }

  /**
   * 切换主题色
   * @param color - 新的主题色值
   */
  const changePrimaryColor = (color: string) => {
    primaryColor.value = color
    applyPrimaryColor(color)
  }

  /**
   * 导航栏主题色
   * @description 混合布局下非暗黑模式强制使用 light 主题，
   *              其他模式根据 theme 值映射
   */
  const navTheme = computed(() => {
    if (isMixLayout.value && theme.value !== 'realDark') return 'light'
    return theme.value === 'light' ? 'light' : 'dark'
  })

  /**
   * 头部主题色
   * @description side/left 布局下非暗黑模式使用 light 主题，
   *              其他模式根据 theme 值映射
   */
  const headTheme = computed(() => {
    if (['side', 'left'].includes(layout.value) && theme.value !== 'realDark') return 'light'
    return theme.value === 'light' ? 'light' : 'dark'
  })

  return {
    theme,
    prevTheme,
    primaryColor,
    applyTheme,
    applyPrimaryColor,
    changeTheme,
    changePrimaryColor,
    navTheme,
    headTheme
  }
})
