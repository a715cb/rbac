/**
 * 接口管理 API
 */

import request from '@/utils/request'
import type { ApiResponse } from '@/types/api'

export const HTTP_METHODS = [
  { value: 'GET', label: 'GET', color: 'green' },
  { value: 'POST', label: 'POST', color: 'blue' },
  { value: 'PUT', label: 'PUT', color: 'orange' },
  { value: 'DELETE', label: 'DELETE', color: 'red' }
] as const

export interface ApiQuery {
  page?: number
  limit?: number
  keyword?: string
  status?: number
  menu_id?: number
  method?: string
  group?: string
}

export interface ApiForm {
  id?: number
  menu_id?: number
  name: string
  code: string
  method: string
  path: string
  group?: string
  status?: number
}

export interface ApiInfo {
  id: number
  menu_id: number
  menu_name: string
  name: string
  code: string
  method: string
  path: string
  group: string
  status: number
  create_time: string
}

export interface ApiPagination {
  list: ApiInfo[]
  groups: string[]
  pagination: {
    page: number
    page_size: number
    total: number
    total_pages: number
  }
}

export function getApiList(params?: ApiQuery): Promise<ApiResponse<ApiPagination>> {
  return request.get('/admin/apis', { params })
}

export function getApiDetail(id: number): Promise<ApiResponse<ApiInfo>> {
  return request.get(`/admin/apis/${id}`)
}

export function createApi(data: ApiForm): Promise<ApiResponse<{ id: number }>> {
  return request.post('/admin/apis', data)
}

export function updateApi(id: number, data: ApiForm): Promise<ApiResponse<void>> {
  return request.put(`/admin/apis/${id}`, data)
}

export function deleteApi(id: number): Promise<ApiResponse<void>> {
  return request.delete(`/admin/apis/${id}`)
}

export function changeApiStatus(id: number, status: number): Promise<ApiResponse<void>> {
  return request.put(`/admin/apis/${id}/status`, { status })
}

export function getApiGroups(): Promise<ApiResponse<{ groups: string[] }>> {
  return request.get('/admin/apis/groups')
}

export function getApisByMenu(menuId: number): Promise<ApiResponse<{ list: ApiInfo[] }>> {
  return request.get(`/admin/apis/menu/${menuId}`)
}
