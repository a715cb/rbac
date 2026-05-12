/**
 * @文件: Menu/index.tsx
 * @用途: 基于路由配置的动态菜单组件
 * @描述: 根据 Vue Router 的路由配置动态渲染 Ant Design Menu 组件，支持多级菜单、菜单项分组、
 *        图标渲染、主题切换、折叠展开等功能的可视化菜单组件。
 * @核心逻辑:
 *   1. 路由菜单数据处理 - 将路由配置转换为菜单可见性判断
 *   2. 菜单渲染 - 递归渲染 SubMenu（子菜单）和 MenuItem（菜单项）
 *   3. 菜单状态管理 - 处理选中、展开、折叠等状态
 *   4. 路由联动 - 监听路由变化自动高亮当前菜单
 *
 * 关键函数说明：
 *
 * isMenuVisible(item: RouteMenu): boolean
 *   参数：item - 路由菜单项对象
 *   返回值：boolean - 表示菜单是否应该显示
 *   功能：根据 hidden、visible、status、meta.hidden 等字段判断菜单是否对用户可见
 *
 * renderMenu(item: RouteMenu): VNode | null
 *   参数：item - 路由菜单项对象
 *   返回值：VNode 或 null - 渲染的菜单节点
 *   功能：根据菜单项是否有子菜单且未隐藏子菜单，决定渲染为 SubMenu 或 MenuItem
 *
 * renderSubMenu(item: RouteMenu): VNode
 *   参数：item - 路由菜单项对象（包含 children）
 *   返回值：VNode - SubMenu 虚拟节点
 *   功能：渲染带有多层嵌套子菜单的 SubMenu 节点
 *
 * renderMenuItem(item: RouteMenu): VNode
 *   参数：item - 叶子菜单项对象（无子菜单或 hideChildrenInMenu 为 true）
 *   返回值：VNode - MenuItem 虚拟节点
 *   功能：渲染单个菜单项，使用 router-link 作为链接组件
 *
 * renderIcon(icon: string | undefined): VNode
 *   参数：icon - 图标类型字符串
 *   返回值：VNode - Icon 组件虚拟节点
 *   功能：根据图标字符串渲染对应的 Icon 组件，默认使用 appstore-outlined
 *
 * renderTitle(title: string | undefined): VNode
 *   参数：title - 菜单标题文本
 *   返回值：VNode - span 包裹的文本节点
 *   功能：渲染菜单标题，统一使用 span 包裹便于样式控制
 *
 * 复杂逻辑说明：
 *
 * 1. activateMenu() - 菜单激活逻辑
 *    处理流程：
 *    - 从 route.matched 获取匹配的路由链
 *    - 如果当前路由 hidden=true 且有多级匹配，弹出最后一级作为选中项
 *    - 否则直接使用最后一级匹配的路径作为选中项
 *    - 支持 meta.active_key 自定义高亮路径
 *    - inline 模式下收集所有祖先路由的 path 作为展开键
 *    - 折叠时缓存当前展开键，展开时恢复缓存
 *
 * 2. onOpenChange(openKeys: Key[]) - 展开键变化处理
 *    处理逻辑：
 *    - horizontal 模式下直接更新展开键
 *    - 其他模式下找到最新打开的键
 *    - 如果新键是顶级菜单键，则只保留它；否则保留所有
 *    - 支持 namePath 回退到指定展开状态
 *
 * 3. 菜单可见性判断逻辑
 *    - hidden=true → 不显示（完全隐藏）
 *    - visible !== 1 → 不显示（业务状态控制）
 *    - status !== 1 → 不显示（禁用状态）
 *    - meta.hidden=true → 不显示（路由元信息控制）
 *
 * 性能优化点：
 * 1. 使用 computed 缓存 rootSubmenuKeys，避免重复计算
 * 2. 使用 reactive 包裹 menuProps 保证响应式更新
 * 3. menuItems 通过 map 生成，返回 JSX 函数避免不必要的重渲染
 * 4. 折叠时缓存 openKeys 到 cachedOpenKeys，避免状态丢失
 *
 * 注意事项：
 * 1. 菜单数据由父组件通过 props.menus 传入，组件本身不管理数据源
 * 2. 主题色由 props.theme 控制，支持 dark/light
 * 3. 折叠状态由父组件通过 props.collapsed 控制
 * 4. emit select 和 click 事件供父组件监听菜单交互
 */
