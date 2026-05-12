/**
 * @文件: options.ts
 * @用途: 设置抽屉配置选项数据
 * @描述: 定义设置抽屉中所有可配置项的选项数据，包括主题风格、主题色、
 *        导航模式、路由动画、标签页类型和设置入口位置。
 *        每个选项包含 label（显示文本）、value（配置值）和 icon（图标标识）。
 */

/** 应用状态类型，约束主题和布局的可选值 */
export type AppState = {
  /** 主题风格：light=亮色, dark=暗色, realDark=暗黑 */
  theme: 'light' | 'dark' | 'realDark'
  /** 布局模式：side=侧边, top=顶部, mix=混合, left=左侧混合 */
  layout: 'side' | 'top' | 'mix' | 'left'
}

/** 主题风格选项：亮色、暗色、暗黑 */
export const themeStyle = [
  {
    label: '亮色主题风格',
    icon: 'svg:theme-light',
    value: 'light' as AppState['theme']
  },
  {
    label: '暗色主题风格',
    icon: 'svg:theme-dark',
    value: 'dark' as AppState['theme']
  },
  {
    label: '暗黑模式',
    icon: 'svg:theme-real-dark',
    value: 'realDark' as AppState['theme']
  }
]

/** 主题色选项：8 种预设颜色 */
export const themeColors = [
  {
    key: '极客蓝（默认）',
    color: '#4073fa'
  },
  {
    key: '拂晓蓝',
    color: '#1677ff'
  },
  {
    key: '薄暮',
    color: '#f5222d'
  },
  {
    key: '火山',
    color: '#fa541c'
  },
  {
    key: '日暮',
    color: '#ff9900'
  },
  {
    key: '苍岭绿',
    color: '#27b59c'
  },
  {
    key: '海湾蓝',
    color: '#536DFE'
  },
  {
    key: '黛紫',
    color: '#955ce6'
  }
]

/** 导航模式（布局方式）选项 */
export const layouts = [
  {
    label: '侧边菜单布局',
    icon: 'svg:layout-side',
    value: 'side'
  },
  {
    label: '顶部菜单布局',
    icon: 'svg:layout-top',
    value: 'top'
  },
  {
    label: '混合布局',
    icon: 'svg:layout-mix',
    value: 'mix'
  },
  {
    label: '左侧混合布局',
    icon: 'svg:layout-left',
    value: 'left'
  }
]

/** 路由动画选项 */
export const animation = [
  {
    value: 'fade-slide',
    label: '滑动消退'
  },
  {
    value: 'fade-bottom',
    label: '底部消退'
  },
  {
    value: 'fade-top',
    label: '顶部消退'
  },
  {
    value: 'fade-scale',
    label: '缩小渐变'
  },
  {
    value: 'zoom-fade',
    label: '放大渐变'
  }
]

/** 多标签页类型选项 */
export const tabsType = [
  {
    value: 'smooth-tab',
    label: '圆滑'
  },
  {
    value: 'smart-tab',
    label: '灵动'
  }
]

/** 主题设置入口位置选项 */
export const setPosiList = [
  {
    value: 'header',
    label: '顶栏'
  },
  {
    value: 'fixed',
    label: '固定'
  }
]
