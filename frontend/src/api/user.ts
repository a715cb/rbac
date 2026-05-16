/**
 * 用户管理 API
 */

import { get, post, put, del } from '@/utils/request'
import type { ApiResponse } from '@/types/api'

export interface UserDeptItem {
  dept_id: number
  dept_name: string
  is_primary: number
  sort: number
}

export interface UserQuery {
  page?: number
  limit?: number
  keyword?: string
  status?: number
  dept_id?: number
  gender?: number
}

export interface UserForm {
  id?: number
  username: string
  password?: string
  confirmPassword?: string
  nickname?: string
  email?: string
  mobile?: string
  gender?: number
  dept_id?: number
  depts?: UserDeptItem[]
  status?: number
  role_ids?: number[]
}

export interface UserInfo {
  id: number
  username: string
  nickname: string
  email: string
  mobile: string
  avatar: string
  gender: number
  status: number
  dept_id: number
  dept_name: string
  depts: UserDeptItem[]
  roles: Array<{ id: number; name: string; code: string }>
  last_login_ip: string
  last_login_time: string
  create_time: string
}

export interface UserPagination {
  list: UserInfo[]
  pagination: {
    page: number
    page_size: number
    total: number
    total_pages: number
  }
}

export function getUserList(params: UserQuery): Promise<ApiResponse<UserPagination>> {
  return get('/api/admin/users', params)
}

export function getUserDetail(id: number): Promise<ApiResponse<UserInfo>> {
  return get(`/api/admin/users/${id}`)
}

export function createUser(data: UserForm): Promise<ApiResponse<{ id: number }>> {
  return post('/api/admin/users', data)
}

export function updateUser(id: number, data: UserForm): Promise<ApiResponse<void>> {
  return put(`/api/admin/users/${id}`, data)
}

export function deleteUser(id: number): Promise<ApiResponse<void>> {
  return del(`/api/admin/users/${id}`)
}

export function assignRoles(id: number, roleIds: number[]): Promise<ApiResponse<void>> {
  return post(`/api/admin/users/${id}/assign-roles`, { role_ids: roleIds })
}

export function resetPassword(id: number, password: string): Promise<ApiResponse<void>> {
  return post(`/api/admin/users/${id}/reset-password`, { password })
}

export function changeUserStatus(id: number, status: number): Promise<ApiResponse<void>> {
  return put(`/api/admin/users/${id}/status`, { status })
}

export function exportUsers(params: UserQuery): Promise<ApiResponse<Record<string, unknown>[]>> {
  return get('/api/admin/users/export', params)
}

export function updateUserDepts(id: number, depts: UserDeptItem[]): Promise<ApiResponse<void>> {
  return put(`/api/admin/users/${id}/depts`, { depts })
}

export function addUserDepts(id: number, depts: UserDeptItem[]): Promise<ApiResponse<void>> {
  return post(`/api/admin/users/${id}/depts`, { depts })
}

export function removeUserDept(id: number, deptId: number): Promise<ApiResponse<void>> {
  return del(`/api/admin/users/${id}/depts/${deptId}`)
}
