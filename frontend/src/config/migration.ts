import { AppConfig } from './app'

export function migrateUnprefixedKeys(): void {
  const prefix = AppConfig.storagePrefix
  const allKeys = Object.keys(AppConfig) as (keyof typeof AppConfig)[]
  const storageKeys = allKeys.filter((k) => typeof AppConfig[k] === 'string' && k.endsWith('Key'))

  for (const configKey of storageKeys) {
    const rawKey = AppConfig[configKey] as string
    const newKey = prefix ? `${prefix}_${rawKey}` : rawKey

    const legacyPatterns = [rawKey, `_${rawKey}`]
    if (prefix) {
      legacyPatterns.push(`${prefix}_${rawKey}`)
    }

    for (const storage of [localStorage, sessionStorage]) {
      for (const oldKey of legacyPatterns) {
        if (oldKey === newKey) continue
        const oldValue = storage.getItem(oldKey)
        if (oldValue !== null) {
          const currentNewValue = storage.getItem(newKey)
          if (currentNewValue === null) {
            storage.setItem(newKey, oldValue)
          }
          storage.removeItem(oldKey)
        }
      }
    }
  }
}
