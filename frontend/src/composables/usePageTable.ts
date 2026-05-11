import { computed } from 'vue'
import type { Ref } from 'vue'
import {
  useTableSetting,
  createTableSettingContext
} from '@/components/TableSetting/useTableSetting'
import type { ColumnItem } from '@/components/TableSetting/types'
import type { SizeType } from '@/components/TableSetting/types'

export interface UsePageTableOptions {
  columns: ColumnItem[]
  fetchData: () => Promise<void>
  wrapRef: Ref<HTMLElement | null>
}

export function usePageTable(options: UsePageTableOptions) {
  const { columns, fetchData, wrapRef } = options

  const {
    state: tableSettingState,
    getVisibleColumns,
    getPopupContainer,
    wrapRef: settingWrapRef
  } = useTableSetting({
    columns,
    onRefresh: fetchData,
    wrapRef
  })

  createTableSettingContext({
    state: tableSettingState,
    actions: {
      refresh: fetchData,
      toggleFullscreen: () => {
        tableSettingState.isFullscreen = !tableSettingState.isFullscreen
      },
      changeSize: (size: SizeType) => {
        tableSettingState.size = size
      },
      setColumns: (cols: ColumnItem[]) => {
        tableSettingState.columns = cols
      },
      resetColumns: () => {
        tableSettingState.columns = columns.map((col) => ({ ...col }))
      }
    },
    wrapRef: settingWrapRef,
    getVisibleColumns,
    getPopupContainer
  })

  const visibleColumns = computed(() =>
    getVisibleColumns.value.map((col) => ({
      title: col.title,
      dataIndex: col.dataIndex,
      key: col.key,
      width: col.width,
      align: col.align as 'left' | 'center' | 'right' | undefined,
      fixed: col.fixed,
      ellipsis: col.ellipsis,
      customRender: col.customRender
    }))
  )

  return {
    tableSettingState,
    visibleColumns,
    getPopupContainer
  }
}
