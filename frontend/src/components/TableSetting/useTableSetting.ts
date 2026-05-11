import { ref, reactive, computed, watch, unref, provide, inject, type Ref } from 'vue'
import type {
  ColumnItem,
  SizeType,
  TableSettingState,
  TableSettingActions,
  TableSettingContext
} from './types'

const TABLE_SETTING_KEY = Symbol('table-setting')

const contextRegistry = new Map<string, TableSettingContext>()
let globalContextCounter = 0

export function createTableSettingContext(context: TableSettingContext) {
  provide(TABLE_SETTING_KEY, context)

  const instanceId = `table-setting-${globalContextCounter++}`
  contextRegistry.set(instanceId, context)

  const cleanup = () => {
    contextRegistry.delete(instanceId)
  }

  return {
    instanceId,
    cleanup
  }
}

export function useTableSettingContext(): TableSettingContext {
  const ctx = inject<TableSettingContext>(TABLE_SETTING_KEY)
  if (ctx) {
    return ctx
  }

  if (contextRegistry.size > 0) {
    const firstContext = contextRegistry.values().next().value
    if (firstContext) {
      return firstContext
    }
  }
  throw new Error('useTableSettingContext must be used inside TableSetting provider')
}

export function useTableSetting(options: {
  columns: ColumnItem[]
  onRefresh?: () => void
  wrapRef?: Ref<HTMLElement | null>
}) {
  const internalWrapRef = ref<HTMLElement | null>(null)
  const wrapRef = options.wrapRef || internalWrapRef

  const defaultColumns = options.columns.map((col: ColumnItem) => ({
    ...col,
    visible: col.visible !== false
  }))

  const state = reactive<TableSettingState>({
    size: 'default',
    isFullscreen: false,
    columns: defaultColumns.map((col: ColumnItem) => ({ ...col }))
  })

  const getVisibleColumns = computed(() => {
    return state.columns.filter((col: ColumnItem) => col.visible !== false)
  })

  const getPopupContainer = () => {
    return unref(wrapRef) || document.body
  }

  const refresh = () => {
    options.onRefresh?.()
  }

  const toggleFullscreen = () => {
    state.isFullscreen = !state.isFullscreen
  }

  watch(
    () => state.isFullscreen,
    (value: boolean) => {
      const el = unref(wrapRef)
      if (!el) return
      if (value) {
        el.classList.add('fullscreen-table')
      } else {
        el.classList.remove('fullscreen-table')
      }
    }
  )

  const changeSize = (size: SizeType) => {
    state.size = size
  }

  const setColumns = (columns: ColumnItem[]) => {
    state.columns = columns.map((col: ColumnItem) => ({ ...col }))
  }

  const resetColumns = () => {
    state.columns = defaultColumns.map((col: ColumnItem) => ({ ...col }))
  }

  const actions: TableSettingActions = {
    refresh,
    toggleFullscreen,
    changeSize,
    setColumns,
    resetColumns
  }

  const context: TableSettingContext = {
    state,
    actions,
    wrapRef,
    getVisibleColumns,
    getPopupContainer
  }

  return {
    context,
    state,
    actions,
    wrapRef,
    getVisibleColumns,
    getPopupContainer
  }
}
