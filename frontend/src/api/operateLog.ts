import request from '@/utils/request'
import type { ApiResponse } from '@/types/api'

export interface OperateLogQuery {
  page?: number
  limit?: number
  user_id?: number
  method?: string
  ip?: string
  start_date?: string
  end_date?: string
}

export interface OperateLogInfo {
  id: number
  user_id: number
  username: string
  module: string
  action: string
  method: string
  url: string
  ip: string
  address: string
  param: string
  result: string
  status: number
  error_msg: string
  duration: number
  create_time: string
}

export interface OperateLogPagination {
  list: OperateLogInfo[]
  pagination: {
    page: number
    page_size: number
    total: number
    total_pages: number
  }
}

export function getOperateLogList(
  params?: OperateLogQuery
): Promise<ApiResponse<OperateLogPagination>> {
  return request.get('/admin/operation-logs', { params })
}

export function getOperateLogStats(params?: {
  start_date?: string
  end_date?: string
}): Promise<ApiResponse<any>> {
  return request.get('/admin/operation-logs/stats', { params })
}

export function cleanOperateLog(data?: {
  before_date?: string
}): Promise<ApiResponse<{ deleted_count: number }>> {
  return request.post('/admin/operation-logs/clean', data)
}

export function clearAllOperateLog(): Promise<ApiResponse<{ deleted_count: number }>> {
  return request.post('/admin/operation-logs/clear')
}

export function deleteOperateLog(ids: number[]): Promise<ApiResponse<{ deleted_count: number }>> {
  return request.post('/admin/operation-logs/delete', { ids })
}

export function exportOperateLog(params?: OperateLogQuery): Promise<ApiResponse<any>> {
  return request.get('/admin/operation-logs/export', { params })
}
