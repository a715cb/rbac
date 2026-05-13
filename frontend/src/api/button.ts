/**
 * 按钮管理 API
 */

import request from '@/utils/request'
import type { ApiResponse } from '@/types/api'

export interface ButtonQuery {
  page?: number
  limit?: number
  keyword?: string
  status?: number
  menu_id?: number
}

export interface ButtonForm {
  name: string
  code: string
  menu_id: number
  icon?: string
  sort?: number
  status?: number
}

export interface ButtonInfo {
  id: number
  menu_id: number
  menu_name: string
  menu_path?: string
  name: string
  code: string
  icon: string
  sort: number
  status: number
  create_time: string
  update_time: string
}

export interface ButtonPagination {
  list: ButtonInfo[]
  pagination: {
    page: number
    page_size: number
    total: number
    total_pages: number
  }
}

export function getButtonList(params?: ButtonQuery): Promise<ApiResponse<ButtonPagination>> {
  return request.get('/admin/menu-buttons', { params })
}

export function getButtonDetail(id: number): Promise<ApiResponse<ButtonInfo>> {
  return request.get(`/admin/menu-buttons/${id}`)
}

export function changeButtonStatus(id: number, status: number): Promise<ApiResponse<void>> {
  return request.put(`/admin/menu-buttons/${id}/status`, { status })
}

export function batchButtonStatus(ids: number[], status: number): Promise<ApiResponse<void>> {
  return request.post('/admin/menu-buttons/batch-status', { ids, status })
}

export function batchDeleteButtons(ids: number[]): Promise<ApiResponse<void>> {
  return request.post('/admin/menu-buttons/batch-delete', { ids })
}