import {
  defineComponent,
  resolveComponent,
  reactive,
  watch,
  onMounted,
  computed,
  type PropType
} from 'vue'
import { useRoute } from 'vue-router'
import Menu from 'ant-design-vue/es/menu'
import type { MenuTheme, MenuMode } from 'ant-design-vue/es/menu'
import type { MenuInfo } from 'ant-design-vue/es/menu/src/interface'
import Icon from '@/components/Icon'
import { isMenuVisible, getMenuIcon, getMenuTitle } from '../menuUtils'
import type { RouteMenu } from '../menuUtils'

const { Item: MenuItem, SubMenu } = Menu

type Key = string | number

/**
 * 渲染单个菜单项（叶子节点或父节点）
 *
 * @param item - 路由菜单项
 * @returns 渲染的菜单节点，或 null（不可见时）
 *
 * 渲染决策：
 * - 有子菜单且未隐藏子菜单 → 渲染为 SubMenu
 * - 否则 → 渲染为 MenuItem
 */
const renderMenu = (item: RouteMenu) => {
  if (item && isMenuVisible(item)) {
    const bool = item.children && !item.hideChildrenInMenu
    return bool ? renderSubMenu(item) : renderMenuItem(item)
  }
  return null
}

const renderSubMenu = (item: RouteMenu) => {
  return (
    <SubMenu
      popupClassName="s-menu-popup"
      key={item.path}
      title={
        <span>
          {renderIcon(getMenuIcon(item))}
          {renderTitle(getMenuTitle(item))}
        </span>
      }
    >
      {() => !item.hideChildrenInMenu && item.children?.map((cd) => renderMenu(cd))}
    </SubMenu>
  )
}

const renderMenuItem = (item: RouteMenu) => {
  const CustomTag = 'router-link'
  const props = { to: item.path }
  const Widget = resolveComponent(CustomTag) as any
  return (
    <MenuItem key={item.path} icon={renderIcon(getMenuIcon(item))}>
      {() => <Widget {...props}>{renderTitle(getMenuTitle(item))}</Widget>}
    </MenuItem>
  )
}

const renderIcon = (icon: string) => {
  return <Icon type={icon} />
}

const renderTitle = (title: string) => {
  return <span>{title}</span>
}

/**
 * RouteMenus - 动态菜单组件
 *
 * @remarks
 * 接收路由配置数组，渲染为 Ant Design Menu 组件。
 * 支持多种菜单模式（horizontal/inline/vertical）、主题切换（dark/light），
 * 自动响应路由变化更新菜单高亮状态。
 */
