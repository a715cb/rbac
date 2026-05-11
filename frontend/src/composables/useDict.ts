import { ref, readonly } from 'vue'
import type { Ref } from 'vue'
import { getDictByCode } from '@/api/dict'

export interface DictOption {
  label: string
  value: string
}

type DictCache = Map<string, DictOption[]>

const dictCache: DictCache = new Map()
const dictLoadingMap: Map<string, boolean> = new Map()
const dictListeners: Map<string, Set<() => void>> = new Map()

function notifyListeners(code: string) {
  const listeners = dictListeners.get(code)
  if (listeners) {
    listeners.forEach((fn) => fn())
  }
}

function addListener(code: string, fn: () => void) {
  if (!dictListeners.has(code)) {
    dictListeners.set(code, new Set())
  }
  dictListeners.get(code)!.add(fn)
}

function removeListener(code: string, fn: () => void) {
  const listeners = dictListeners.get(code)
  if (listeners) {
    listeners.delete(fn)
    if (listeners.size === 0) {
      dictListeners.delete(code)
    }
  }
}

async function fetchDict(code: string, limit?: number): Promise<DictOption[]> {
  if (dictCache.has(code)) {
    return dictCache.get(code)!
  }

  if (dictLoadingMap.get(code)) {
    return new Promise<DictOption[]>((resolve) => {
      const check = () => {
        const cached = dictCache.get(code)
        if (cached) {
          resolve(cached)
          removeListener(code, check)
        }
      }
      addListener(code, check)
    })
  }

  dictLoadingMap.set(code, true)
  try {
    const res = await getDictByCode(code, limit)
    const options: DictOption[] = res.data.map((item) => ({
      label: item.label,
      value: item.value
    }))
    dictCache.set(code, options)
    notifyListeners(code)
    return options
  } catch (error) {
    if (import.meta.env.DEV) console.error(`[useDict] Failed to fetch dict "${code}":`, error)
    const empty: DictOption[] = []
    dictCache.set(code, empty)
    notifyListeners(code)
    return empty
  } finally {
    dictLoadingMap.delete(code)
  }
}

export interface UseDictOptions {
  code: string
  limit?: number
  immediate?: boolean
}

export interface UseDictReturn {
  options: Ref<DictOption[]>
  loading: Ref<boolean>
  refresh: () => Promise<void>
}

export function useDict(options: UseDictOptions): UseDictReturn {
  const { code, limit, immediate = true } = options

  const dictOptions = ref<DictOption[]>([])
  const loading = ref(false)

  const load = async () => {
    loading.value = true
    try {
      dictOptions.value = await fetchDict(code, limit)
    } finally {
      loading.value = false
    }
  }

  const refresh = async () => {
    dictCache.delete(code)
    await load()
  }

  if (immediate) {
    load()
  }

  return {
    options: dictOptions,
    loading: readonly(loading) as typeof loading,
    refresh
  }
}

export function clearDictCache(code?: string) {
  if (code) {
    dictCache.delete(code)
  } else {
    dictCache.clear()
  }
}

export function getDictLabel(
  options: DictOption[] | undefined,
  value: string | number | undefined
): string {
  if (!options || value === undefined || value === null) return ''
  const strValue = String(value)
  const found = options.find((opt) => opt.value === strValue)
  return found?.label ?? ''
}
