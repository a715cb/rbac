/**
 * 菜单管理 API
 */

import { get, post, put, del } from '@/utils/request'
import type { ApiResponse } from '@/types/api'

export interface MenuQuery {
  status?: number
  menu_type?: number
  keyword?: string
}

export interface MenuForm {
  id?: number
  name: string
  code: string
  menu_type: number
  parent_id?: number
  path?: string
  icon?: string
  component?: string
  sort?: number
  visible?: number
  keep_alive?: number
  always_show?: number
  breadcrumb?: number
  active_menu?: string
  is_external?: number
  is_frame?: number
  status?: number
  remark?: string
}

export interface MenuButton {
  id: number
  menu_id: number
  name: string
  code: string
  icon: string
  sort: number
  status: number
}

export interface MenuInfo {
  id: number
  parent_id: number
  name: string
  code: string
  path: string
  icon: string
  component: string
  menu_type: number
  sort: number
  visible: number
  status: number
  keep_alive: number
  always_show: number
  breadcrumb: number
  active_menu: string
  is_external: number
  is_frame: number
  remark: string
  create_time: string
  children?: MenuInfo[]
  buttons?: MenuButton[]
}

export function getMenuList(params?: MenuQuery): Promise<ApiResponse<{ list: MenuInfo[] }>> {
  return get('/api/admin/menus', params)
}

export function getMenuTree(status?: number): Promise<ApiResponse<{ tree: MenuInfo[] }>> {
  return get('/api/admin/menus/tree', status !== undefined ? { status } : undefined)
}

export function getMenuDetail(id: number): Promise<ApiResponse<MenuInfo>> {
  return get(`/api/admin/menus/${id}`)
}

export function createMenu(data: MenuForm): Promise<ApiResponse<{ id: number }>> {
  return post('/api/admin/menus', data)
}

export function updateMenu(id: number, data: MenuForm): Promise<ApiResponse<void>> {
  return put(`/api/admin/menus/${id}`, data)
}

/** 切换菜单启用/禁用状态 */
export function changeMenuStatus(id: number, status: number): Promise<ApiResponse<void>> {
  return put(`/api/admin/menus/${id}/status`, { status })
}

export function deleteMenu(id: number): Promise<ApiResponse<void>> {
  return del(`/api/admin/menus/${id}`)
}

export function getMenuButtons(id: number): Promise<ApiResponse<MenuButton[]>> {
  return get(`/api/admin/menus/${id}/buttons`)
}

export function createMenuButton(
  id: number,
  data: Omit<MenuButton, 'id' | 'menu_id'>
): Promise<ApiResponse<{ id: number }>> {
  return post(`/api/admin/menus/${id}/buttons`, data)
}

export function updateMenuButton(
  id: number,
  buttonId: number,
  data: Partial<MenuButton>
): Promise<ApiResponse<void>> {
  return put(`/api/admin/menus/${id}/buttons/${buttonId}`, data)
}

export function deleteMenuButton(id: number, buttonId: number): Promise<ApiResponse<void>> {
  return del(`/api/admin/menus/${id}/buttons/${buttonId}`)
}