const RouteMenus = defineComponent({
  name: 'RouteMenus',
  props: {
    /**
     * 菜单数据源，通常来自路由配置
     */
    menus: {
      type: Array as PropType<RouteMenu[]>,
      required: true
    },
    /**
     * 菜单主题色
     * @defaultValue 'dark'
     */
    theme: {
      type: String as PropType<MenuTheme>,
      default: 'dark'
    },
    /**
     * 菜单模式
     * - horizontal: 水平菜单（顶部导航）
     * - inline: 内联菜单（侧边栏，默认）
     * - vertical: 垂直菜单
     * @defaultValue 'inline'
     */
    mode: {
      type: String as PropType<MenuMode>,
      default: 'inline'
    },
    /**
     * 菜单是否折叠状态
     * 折叠时仅显示图标，展开时显示图标+文字
     * @defaultValue false
     */
    collapsed: {
      type: Boolean,
      default: false
    }
  },
  emits: ['select', 'click'],
  setup(props, { emit }) {
    /**
     * 菜单状态管理
     * openKeys - 当前展开的 SubMenu 键数组
     * selectedKeys - 当前选中的 MenuItem 键数组
     * cachedOpenKeys - 折叠时缓存的展开键，用于展开时恢复
     * cachedSelectedKeys - 折叠时缓存的选中键
     */
    const state = reactive({
      openKeys: [] as Array<Key>,
      selectedKeys: [] as Array<Key>,
      cachedOpenKeys: [] as Array<Key>,
      cachedSelectedKeys: [] as Array<Key>
    })

    /**
     * 顶级菜单键列表
     * 用于判断打开的键是否为顶级菜单，决定展开行为
     */
    const rootSubmenuKeys = computed(() => {
      const keys: Array<Key> = []
      props.menus.forEach((item) => keys.push(item.path))
      return keys
    })

    const route = useRoute()

    /**
     * 处理 SubMenu 展开/折叠变化
     *
     * @param openKeys - 新的展开键数组
     *
     * 处理逻辑：
     * 1. horizontal 模式直接使用新键
     * 2. 其他模式下找最新打开的键
     * 3. 如果是顶级菜单键则只保留它，否则保留所有
     * 4. 支持 namePath 回退到指定展开状态
     */
    const onOpenChange = (openKeys: Key[]) => {
      if (!openKeys.length) return

      const namePath = route.meta.namePath as Array<string>
      if (props.mode === 'horizontal') {
        state.openKeys = openKeys
        return
      }
      const latestOpenKey = openKeys.find((key) => !state.openKeys.includes(key)) as string
      if (!rootSubmenuKeys.value.includes(latestOpenKey)) {
        state.openKeys = openKeys
      } else {
        state.openKeys = latestOpenKey ? [latestOpenKey] : []
      }

      if (namePath && namePath.includes(latestOpenKey)) {
        state.openKeys = [...namePath].reverse()
      }
    }

    /**
     * 根据当前路由激活对应菜单项
     *
     * 处理流程：
     * 1. 获取路由匹配的组件链
     * 2. 处理隐藏路由：如果是隐藏的多级路由，移除最后一级
     * 3. 设置选中键：使用最后匹配的路由路径
     * 4. 支持自定义高亮键（meta.active_key）
     * 5. inline 模式下收集所有祖先路径作为展开键
     * 6. 折叠时缓存展开键，展开时恢复
     */
    const activateMenu = () => {
      const routes = route.matched.concat()
      const { hidden }: { hidden?: boolean } = route.meta as any
      let { active_key }: { active_key?: string } = route.meta as any
      if (routes.length >= 2 && hidden) {
        routes.pop()
        state.selectedKeys = [routes[routes.length - 1].path]
      } else {
        state.selectedKeys = [(routes as Array<any>).pop().path]
      }
      if (active_key) {
        if (!active_key.startsWith('/')) {
          active_key = `/${active_key}`
        }
        state.selectedKeys.push(active_key)
      }
      const openKeys: Array<Key> = []
      if (props.mode === 'inline') {
        routes.forEach((item) => {
          item.path && openKeys.push(item.path)
        })
      }
      props.collapsed ? (state.cachedOpenKeys = openKeys) : (state.openKeys = openKeys)
    }

    /**
     * 监听路由路径变化，实时更新菜单高亮状态
     */
    watch(
      () => route.path,
      () => {
        activateMenu()
      },
      { immediate: true }
    )

    /**
     * 监听折叠状态变化
     *
     * 折叠时：缓存当前展开键，清空展开键
     * 展开时：从缓存恢复展开键
     */
    watch(
      () => props.collapsed,
      (val) => {
        if (val) {
          state.cachedOpenKeys = state.openKeys.concat()
          state.openKeys = []
        } else {
          state.openKeys = state.cachedOpenKeys
        }
      }
    )

    onMounted(() => activateMenu())

    return () => {
      const menuProps = reactive({
        mode: props.mode,
        theme: props.theme,
        openKeys: state.openKeys,
        selectedKeys: state.selectedKeys,
        onSelect: (menu: MenuInfo) => {
          emit('select', menu)
          state.selectedKeys = [menu.key as string]
        },
        onClick: (menu: MenuInfo) => {
          emit('click', menu)
        },
        onOpenChange: onOpenChange
      })

      const menuItems = props.menus.map((item) => {
        if (!isMenuVisible(item)) return null
        return renderMenu(item)
      })

      return (
        <Menu class="s-menu" {...menuProps}>
          {() => menuItems}
        </Menu>
      )
    }
  }
})

export default RouteMenus
export type { RouteMenu } from '../menuUtils'
