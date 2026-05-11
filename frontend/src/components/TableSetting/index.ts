import TableSetting from './TableSetting.vue'
import {
  useTableSetting,
  createTableSettingContext,
  useTableSettingContext
} from './useTableSetting'

export { useTableSetting, createTableSettingContext, useTableSettingContext }
export default TableSetting
export type {
  ColumnItem,
  SizeType,
  TableSettingState,
  TableSettingActions,
  TableSettingContext
} from './types'
