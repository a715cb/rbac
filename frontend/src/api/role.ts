/**
 * 角色管理 API
 */

import request from '@/utils/request'
import type { ApiResponse } from '@/types/api'

export interface RoleQuery {
  page?: number
  limit?: number
  keyword?: string
  status?: number
}

export interface RoleForm {
  id?: number
  name: string
  code: string
  data_scope?: number
  data_scope_dept_ids?: string
  sort?: number
  status?: number
  remark?: string
  menu_ids?: number[]
  button_ids?: number[]
  api_ids?: number[]
}

export interface RoleInfo {
  id: number
  name: string
  code: string
  data_scope: number
  data_scope_dept_ids: string
  sort: number
  status: number
  remark: string
  user_count: number
  menu_ids: number[]
  button_ids: number[]
  api_ids: number[]
  create_time: string
}

export interface RolePagination {
  list: RoleInfo[]
  pagination: {
    page: number
    page_size: number
    total: number
    total_pages: number
  }
}

export function getRoleList(params?: RoleQuery): Promise<ApiResponse<RolePagination>> {
  return request.get('/admin/roles', { params })
}

export function getRoleDetail(id: number): Promise<ApiResponse<RoleInfo>> {
  return request.get(`/admin/roles/${id}`)
}

export function createRole(data: RoleForm): Promise<ApiResponse<{ id: number }>> {
  return request.post('/admin/roles', data)
}

export function updateRole(id: number, data: RoleForm): Promise<ApiResponse<void>> {
  return request.put(`/admin/roles/${id}`, data)
}

export function deleteRole(id: number): Promise<ApiResponse<void>> {
  return request.delete(`/admin/roles/${id}`)
}

export function assignRoleMenus(id: number, menuIds: number[]): Promise<ApiResponse<void>> {
  return request.post(`/admin/roles/${id}/assign-menus`, { menu_ids: menuIds })
}

export function assignRoleButtons(id: number, buttonIds: number[]): Promise<ApiResponse<void>> {
  return request.post(`/admin/roles/${id}/assign-buttons`, { button_ids: buttonIds })
}

export function assignRoleApis(id: number, apiIds: number[]): Promise<ApiResponse<void>> {
  return request.post(`/admin/roles/${id}/assign-apis`, { api_ids: apiIds })
}

export function setRoleDataScope(
  id: number,
  dataScope: number,
  deptIds?: string
): Promise<ApiResponse<void>> {
  return request.put(`/admin/roles/${id}/data-scope`, {
    data_scope: dataScope,
    data_scope_dept_ids: deptIds || ''
  })
}

export function changeRoleStatus(id: number, status: number): Promise<ApiResponse<void>> {
  return request.put(`/admin/roles/${id}/status`, { status })
}
