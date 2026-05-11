/**
 * 认证 API
 */

import request from '@/utils/request'
import type { ApiResponse } from '@/types/api'

export interface LoginRequest {
  username: string
  password: string
}

export interface LoginResponse {
  access_token: string
  refresh_token: string
  token_type: string
  expires_in: number
  user_info: {
    id: number
    username: string
    nickname: string
    email: string
    mobile: string
    avatar: string
  }
}

/**
 * 用户登录
 * @param data 登录信息
 * @returns Promise<ApiResponse<LoginResponse>>
 */
export function login(data: LoginRequest): Promise<ApiResponse<LoginResponse>> {
  return request.post('/admin/login', data)
}

/**
 * 用户登出
 * @returns Promise<ApiResponse<void>>
 */
export function logout(): Promise<ApiResponse<void>> {
  return request.post('/admin/logout')
}

/**
 * 刷新 Token
 * @returns Promise<ApiResponse<LoginResponse>>
 */
export function refreshToken(refresh_token: string): Promise<ApiResponse<LoginResponse>> {
  return request.post('/admin/refresh-token', { refresh_token })
}

export interface ProfileResponse {
  id: number
  username: string
  nickname: string
  email: string
  mobile: string
  avatar: string
  gender: number
  dept_id: number
  last_login_ip: string
  last_login_time: string
  roles: { id: number; name: string; code: string }[]
  menus: Record<string, unknown>[]
  permissions: string[]
}

/**
 * 获取当前用户信息
 * @returns Promise<ApiResponse<ProfileResponse>>
 */
export function getCurrentUser(): Promise<ApiResponse<ProfileResponse>> {
  return request.get('/admin/profile')
}
