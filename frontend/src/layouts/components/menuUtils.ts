/**
 * @文件: menuUtils.ts
 * @用途: 菜单工具函数和类型定义
 * @描述: 提供菜单数据的类型定义、可见性判断、图标/标题提取、
 *        路由菜单列表转换和菜单路径匹配等工具函数，
 *        供 Menu 组件、Header 组件和 LeftNav 组件共同使用
 */

import type { RouteLocationNormalized } from 'vue-router'

/** 路由菜单项类型定义，描述从后端/路由配置中获取的菜单数据结构 */
export interface RouteMenu {
  /** 菜单路径，作为菜单项的唯一标识和路由跳转地址 */
  path: string
  /** 菜单名称，作为标题的备选来源 */
  name?: string
  /** 菜单图标标识，优先级低于 meta.icon */
  icon?: string
  /** 是否完全隐藏该菜单项（不渲染） */
  hidden?: boolean
  /** 是否隐藏子菜单（将父菜单直接渲染为可点击项） */
  hideChildrenInMenu?: boolean
  /** 子菜单列表 */
  children?: RouteMenu[]
  /** 菜单可见状态：1=可见，其他值=不可见 */
  visible?: number
  /** 菜单启用状态：1=启用，其他值=禁用（不显示） */
  status?: number
  /** 路由元信息，包含标题和图标等 */
  meta?: {
    /** 菜单标题，优先级最高 */
    title?: string
    /** 菜单图标，优先级高于外层 icon */
    icon?: string
    /** 路由元信息级别的隐藏控制 */
    hidden?: boolean
  }
}

/** 默认菜单图标，当菜单项未配置图标时使用 */
export const DEFAULT_MENU_ICON = 'appstore-outlined'

/** 默认菜单标题，当菜单项未配置标题时使用 */
export const DEFAULT_MENU_TITLE = '未命名菜单'

/**
 * 判断菜单项是否可见
 * @param item - 路由菜单项
 * @returns 是否可见
 * @description 综合判断 hidden、visible、status、meta.hidden 四个字段，
 *              任一条件不满足即判定为不可见
 */
export const isMenuVisible = (item: RouteMenu): boolean => {
  if (item.hidden) return false
  if (item.visible !== undefined && item.visible !== 1) return false
  if (item.status !== undefined && item.status !== 1) return false
  if (item.meta?.hidden) return false
  return true
}

/**
 * 判断菜单项是否配置了图标
 * @param item - 路由菜单项
 * @returns 是否有图标配置
 */
export const hasMenuIcon = (item: RouteMenu): boolean => {
  return !!(item.meta?.icon || item.icon)
}

/**
 * 获取菜单项图标，优先使用 meta.icon
 * @param item - 路由菜单项
 * @returns 图标标识字符串，无图标时返回默认图标
 */
export const getMenuIcon = (item: RouteMenu): string => {
  return item.meta?.icon || item.icon || DEFAULT_MENU_ICON
}

/**
 * 获取菜单项标题，优先使用 meta.title
 * @param item - 路由菜单项
 * @returns 标题字符串，无标题时返回默认标题
 */
export const getMenuTitle = (item: RouteMenu): string => {
  return item.meta?.title || item.name || DEFAULT_MENU_TITLE
}

/**
 * 将原始路由数据过滤转换为 RouteMenu 列表
 * @param routes - 原始路由配置数组
 * @returns 过滤后的 RouteMenu 数组
 * @description 过滤掉空值、非对象和缺少 path 属性的项，确保数据合法性
 */
export function asRouteMenuList(routes: any[]): RouteMenu[] {
  return routes.filter((item) => item && typeof item === 'object' && 'path' in item) as RouteMenu[]
}

/**
 * 根据当前路由匹配对应的一级菜单路径
 * @param route - 当前路由对象
 * @param menus - 菜单列表
 * @returns 匹配到的一级菜单路径
 * @description 用于 mix/left 布局下确定当前激活的一级菜单，
 *              遍历 route.matched 链查找第一个存在于菜单树中的路径
 */
export function getMatchedMenuPath(route: RouteLocationNormalized, menus: RouteMenu[]): string {
  /** 收集菜单树中所有路径，用于快速查找 */
  const menuPaths = new Set<string>()
  collectMenuPaths(menus, menuPaths)

  for (const matched of route.matched) {
    if (matched.path === '/') continue
    if (menuPaths.has(matched.path)) return matched.path
  }

  if (route.matched[0]?.path === '/' && menuPaths.has(route.path)) {
    return route.path
  }

  return route.path
}

/**
 * 递归收集菜单树中所有路径
 * @param menus - 菜单列表
 * @param result - 路径集合，用于去重存储
 */
function collectMenuPaths(menus: RouteMenu[], result: Set<string>): void {
  for (const menu of menus) {
    if (menu.path) result.add(menu.path)
    if (menu.children?.length) collectMenuPaths(menu.children, result)
  }
}
