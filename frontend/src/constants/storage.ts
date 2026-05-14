import { AppConfig } from '@/config/app'

export function prefixedKey(key: string): string {
  return AppConfig.storagePrefix ? `${AppConfig.storagePrefix}_${key}` : key
}
