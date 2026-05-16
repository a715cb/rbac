/**
 * 部门管理 API
 */

import { get, post, put, del } from '@/utils/request'
import type { ApiResponse } from '@/types/api'

export interface DeptQuery {
  keyword?: string
  status?: number
}

export interface DeptForm {
  id?: number
  parent_id?: number
  name: string
  code: string
  leader?: string
  phone?: string
  email?: string
  sort?: number
  status?: number
}

export interface DeptInfo {
  id: number
  parent_id: number
  parent_name: string
  name: string
  code: string
  leader: string
  phone: string
  email: string
  sort: number
  status: number
  create_time: string
  children?: DeptInfo[]
}

export function getDeptList(params?: DeptQuery): Promise<ApiResponse<{ list: DeptInfo[] }>> {
  return get('/api/admin/depts', params)
}

export function getDeptTree(status?: number): Promise<ApiResponse<{ tree: DeptInfo[] }>> {
  return get('/api/admin/depts/tree', status !== undefined ? { status } : undefined)
}

export function getDeptDetail(id: number): Promise<ApiResponse<DeptInfo>> {
  return get(`/api/admin/depts/${id}`)
}

export function createDept(data: DeptForm): Promise<ApiResponse<{ id: number }>> {
  return post('/api/admin/depts', data)
}

export function updateDept(id: number, data: DeptForm): Promise<ApiResponse<void>> {
  return put(`/api/admin/depts/${id}`, data)
}

export function deleteDept(id: number): Promise<ApiResponse<void>> {
  return del(`/api/admin/depts/${id}`)
}

export function changeDeptStatus(id: number, status: number): Promise<ApiResponse<void>> {
  return put(`/api/admin/depts/${id}/status`, { status })
}

export function changeDeptSort(id: number, sort: number): Promise<ApiResponse<void>> {
  return put(`/api/admin/depts/${id}/sort`, { sort })
}

export interface DeptUserItem {
  id: number
  username: string
  nickname: string
  mobile: string
  status: number
  is_primary: number
}

export function getDeptUsers(id: number): Promise<ApiResponse<{ list: DeptUserItem[] }>> {
  return get(`/api/admin/depts/${id}/users`)
}
