/**
 * 存储管理器
 * 封装 localStorage 和 sessionStorage，提供统一的缓存前缀支持
 */

import { AppConfig } from '@/config/app'

type StorageType = 'local' | 'session'

function getStorage(type: StorageType): Storage {
  return type === 'local' ? localStorage : sessionStorage
}

function addPrefix(key: string): string {
  return AppConfig.storagePrefix ? `${AppConfig.storagePrefix}_${key}` : key
}

function logStorageError(action: string, key: string, error: unknown): void {
  if (import.meta.env.DEV) {
    console.warn(`Storage ${action} failed for key "${key}":`, error)
  }
}

export const StorageManager = {
  setItem(type: StorageType, key: string, value: string): void {
    try {
      const storage = getStorage(type)
      storage.setItem(addPrefix(key), value)
    } catch (error) {
      logStorageError('setItem', key, error)
    }
  },

  getItem(type: StorageType, key: string): string | null {
    try {
      const storage = getStorage(type)
      return storage.getItem(addPrefix(key))
    } catch (error) {
      logStorageError('getItem', key, error)
      return null
    }
  },

  removeItem(type: StorageType, key: string): void {
    try {
      const storage = getStorage(type)
      storage.removeItem(addPrefix(key))
    } catch (error) {
      logStorageError('removeItem', key, error)
    }
  },

  setObject<T>(type: StorageType, key: string, value: T): void {
    try {
      const jsonStr = JSON.stringify(value)
      this.setItem(type, key, jsonStr)
    } catch (error) {
      logStorageError('setObject', key, error)
    }
  },

  getObject<T>(type: StorageType, key: string): T | null {
    try {
      const jsonStr = this.getItem(type, key)
      if (jsonStr === null) return null
      return JSON.parse(jsonStr) as T
    } catch (error) {
      logStorageError('getObject', key, error)
      return null
    }
  },

  clear(type: StorageType): void {
    try {
      const storage = getStorage(type)
      const prefix = AppConfig.storagePrefix ? `${AppConfig.storagePrefix}_` : ''
      for (let i = storage.length - 1; i >= 0; i--) {
        const key = storage.key(i)
        if (key && key.startsWith(prefix)) {
          storage.removeItem(key)
        }
      }
    } catch (error) {
      if (import.meta.env.DEV) console.warn(`Storage clear failed:`, error)
    }
  },

  hasKey(type: StorageType, key: string): boolean {
    return this.getItem(type, key) !== null
  },

  keys(type: StorageType): string[] {
    try {
      const storage = getStorage(type)
      const result: string[] = []
      const prefix = AppConfig.storagePrefix ? `${AppConfig.storagePrefix}_` : ''
      for (let i = 0; i < storage.length; i++) {
        const key = storage.key(i)
        if (key && key.startsWith(prefix)) {
          result.push(key.substring(prefix.length))
        }
      }
      return result
    } catch (error) {
      logStorageError('keys', '', error)
      return []
    }
  }
}

export default StorageManager
