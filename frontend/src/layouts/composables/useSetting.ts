/**
 * @文件: useSetting.ts
 * @用途: 全局布局设置状态管理 Hook
 * @描述: 集中管理应用布局的所有配置状态，包括主题、布局模式、侧边栏、标签页、
 *        动画等设置项。使用 createSharedComposable 确保全局单例，
 *        所有设置通过 useStorage 持久化到 localStorage。
 *        同时管理窗口尺寸监听、主题应用和菜单数据计算。
 * @核心逻辑:
 *   1. 使用 useStorage 持久化所有设置项到 localStorage
 *   2. 使用 createSharedComposable 确保全局单例，避免重复初始化
 *   3. 监听窗口 resize 事件，自动检测设备类型（mobile/tablet/desktop）
 *   4. 根据布局模式和路由状态计算侧边栏菜单数据
 *   5. 通过 settingMap 统一管理设置项的 ref 和副作用函数
 *   6. applyTheme/applyPrimaryColor 将设置应用到 DOM
 */

import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStorage, useDebounceFn, createSharedComposable } from '@vueuse/core'
import { useUserStore } from '@/stores/user'
import { asRouteMenuList, getMatchedMenuPath } from '@/layouts/components/menuUtils'
import type { RouteMenu } from '@/layouts/components/menuUtils'
import { AppConfig } from '@/config/app'
import { prefixedKey } from '@/constants/storage'

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

/** 布局模式类型 */
export type LayoutType = 'side' | 'top' | 'mix' | 'left'

/** 主题风格类型 */
export type ThemeType = 'light' | 'dark' | 'realDark'

/** 标签页样式类型 */
export type TabsType = 'smooth-tab' | 'smart-tab'

/** 设备类型 */
export type DeviceType = 'desktop' | 'tablet' | 'mobile'

/** 布局尺寸配置 */
export interface LayoutConfig {
  /** 侧边栏展开宽度 */
  sidebarWidth: number
  /** 侧边栏折叠宽度 */
  collapsedWidth: number
  /** 头部高度 */
  headerHeight: number
  /** 标签页高度 */
  tagsViewHeight: number
  /** 左侧导航栏宽度（left 布局） */
  leftNavWidth: number
  /** 左侧子菜单宽度（left 布局） */
  leftSubWidth: number
}

/** 布局尺寸常量配置 */
const layoutConfig: LayoutConfig = {
  sidebarWidth: 208,
  collapsedWidth: 48,
  headerHeight: 48,
  tagsViewHeight: 48,
  leftNavWidth: 80,
  leftSubWidth: 160
}

/** 响应式断点配置 */
const breakpoints = {
  mobile: 768,
  tablet: 1024,
  desktop: 1366
}

// ==================== 持久化状态定义 ====================
// 所有设置项通过 useStorage 持久化到 localStorage，页面刷新后自动恢复

/** 侧边栏是否展开 */
const sidebarOpened = useStorage(prefixedKey(AppConfig.sidebarOpenedKey), true)
/** 是否为移动端设备 */
const isMobile = ref(false)
/** 是否为平板设备 */
const isTablet = ref(false)
/** 当前设备类型 */
const device = useStorage<DeviceType>(prefixedKey(AppConfig.deviceKey), 'desktop')

/** 当前主题风格 */
const theme = useStorage<ThemeType>(prefixedKey(AppConfig.themeKey), 'light')
/** 切换暗黑模式前的主题，用于切换回非暗黑模式时恢复 */
const prevTheme = useStorage<ThemeType>(prefixedKey(AppConfig.prevThemeKey), 'light')
/** 主题色 */
const primaryColor = useStorage(prefixedKey(AppConfig.primaryColorKey), '#4073fa')
/** 布局模式 */
const layout = useStorage<LayoutType>(prefixedKey(AppConfig.layoutKey), 'side')

/** 是否显示多标签页 */
const showMultiTabs = useStorage(prefixedKey(AppConfig.showMultiTabsKey), true)
/** 是否固定多标签页 */
const fixedMultiTabs = useStorage(prefixedKey(AppConfig.fixedMultiTabsKey), true)
/** 是否显示面包屑 */
const showBreadcrumb = useStorage(prefixedKey(AppConfig.showBreadcrumbKey), true)
/** 是否显示底部栏 */
const showFooter = useStorage(prefixedKey(AppConfig.showFooterKey), true)
/** 是否分割菜单（mix/left 布局下将一级菜单放顶部，子菜单放侧边栏） */
const splitMenu = useStorage(prefixedKey(AppConfig.splitMenuKey), false)

/** 标签页样式类型 */
const tabsType = useStorage<TabsType>(prefixedKey(AppConfig.tabsTypeKey), 'smart-tab')
/** 是否开启路由动画 */
const openAnimation = useStorage(prefixedKey(AppConfig.openAnimationKey), false)
/** 路由动画类型 */
const animation = useStorage(prefixedKey(AppConfig.animationKey), 'fade-slide')
/** 是否开启页面加载进度条 */
const openNProgress = useStorage(prefixedKey(AppConfig.nprogressKey), true)
/** 圆角大小（px） */
const borderRadius = useStorage(prefixedKey(AppConfig.borderRadiusKey), 4)
/** 设置入口位置（header=头部按钮, fixed=右侧固定把手） */
const setPosition = useStorage<'header' | 'fixed'>(prefixedKey(AppConfig.setPositionKey), 'header')

