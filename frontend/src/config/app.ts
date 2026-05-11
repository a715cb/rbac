export const AppConfig = {
  // 应用标题
  title: import.meta.env.VITE_APP_TITLE || 'RBAC 权限管理系统',

  // API 基础路径
  baseApi: import.meta.env.VITE_APP_BASE_API || '/api',

  // API 基础 URL
  apiBaseUrl: import.meta.env.VITE_APP_API_BASE_URL || '',

  // 默认分页大小
  defaultPageSize: 10,

  // 分页大小选项
  pageSizeOptions: [10, 20, 50, 100],

  // 缓存前缀
  storagePrefix: import.meta.env.VITE_APP_STORAGE_PREFIX || '',

  // Token 存储键名
  tokenKey: 'token',

  // Refresh Token 存储键名
  refreshTokenKey: 'refresh_token',

  // Token 过期时间存储键名
  tokenExpiresKey: 'token_expires',

  // 用户信息存储键名
  userInfoKey: 'user_info',

  // 菜单数据存储键名
  menusKey: 'menus',

  // NProgress 开关存储键名
  nprogressKey: 'open_nprogress',

  // Tabs 列表存储键名
  tabsListKey: 'tabs_list',

  // Tabs 当前激活存储键名
  tabsActiveKey: 'tabs_active',

  // 记住用户名存储键名
  rememberUsernameKey: 'remember_username',

  // 侧边栏展开状态存储键名
  sidebarOpenedKey: 'sidebar_opened',

  // 设备类型存储键名
  deviceKey: 'device',

  // 主题风格存储键名
  themeKey: 'theme',

  // 上次主题存储键名
  prevThemeKey: 'prev_theme',

  // 主题色存储键名
  primaryColorKey: 'primary_color',

  // 布局模式存储键名
  layoutKey: 'layout',

  // 多标签页显示存储键名
  showMultiTabsKey: 'show_multi_tabs',

  // 固定多标签页存储键名
  fixedMultiTabsKey: 'fixed_multi_tabs',

  // 面包屑显示存储键名
  showBreadcrumbKey: 'show_breadcrumb',

  // 底部栏显示存储键名
  showFooterKey: 'show_footer',

  // 分割菜单存储键名
  splitMenuKey: 'split_menu',

  // 标签页类型存储键名
  tabsTypeKey: 'tabs_type',

  // 路由动画开关存储键名
  openAnimationKey: 'open_animation',

  // 动画类型存储键名
  animationKey: 'animation',

  // 圆角大小存储键名
  borderRadiusKey: 'border_radius',

  // 设置位置存储键名
  setPositionKey: 'set_position',

  // 请求超时时间（毫秒）
  requestTimeout: 30000,

  // Token 过期时长（秒）
  tokenExpirySeconds: 2 * 60 * 60
} as const

export interface RouterConfig {
  viewComponentPrefix: string
  errorComponentPath: string
}

export function getRouterConfig(): RouterConfig {
  return {
    viewComponentPrefix: import.meta.env.VITE_APP_VIEW_COMPONENT_PREFIX || '@/pages/',
    errorComponentPath: import.meta.env.VITE_APP_ERROR_COMPONENT_PATH || '@/pages/error/404.vue'
  }
}

export default {
  appName: AppConfig.title,
  version: '1.0.0',
  copyright: {
    company: 'RBAC 权限管理系统',
    link: '',
    icp: ''
  }
}
