import type { ComputedRef, Ref } from 'vue'

export type SizeType = 'default' | 'middle' | 'small'

export interface ColumnItem {
  key: string
  title?: string
  dataIndex?: string | number
  visible?: boolean
  width?: number | string
  fixed?: 'left' | 'right' | boolean
  align?: 'left' | 'center' | 'right'
  [key: string]: unknown
}

export interface TableSettingState {
  size: SizeType
  isFullscreen: boolean
  columns: ColumnItem[]
}

export interface TableSettingActions {
  refresh: () => void
  toggleFullscreen: () => void
  changeSize: (size: SizeType) => void
  setColumns: (columns: ColumnItem[]) => void
  resetColumns: () => void
}

export interface TableSettingContext {
  state: TableSettingState
  actions: TableSettingActions
  wrapRef: Ref<HTMLElement | null>
  getVisibleColumns: ComputedRef<ColumnItem[]>
  getPopupContainer: () => HTMLElement
}
