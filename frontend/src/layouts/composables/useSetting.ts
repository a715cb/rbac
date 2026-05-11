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

export type LayoutType = 'side' | 'top' | 'mix' | 'left'
export type ThemeType = 'light' | 'dark' | 'realDark'
export type TabsType = 'smooth-tab' | 'smart-tab'
export type DeviceType = 'desktop' | 'tablet' | 'mobile'

export interface LayoutConfig {
  sidebarWidth: number
  collapsedWidth: number
  headerHeight: number
  tagsViewHeight: number
  leftNavWidth: number
  leftSubWidth: number
}

const layoutConfig: LayoutConfig = {
  sidebarWidth: 208,
  collapsedWidth: 48,
  headerHeight: 48,
  tagsViewHeight: 48,
  leftNavWidth: 80,
  leftSubWidth: 160
}

const breakpoints = {
  mobile: 768,
  tablet: 1024,
  desktop: 1366
}

const sidebarOpened = useStorage(prefixedKey(AppConfig.sidebarOpenedKey), true)
const isMobile = ref(false)
const isTablet = ref(false)
const device = useStorage<DeviceType>(prefixedKey(AppConfig.deviceKey), 'desktop')

const theme = useStorage<ThemeType>(prefixedKey(AppConfig.themeKey), 'light')
const prevTheme = useStorage<ThemeType>(prefixedKey(AppConfig.prevThemeKey), 'light')
const primaryColor = useStorage(prefixedKey(AppConfig.primaryColorKey), '#4073fa')
const layout = useStorage<LayoutType>(prefixedKey(AppConfig.layoutKey), 'side')

const showMultiTabs = useStorage(prefixedKey(AppConfig.showMultiTabsKey), true)
const fixedMultiTabs = useStorage(prefixedKey(AppConfig.fixedMultiTabsKey), true)
const showBreadcrumb = useStorage(prefixedKey(AppConfig.showBreadcrumbKey), true)
const showFooter = useStorage(prefixedKey(AppConfig.showFooterKey), true)
const splitMenu = useStorage(prefixedKey(AppConfig.splitMenuKey), false)

const tabsType = useStorage<TabsType>(prefixedKey(AppConfig.tabsTypeKey), 'smart-tab')
const openAnimation = useStorage(prefixedKey(AppConfig.openAnimationKey), false)
const animation = useStorage(prefixedKey(AppConfig.animationKey), 'fade-slide')
const openNProgress = useStorage(prefixedKey(AppConfig.nprogressKey), true)
const borderRadius = useStorage(prefixedKey(AppConfig.borderRadiusKey), 4)
const setPosition = useStorage<'header' | 'fixed'>(prefixedKey(AppConfig.setPositionKey), 'header')

let resizeListenerCount = 0

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

const debouncedCheckDevice = useDebounceFn(checkDevice, 100)

const applyTheme = (newTheme: ThemeType) => {
  const html = document.documentElement
  if (newTheme === 'realDark') {
    html.setAttribute('data-theme', 'dark')
  } else {
    html.setAttribute('data-theme', 'light')
  }
}

const applyPrimaryColor = (color: string) => {
  const style = document.documentElement.style
  style.setProperty('--ant-color-primary', color)
}

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

export const useSetting = createSharedComposable(() => {
  const router = useRouter()
  const userStore = useUserStore()
  const route = useRoute()

  const menus = computed<RouteMenu[]>(() => {
    const dynamicMenus = asRouteMenuList(userStore.menuRoutes)
    if (dynamicMenus.length === 0) {
      return [DASHBOARD_MENU]
    }
    return [DASHBOARD_MENU, ...dynamicMenus]
  })

  const isMixLayout = computed(() => ['mix', 'left'].includes(layout.value))

  const isLeftNotMobile = computed(() => layout.value === 'left' && !isMobile.value)

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

  const sideLength = computed(() => sideMenus.value.length)

  const sideWidth = computed(() => {
    const sw = sidebarOpened.value ? layoutConfig.sidebarWidth : layoutConfig.collapsedWidth
    if (isLeftNotMobile.value) return layoutConfig.leftSubWidth
    return sw
  })

  const stuffWidth = computed(() => {
    if (layout.value === 'left') {
      if (!sideLength.value) return layoutConfig.leftNavWidth
      return sideWidth.value + layoutConfig.leftNavWidth
    }
    return sideWidth.value
  })

  const navTheme = computed(() => {
    if (isMixLayout.value && theme.value !== 'realDark') return 'light'
    return theme.value === 'light' ? 'light' : 'dark'
  })

  const headTheme = computed(() => {
    if (['side', 'left'].includes(layout.value) && theme.value !== 'realDark') return 'light'
    return theme.value === 'light' ? 'light' : 'dark'
  })

  const reduceWidth = computed(() => {
    let reduce = sidebarOpened.value ? layoutConfig.sidebarWidth : layoutConfig.collapsedWidth
    if (layout.value === 'left') reduce = stuffWidth.value
    if (layout.value === 'top' || isMobile.value) reduce = 0
    if (!sideLength.value) {
      reduce = layout.value === 'left' ? layoutConfig.leftNavWidth : 0
    }
    return reduce
  })

  const tabsWidth = computed(() => {
    const width = reduceWidth.value
    return fixedMultiTabs.value ? `calc(100% - ${width}px)` : 'auto'
  })

  const siderLeft = computed(() => {
    const left = isLeftNotMobile.value ? `${layoutConfig.leftNavWidth}px` : '0px'
    const background = navTheme.value === 'light' ? '#ffffff' : undefined
    return { left, background }
  })

  const showSidebar = computed(() => {
    if (isMobile.value) return true
    if (layout.value === 'top') return false
    if (layout.value === 'mix' && !sideLength.value) return false
    return true
  })

  const showSideTrigger = computed(() => {
    return layout.value === 'mix' && !splitMenu.value
  })

  onMounted(() => {
    if (resizeListenerCount === 0) {
      checkDevice()
      window.addEventListener('resize', debouncedCheckDevice)
    }
    resizeListenerCount++
    applyTheme(theme.value)
    applyPrimaryColor(primaryColor.value)
  })

  onUnmounted(() => {
    resizeListenerCount--
    if (resizeListenerCount === 0) {
      window.removeEventListener('resize', debouncedCheckDevice)
    }
  })

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

  const toggleSidebar = () => {
    sidebarOpened.value = !sidebarOpened.value
  }

  const changeTheme = (newTheme: ThemeType) => {
    if (theme.value !== 'realDark') {
      prevTheme.value = theme.value
    }
    theme.value = newTheme
    applyTheme(newTheme)
  }

  const changePrimaryColor = (color: string) => {
    primaryColor.value = color
    applyPrimaryColor(color)
  }

  const changeLayout = (newLayout: LayoutType) => {
    layout.value = newLayout
  }

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
