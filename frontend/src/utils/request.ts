/**
 * 请求工具函数
 * 支持 Token 自动刷新
 */

import axios, {
  type AxiosInstance,
  type AxiosRequestConfig,
  type AxiosResponse,
  type AxiosError
} from 'axios'
import { message } from 'ant-design-vue'
import { AppConfig } from '@/config/app'
import { refreshToken } from '@/api/auth'
import { TokenManager } from '@/utils/token'
import { StorageManager } from './storage'
import type { ApiResponse } from '@/types/api'

const axiosConfig = {
  baseURL: AppConfig.apiBaseUrl,
  timeout: AppConfig.requestTimeout,
  headers: {
    'Content-Type': 'application/json'
  },
  transformResponse: [
    (data: any) => {
      try {
        return JSON.parse(data)
      } catch {
        return data
      }
    }
  ],
  withCredentials: false
}

const request: AxiosInstance = axios.create(axiosConfig)

const pendingRequests = new Map<string, AbortController>()

export function cancelPendingRequests(reason?: string): void {
  pendingRequests.forEach((controller) => {
    controller.abort(reason || '请求已取消')
  })
  pendingRequests.clear()
}

export function cancelRequest(url: string): void {
  const controller = pendingRequests.get(url)
  if (controller) {
    controller.abort()
    pendingRequests.delete(url)
  }
}

let isRefreshing = false
let refreshSubscribers: Array<(token: string) => void> = []

function subscribeTokenRefresh(callback: (token: string) => void): void {
  refreshSubscribers.push(callback)
}

function onTokenRefreshed(token: string): void {
  refreshSubscribers.forEach((callback) => callback(token))
  refreshSubscribers = []
}

function getErrorMessage(error: AxiosError): string {
  if (!error.response) {
    if (error.code === 'ECONNABORTED' || error.code === 'ERR_CANCELED') {
      return '请求超时，请检查网络连接后重试'
    }
    if (!axios.isCancel(error) && error.message === 'Network Error') {
      return '网络连接失败，请检查您的网络设置'
    }
    return '网络异常，请稍后重试'
  }

  const status = error.response.status
  const data = error.response.data as ApiResponse | undefined
  const serverMsg = data?.msg

  switch (status) {
    case 400:
      return serverMsg || '请求参数错误'
    case 401:
      return serverMsg || '认证已过期，请重新登录'
    case 403:
      return serverMsg || '没有权限执行此操作'
    case 404:
      return serverMsg || '请求的资源不存在'
    case 422:
      return serverMsg || '提交的数据验证失败'
    case 429:
      return '请求过于频繁，请稍后再试'
    case 500:
      return '服务器内部错误，请稍后重试'
    case 502:
      return '服务暂时不可用，请稍后重试'
    case 503:
      return '服务正在维护中，请稍后重试'
    default:
      return serverMsg || `请求失败（${status}）`
  }
}

function isLoginRequest(url: string | undefined): boolean {
  return !!url && (url.includes('/login') || url.includes('/refresh-token'))
}

request.interceptors.request.use(
  (config) => {
    const token = StorageManager.getItem('session', AppConfig.tokenKey)
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    const controller = new AbortController()
    config.signal = controller.signal
    pendingRequests.set(config.url || '', controller)

    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

request.interceptors.response.use(
  (response: AxiosResponse<ApiResponse>): any => {
    const res = response.data

    if (res.code !== 200) {
      const errorMsg = res.msg || '请求失败'
      message.error(errorMsg)

      if (response.config.url) {
        pendingRequests.delete(response.config.url)
      }

      if (res.code === 401) {
        TokenManager.clearToken()
        StorageManager.removeItem('session', AppConfig.userInfoKey)
        window.location.href = '/login'
      }

      return Promise.reject(new Error(errorMsg))
    }

    if (response.config.url) {
      pendingRequests.delete(response.config.url)
    }

    return res
  },
  async (error: AxiosError) => {
    const originalRequest = error.config as AxiosRequestConfig & { _retry?: boolean }

    if (originalRequest?.url) {
      pendingRequests.delete(originalRequest.url)
    }

    if (error.response?.status === 401 && !originalRequest._retry) {
      if (isLoginRequest(originalRequest?.url)) {
        const data = error.response.data as ApiResponse | undefined
        const loginError = data?.msg || '用户名或密码错误'
        return Promise.reject(new Error(loginError))
      }

      if (isRefreshing) {
        return new Promise((resolve) => {
          subscribeTokenRefresh((token: string) => {
            originalRequest.headers = {
              ...originalRequest.headers,
              Authorization: `Bearer ${token}`
            }
            resolve(request(originalRequest))
          })
        })
      }

      if (!TokenManager.hasAccessToken()) {
        TokenManager.clearToken()
        StorageManager.removeItem('session', AppConfig.userInfoKey)
        window.location.href = '/login'
        return Promise.reject(new Error('未登录，请先登录'))
      }

      if (!TokenManager.hasRefreshToken()) {
        TokenManager.clearToken()
        StorageManager.removeItem('session', AppConfig.userInfoKey)
        window.location.href = '/login'
        return Promise.reject(new Error('登录已过期，请重新登录'))
      }

      originalRequest._retry = true
      isRefreshing = true

      try {
        const currentRefreshToken = TokenManager.getRefreshToken()
        if (!currentRefreshToken) {
          throw new Error('刷新令牌不可用')
        }

        const response = await refreshToken(currentRefreshToken)
        const { access_token: newToken, refresh_token: newRefreshToken } = response.data

        TokenManager.setToken({
          accessToken: newToken,
          refreshToken: newRefreshToken,
          expiresAt: Math.floor(Date.now() / 1000) + AppConfig.tokenExpirySeconds
        })

        StorageManager.setItem('session', AppConfig.tokenKey, newToken)

        onTokenRefreshed(newToken)
        isRefreshing = false

        originalRequest.headers = {
          ...originalRequest.headers,
          Authorization: `Bearer ${newToken}`
        }
        return request(originalRequest)
      } catch (refreshError) {
        isRefreshing = false
        TokenManager.clearToken()
        StorageManager.removeItem('session', AppConfig.userInfoKey)
        window.location.href = '/login'
        return Promise.reject(refreshError)
      }
    }

    const errorMsg = getErrorMessage(error)
    message.error(errorMsg)
    return Promise.reject(new Error(errorMsg))
  }
)

export function get<T = unknown>(
  url: string,
  params?: Record<string, unknown>,
  config?: AxiosRequestConfig
): Promise<ApiResponse<T>> {
  return request.get(url, { params, ...config })
}

export function post<T = unknown>(
  url: string,
  data?: Record<string, unknown>,
  config?: AxiosRequestConfig
): Promise<ApiResponse<T>> {
  return request.post(url, data, config)
}

export function put<T = unknown>(
  url: string,
  data?: Record<string, unknown>,
  config?: AxiosRequestConfig
): Promise<ApiResponse<T>> {
  return request.put(url, data, config)
}

export function del<T = unknown>(
  url: string,
  config?: AxiosRequestConfig
): Promise<ApiResponse<T>> {
  return request.delete(url, config)
}

export default request
