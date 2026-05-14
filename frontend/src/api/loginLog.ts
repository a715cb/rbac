import request from '@/utils/request'
import type { ApiResponse } from '@/types/api'

export interface LoginLogQuery {
  page?: number
  limit?: number
  keyword?: string
  status?: number
  start_date?: string
  end_date?: string
}

export interface LoginLogInfo {
  id: number
  username: string
  ip: string
  address: string
  user_agent: string
  os: string
  browser: string
  status: number
  msg: string
  login_time: string
}

export interface LoginLogPagination {
  list: LoginLogInfo[]
  pagination: {
    page: number
    page_size: number
    total: number
    total_pages: number
  }
}

export function getLoginLogList(params?: LoginLogQuery): Promise<ApiResponse<LoginLogPagination>> {
  return request.get('/admin/login-logs', { params })
}

export interface LoginLogStats {
  total: number
  success_count: number
  fail_count: number
  daily_stats: Array<{
    date: string
    count: number
  }>
}

export function getLoginLogStats(params?: {
  start_date?: string
  end_date?: string
}): Promise<ApiResponse<LoginLogStats>> {
  return request.get('/admin/login-logs/stats', { params })
}

export function cleanLoginLog(data?: {
  before_date?: string
}): Promise<ApiResponse<{ deleted_count: number }>> {
  return request.post('/admin/login-logs/clean', data)
}

export function clearLoginLog(): Promise<ApiResponse<{ deleted_count: number }>> {
  return request.post('/admin/login-logs/clear')
}

export function deleteLoginLog(ids: number[]): Promise<ApiResponse<{ deleted_count: number }>> {
  return request.post('/admin/login-logs/delete', { ids })
}
