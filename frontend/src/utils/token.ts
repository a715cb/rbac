/**
 * Token 管理模块
 * 包含 Token 存储、刷新、验证等功能
 */

import { AppConfig } from '@/config/app'
import { StorageManager } from './storage'

interface TokenInfo {
  accessToken: string
  refreshToken: string
  expiresAt: number
}

const REFRESH_BEFORE_SECONDS = 30 * 60

const TokenManager = {
  setToken(tokenInfo: TokenInfo): void {
    StorageManager.setItem('session', AppConfig.tokenKey, tokenInfo.accessToken)
    StorageManager.setItem('local', AppConfig.refreshTokenKey, tokenInfo.refreshToken)
    StorageManager.setItem('local', AppConfig.tokenExpiresKey, String(tokenInfo.expiresAt))
  },

  getAccessToken(): string | null {
    return StorageManager.getItem('session', AppConfig.tokenKey)
  },

  getRefreshToken(): string | null {
    return StorageManager.getItem('local', AppConfig.refreshTokenKey)
  },

  getExpiresAt(): number | null {
    const expiresAt = StorageManager.getItem('local', AppConfig.tokenExpiresKey)
    return expiresAt ? parseInt(expiresAt, 10) : null
  },

  isTokenExpiringSoon(): boolean {
    const expiresAt = this.getExpiresAt()
    if (!expiresAt) return true

    const now = Math.floor(Date.now() / 1000)
    return expiresAt - now < REFRESH_BEFORE_SECONDS
  },

  isTokenExpired(): boolean {
    const expiresAt = this.getExpiresAt()
    if (!expiresAt) return true

    const now = Math.floor(Date.now() / 1000)
    return now >= expiresAt
  },

  hasRefreshToken(): boolean {
    const refreshToken = StorageManager.getItem('local', AppConfig.refreshTokenKey)
    return !!refreshToken
  },

  hasAccessToken(): boolean {
    const accessToken = StorageManager.getItem('session', AppConfig.tokenKey)
    return !!accessToken
  },

  clearToken(): void {
    StorageManager.removeItem('session', AppConfig.tokenKey)
    StorageManager.removeItem('local', AppConfig.refreshTokenKey)
    StorageManager.removeItem('local', AppConfig.tokenExpiresKey)
  },

  setRefreshTime(): void {
    const expiresAt = Math.floor(Date.now() / 1000) + AppConfig.tokenExpirySeconds
    StorageManager.setItem('local', AppConfig.tokenExpiresKey, String(expiresAt))
  }
}

export function calculateExpiresAt(expiresIn: number): number {
  return Math.floor(Date.now() / 1000) + expiresIn
}

export { TokenManager }
