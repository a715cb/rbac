/**
 * 仪表盘 API
 */

import { get } from '@/utils/request'
import type { ApiResponse } from '@/types/api'

export interface DashboardStats {
  user_total: number
  role_total: number
  menu_total: number
  dept_total: number
}

/**
 * 获取仪表盘统计数据
 * @returns Promise<ApiResponse<DashboardStats>>
 */
export function getDashboardStats(): Promise<ApiResponse<DashboardStats>> {
  return get('/api/admin/dashboard/statistics')
}