/** resize 事件监听器引用计数，用于正确管理事件监听器的注册和注销 */
let resizeListenerCount = 0

/**
 * 检测当前设备类型
 * @description 根据窗口宽度判断设备类型，移动端自动收起侧边栏
 */
const checkDevice = () => {
  const width = window.innerWidth
  const newIsMobile = width < breakpoints.mobile
  const newIsTablet = width >= breakpoints.mobile && width < breakpoints.desktop

  isMobile.value = newIsMobile
  isTablet.value = newIsTablet

  if (newIsMobile) {
    device.value = 'mobile'
    sidebarOpened.value = false
  } else if (newIsTablet) {
    device.value = 'tablet'
  } else {
    device.value = 'desktop'
  }
}

/** 防抖的设备检测函数，避免 resize 事件频繁触发 */
const debouncedCheckDevice = useDebounceFn(checkDevice, 100)

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

/** 设置项键名联合类型，用于 changeSetting 方法的类型约束 */
type SettingKey =
  | 'sidebarOpened'
  | 'theme'
  | 'primaryColor'
  | 'layout'
  | 'showMultiTabs'
  | 'fixedMultiTabs'
  | 'showBreadcrumb'
  | 'showFooter'
  | 'splitMenu'
  | 'tabsType'
  | 'openAnimation'
  | 'animation'
  | 'openNProgress'
  | 'borderRadius'
  | 'setPosition'

/**
 * 设置项映射表
 * @description 将每个设置项的 ref 和副作用函数（apply）统一管理，
 *              changeSetting 方法通过查找此表来更新值并执行副作用
 */
const settingMap: Record<SettingKey, { ref: any; apply?: (val: any) => void }> = {
  sidebarOpened: { ref: sidebarOpened },
  theme: { ref: theme, apply: applyTheme },
  primaryColor: { ref: primaryColor, apply: applyPrimaryColor },
  layout: { ref: layout },
  showMultiTabs: { ref: showMultiTabs },
  fixedMultiTabs: { ref: fixedMultiTabs },
  showBreadcrumb: { ref: showBreadcrumb },
  showFooter: { ref: showFooter },
  splitMenu: { ref: splitMenu },
  tabsType: { ref: tabsType },
  openAnimation: { ref: openAnimation },
  animation: { ref: animation },
  openNProgress: { ref: openNProgress },
  borderRadius: { ref: borderRadius },
  setPosition: { ref: setPosition }
}

/**
 * useSetting - 全局布局设置状态管理 Hook
 * @returns 所有布局设置的状态和操作方法
 *
 * 使用 createSharedComposable 确保全局单例，
 * 多个组件调用 useSetting() 共享同一份状态。
 *
 * 状态说明：
 * - menus: 菜单数据（仪表盘 + 动态菜单）
 * - sidebarOpened/isMobile/isTablet/device: 设备和侧边栏状态
 * - theme/prevTheme/primaryColor/layout: 核心布局配置
 * - showMultiTabs/fixedMultiTabs/showBreadcrumb/showFooter/splitMenu: 功能开关
 * - tabsType/openAnimation/animation/openNProgress/borderRadius: 样式配置
 * - sideWidth/stuffWidth/navTheme/headTheme/reduceWidth/tabsWidth/siderLeft: 计算属性
 * - showSidebar/sideMenus/sideLength/isMixLayout/isLeftNotMobile: 布局计算
 *
 * 方法说明：
 * - changeSetting: 通用设置变更方法
 * - toggleSidebar: 切换侧边栏展开/折叠
 * - changeTheme: 切换主题风格
 * - changePrimaryColor: 切换主题色
 * - changeLayout: 切换布局模式
 * - routerTo: 路由跳转（从菜单点击触发）
 */
export const useSetting = createSharedComposable(() => {
  const router = useRouter()
  const userStore = useUserStore()
  const route = useRoute()

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
  const isLeftNotMobile = computed(() => layout.value === 'left' && !isMobile.value)

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
  const sideLength = computed(() => sideMenus.value.length)

  /**
   * 侧边栏宽度
   * @description 根据展开/折叠状态和布局模式计算：
   * - left 布局：使用 leftSubWidth
   * - 其他：展开时使用 sidebarWidth，折叠时使用 collapsedWidth
   */
  const sideWidth = computed(() => {
    const sw = sidebarOpened.value ? layoutConfig.sidebarWidth : layoutConfig.collapsedWidth
    if (isLeftNotMobile.value) return layoutConfig.leftSubWidth
    return sw
  })

  /**
   * 侧边栏占位宽度
   * @description 用于在文档流中占据空间，防止内容被固定定位的侧边栏遮挡：
   * - left 布局：leftNavWidth + sideWidth（双栏）
   * - 其他：sideWidth（单栏）
   */
  const stuffWidth = computed(() => {
    if (layout.value === 'left') {
      if (!sideLength.value) return layoutConfig.leftNavWidth
      return sideWidth.value + layoutConfig.leftNavWidth
    }
    return sideWidth.value
  })

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

  /**
   * 内容区需要扣除的宽度
   * @description 用于计算头部和标签页的宽度：
   * - left 布局：使用 stuffWidth
   * - top 布局或移动端：0（全宽）
   * - 无子菜单时：left 布局扣除 leftNavWidth，其他扣除 0
   */
  const reduceWidth = computed(() => {
    let reduce = sidebarOpened.value ? layoutConfig.sidebarWidth : layoutConfig.collapsedWidth
    if (layout.value === 'left') reduce = stuffWidth.value
    if (layout.value === 'top' || isMobile.value) reduce = 0
    if (!sideLength.value) {
      reduce = layout.value === 'left' ? layoutConfig.leftNavWidth : 0
    }
    return reduce
  })

  /**
   * 标签页容器宽度
   * @description 固定标签页时使用 calc 扣除侧边栏宽度，非固定时自适应
   */
  const tabsWidth = computed(() => {
    const width = reduceWidth.value
    return fixedMultiTabs.value ? `calc(100% - ${width}px)` : 'auto'
  })

  /**
   * 侧边栏 left 偏移
   * @description left 布局下侧边栏需要偏移 leftNavWidth，
   *              亮色主题下设置白色背景
   */
  const siderLeft = computed(() => {
    const left = isLeftNotMobile.value ? `${layoutConfig.leftNavWidth}px` : '0px'
    const background = navTheme.value === 'light' ? '#ffffff' : undefined
    return { left, background }
  })

  /**
   * 是否显示侧边栏
   * @description 移动端始终显示（抽屉模式），
   *              top 布局不显示，mix 布局无子菜单时不显示
   */
  const showSidebar = computed(() => {
    if (isMobile.value) return true
    if (layout.value === 'top') return false
    if (layout.value === 'mix' && !sideLength.value) return false
    return true
  })

  /**
   * 是否显示侧边栏折叠触发器
   * @description 仅 mix 布局未开启 splitMenu 时显示
   */
  const showSideTrigger = computed(() => {
    return layout.value === 'mix' && !splitMenu.value
  })

  // ==================== 生命周期管理 ====================

  /**
   * 组件挂载时注册 resize 监听器并应用初始主题
   * @description 使用引用计数确保全局只注册一个 resize 监听器
   */
  onMounted(() => {
    if (resizeListenerCount === 0) {
      checkDevice()
      window.addEventListener('resize', debouncedCheckDevice)
    }
    resizeListenerCount++
    applyTheme(theme.value)
    applyPrimaryColor(primaryColor.value)
  })

  /**
   * 组件卸载时注销 resize 监听器
   * @description 引用计数归零时移除监听器，避免内存泄漏
   */
  onUnmounted(() => {
    resizeListenerCount--
    if (resizeListenerCount === 0) {
      window.removeEventListener('resize', debouncedCheckDevice)
    }
  })

  // ==================== 设置变更方法 ====================

  /**
   * 通用设置变更方法
   * @param key - 设置项键名
   * @param value - 新值
   * @description 通过 settingMap 查找对应的 ref 和副作用函数，
   *              更新值并执行副作用（如 applyTheme、applyPrimaryColor）
   */
  const changeSetting = (key: SettingKey, value: any) => {
    if (key === 'theme' && theme.value !== 'realDark') {
      prevTheme.value = theme.value
    }
    const entry = settingMap[key]
    if (entry) {
      entry.ref.value = value
      entry.apply?.(value)
    }
  }

  /** 切换侧边栏展开/折叠状态 */
  const toggleSidebar = () => {
    sidebarOpened.value = !sidebarOpened.value
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
   * 切换布局模式
   * @param newLayout - 新的布局模式
   */
  const changeLayout = (newLayout: LayoutType) => {
    layout.value = newLayout
  }

  /**
   * 路由跳转（从菜单点击触发）
   * @param data - 菜单点击数据，包含 key 或 path 属性
   * @description 仅处理以 '/' 开头的路径，执行 router.push 跳转
   */
  const routerTo = (data: Record<string, any>) => {
    const path = data.key || data.path
    if (path && String(path).startsWith('/')) {
      router.push({ path: String(path) })
    }
  }

  return {
    menus,
    sidebarOpened,
    isMobile,
    isTablet,
    device,
    theme,
    prevTheme,
    primaryColor,
    layout,
    showMultiTabs,
    fixedMultiTabs,
    showBreadcrumb,
    showFooter,
    splitMenu,
    tabsType,
    openAnimation,
    animation,
    openNProgress,
    borderRadius,
    setPosition,
    sideWidth,
    stuffWidth,
    navTheme,
    headTheme,
    reduceWidth,
    tabsWidth,
    siderLeft,
    showSidebar,
    sideMenus,
    sideLength,
    isMixLayout,
    isLeftNotMobile,
    showSideTrigger,
    changeSetting,
    toggleSidebar,
    changeTheme,
    changePrimaryColor,
    changeLayout,
    routerTo,
    layoutConfig
  }
})
