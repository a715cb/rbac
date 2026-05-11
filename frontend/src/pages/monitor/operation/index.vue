<template>
  <div ref="wrapRef" class="page-container">
    <div class="search-card">
      <a-form layout="inline" :model="searchForm">
        <a-form-item label="操作人" html-for="operate-log-search-user">
          <a-select
            id="operate-log-search-user"
            v-model:value="searchForm.user_id"
            placeholder="请选择操作人"
            allow-clear
            style="width: 180px"
          >
            <a-select-option v-for="user in userOptions" :key="user.id" :value="user.id">
              {{ user.username }}
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="请求方式" html-for="operate-log-search-method">
          <a-select
            id="operate-log-search-method"
            v-model:value="searchForm.method"
            placeholder="全部方式"
            allow-clear
            style="width: 120px"
          >
            <a-select-option value="GET">GET</a-select-option>
            <a-select-option value="POST">POST</a-select-option>
            <a-select-option value="PUT">PUT</a-select-option>
            <a-select-option value="DELETE">DELETE</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="IP地址" html-for="operate-log-search-ip">
          <a-input
            id="operate-log-search-ip"
            v-model:value="searchForm.ip"
            placeholder="请输入IP地址"
            style="width: 160px"
            allow-clear
          />
        </a-form-item>
        <a-form-item label="操作时间" html-for="operate-log-search-time">
          <a-range-picker
            id="operate-log-search-time"
            v-model:value="searchForm.dateRange"
            :presets="datePresets"
            style="width: 280px"
          />
        </a-form-item>
        <a-form-item>
          <a-space>
            <a-button type="primary" @click="handleSearch">
              <SearchOutlined />
              查询
            </a-button>
            <a-button @click="handleReset">
              <ReloadOutlined />
              重置
            </a-button>
          </a-space>
        </a-form-item>
      </a-form>
    </div>

    <div class="s-table-wrapper">
      <div class="s-table-header">
        <div class="table-header-container">
          <div class="flex items-center">
            <div class="table-header-toolbar">
              <div>
                <a-button
                  v-auth="'admin:operation-log:clear'"
                  danger
                  type="primary"
                  @click="handleClear"
                >
                  <DeleteOutlined />
                  清除日志
                </a-button>
                <a-button
                  v-if="selectedRowKeys.length"
                  v-auth="'admin:operation-log:delete'"
                  danger
                  type="primary"
                  @click="handleBatchDelete"
                >
                  <DeleteOutlined />
                  批量删除
                </a-button>
                <a-button @click="handleExport">
                  <ExportOutlined />
                  导出
                </a-button>
              </div>
              <TableSetting class="table-header__toolbar-desktop" />
            </div>
          </div>
        </div>
      </div>

      <a-table
        :columns="visibleColumns"
        :data-source="tableData"
        :loading="loading"
        :pagination="pagination"
        :row-selection="rowSelection"
        :size="tableSettingState.size"
        row-key="id"
        @change="handleTableChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, h } from 'vue'
import {
  SearchOutlined,
  ReloadOutlined,
  DeleteOutlined,
  ExportOutlined
} from '@ant-design/icons-vue'
import { message, Modal, Button, Tag, Popover } from 'ant-design-vue'
import type { Dayjs } from 'dayjs'
import dayjs from 'dayjs'
import { getOperateLogList, clearAllOperateLog, deleteOperateLog } from '@/api/operateLog'
import type { OperateLogInfo, OperateLogQuery } from '@/api/operateLog'
import { createPagination, type TablePaginationConfig } from '@/utils/common'
import TableSetting from '@/components/TableSetting/TableSetting.vue'
import {
  useTableSetting,
  createTableSettingContext
} from '@/components/TableSetting/useTableSetting'
import type { ColumnItem } from '@/components/TableSetting/types'
import { getUserList } from '@/api/user'

const loading = ref(false)
const tableData = ref<OperateLogInfo[]>([])
const selectedRowKeys = ref<number[]>([])
const wrapRef = ref<HTMLElement | null>(null)
const userOptions = ref<{ id: number; username: string }[]>([])

const searchForm = reactive({
  user_id: undefined as number | undefined,
  method: undefined as string | undefined,
  ip: '',
  dateRange: undefined as [Dayjs, Dayjs] | undefined
})

const pagination = reactive(createPagination())

const datePresets = [
  { label: '今天', value: [dayjs(), dayjs()] as [Dayjs, Dayjs] },
  {
    label: '本周',
    value: [dayjs().startOf('week').add(1, 'day'), dayjs().endOf('week').add(1, 'day')] as [
      Dayjs,
      Dayjs
    ]
  },
  { label: '本月', value: [dayjs().startOf('month'), dayjs().endOf('month')] as [Dayjs, Dayjs] },
  {
    label: '上月',
    value: [
      dayjs().subtract(1, 'month').startOf('month'),
      dayjs().subtract(1, 'month').endOf('month')
    ] as [Dayjs, Dayjs]
  }
]

const columnItems: ColumnItem[] = [
  { key: 'id', title: 'ID', dataIndex: 'id', width: 80 },
  {
    key: 'username',
    title: '操作人',
    dataIndex: 'username',
    width: 120,
    customRender: ({ record }: { record: OperateLogInfo }) =>
      h(Button, { type: 'link', size: 'small', style: { padding: '0' } }, () => record.username)
  },
  { key: 'module', title: '操作模块', dataIndex: 'module', width: 140 },
  { key: 'action', title: '操作功能', dataIndex: 'action', width: 140 },
  { key: 'url', title: '请求地址', dataIndex: 'url', width: 180 },
  {
    key: 'method',
    title: '请求方式',
    dataIndex: 'method',
    width: 100,
    align: 'center',
    customRender: ({ record }: { record: OperateLogInfo }) =>
      h(Tag, { color: methodColor(record.method) }, () => record.method)
  },
  { key: 'ip', title: 'IP地址', dataIndex: 'ip', width: 140 },
  {
    key: 'param',
    title: '请求参数',
    dataIndex: 'param',
    width: 100,
    align: 'center',
    customRender: ({ record }: { record: OperateLogInfo }) =>
      h(
        Popover,
        { placement: 'top' },
        {
          content: () =>
            h('highlightjs', { autodetect: true, code: record.param || '{}', class: 'code' }),
          default: () => h(Tag, { color: 'blue', class: 'text-[12px]' }, () => '查看')
        }
      )
  },
  {
    key: 'status',
    title: '状态',
    dataIndex: 'status',
    width: 80,
    align: 'center',
    customRender: ({ record }: { record: OperateLogInfo }) =>
      h(Tag, { color: record.status === 1 ? 'green' : 'red' }, () =>
        record.status === 1 ? '成功' : '失败'
      )
  },
  { key: 'create_time', title: '操作时间', dataIndex: 'create_time', width: 180 }
]

const {
  state: tableSettingState,
  getVisibleColumns,
  getPopupContainer,
  wrapRef: settingWrapRef
} = useTableSetting({
  columns: columnItems,
  onRefresh: () => {
    fetchData()
  },
  wrapRef
})

createTableSettingContext({
  state: tableSettingState,
  actions: {
    refresh: () => fetchData(),
    toggleFullscreen: () => {
      tableSettingState.isFullscreen = !tableSettingState.isFullscreen
    },
    changeSize: (size) => {
      tableSettingState.size = size
    },
    setColumns: (columns) => {
      tableSettingState.columns = columns
    },
    resetColumns: () => {
      tableSettingState.columns = columnItems.map((col) => ({ ...col }))
    }
  },
  wrapRef: settingWrapRef,
  getVisibleColumns,
  getPopupContainer
})

const visibleColumns = computed(() => {
  return getVisibleColumns.value.map((col) => ({
    title: col.title,
    dataIndex: col.dataIndex,
    key: col.key,
    width: col.width,
    align: col.align as 'left' | 'center' | 'right' | undefined
  }))
})

const rowSelection = computed(() => ({
  selectedRowKeys: selectedRowKeys.value,
  onChange: (keys: number[]) => {
    selectedRowKeys.value = keys
  }
}))

const methodColor = (method: string) => {
  const colors: Record<string, string> = {
    GET: 'green',
    POST: 'blue',
    PUT: 'orange',
    DELETE: 'red'
  }
  return colors[method] || 'default'
}

const fetchUserOptions = async () => {
  try {
    const res = await getUserList({ page: 1, limit: 1000 })
    userOptions.value = (res.data.list || []).map((u: any) => ({
      id: u.id,
      username: u.username
    }))
  } catch {
    userOptions.value = []
  }
}

const fetchData = async () => {
  loading.value = true
  try {
    const params: OperateLogQuery = {
      page: pagination.current,
      limit: pagination.pageSize,
      user_id: searchForm.user_id,
      method: searchForm.method || undefined,
      ip: searchForm.ip || undefined
    }
    if (searchForm.dateRange) {
      params.start_date = searchForm.dateRange[0].format('YYYY-MM-DD')
      params.end_date = searchForm.dateRange[1].format('YYYY-MM-DD')
    }
    const res = await getOperateLogList(params)
    tableData.value = res.data.list.map((l: OperateLogInfo) => ({ ...l, id: Number(l.id) }))
    pagination.total = res.data.pagination.total
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[OperationLogPage] fetchData failed:', error)
    // error handled by request interceptor
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  pagination.current = 1
  fetchData()
}

const handleReset = () => {
  searchForm.user_id = undefined
  searchForm.method = undefined
  searchForm.ip = ''
  searchForm.dateRange = undefined
  pagination.current = 1
  fetchData()
}

const handleTableChange = (pag: TablePaginationConfig) => {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  fetchData()
}

const handleClear = () => {
  Modal.confirm({
    title: '确定要清除所有日志记录吗？',
    content: '此操作不可恢复。',
    okText: '确认',
    cancelText: '取消',
    okType: 'danger',
    onOk: async () => {
      try {
        await clearAllOperateLog()
        message.success('日志已成功清除')
        selectedRowKeys.value = []
        pagination.current = 1
        await fetchData()
      } catch (error: any) {
        message.error(error?.message || '清除日志失败，请稍后重试')
      }
    }
  })
}

const handleBatchDelete = () => {
  if (!selectedRowKeys.value.length) {
    message.warning('请先选择要删除的日志')
    return
  }
  Modal.confirm({
    title: `确定删除选中的 ${selectedRowKeys.value.length} 条日志吗?`,
    content: '删除后将无法恢复，请谨慎操作',
    okType: 'danger',
    onOk: async () => {
      try {
        const res = await deleteOperateLog(selectedRowKeys.value)
        const count = res.data?.deleted_count ?? 0
        message.success(`已删除 ${count} 条操作日志`)
        selectedRowKeys.value = []
        await fetchData()
      } catch (error: any) {
        message.error(error?.message || '删除失败，请稍后重试')
      }
    }
  })
}

const handleExport = () => {
  const data = selectedRowKeys.value.length
    ? tableData.value.filter((item) => selectedRowKeys.value.includes(item.id))
    : tableData.value
  const headers = [
    'ID',
    '操作人',
    '操作模块',
    '操作功能',
    '请求地址',
    '请求方式',
    'IP地址',
    '请求参数',
    '状态',
    '操作时间'
  ]
  const rows = data.map((item) => [
    item.id,
    item.username,
    item.module,
    item.action,
    item.url,
    item.method,
    item.ip,
    item.param,
    item.status === 1 ? '成功' : '失败',
    item.create_time
  ])
  const csvContent = [headers.join(','), ...rows.map((row) => row.join(','))].join('\n')
  const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `operate_logs_${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
  message.success('导出成功')
}

onMounted(() => {
  fetchUserOptions()
  fetchData()
})
</script>

<style lang="less" scoped>
.page-container {
  .search-card {
    background: var(--ant-color-bg-container, #fff);
    border-radius: var(--ant-border-radius, 8px);
    padding: 16px;
    margin-bottom: 16px;

    :deep(.ant-form-item) {
      margin-bottom: 0;
    }
  }

  .s-table-wrapper {
    background: var(--ant-color-bg-container, #fff);
    border-radius: var(--ant-border-radius, 8px);
    padding: 16px;
  }

  .s-table-header {
    margin-bottom: 16px;
  }

  .table-header-container {
    width: 100%;
    padding: 0;
  }

  .table-header-toolbar {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;

    div > * {
      margin-right: 8px;
    }
  }

  .table-header__toolbar-desktop {
    margin-left: auto;
  }

  &.fullscreen-table {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    background: var(--ant-color-bg-container, #fff);
    padding: 16px;
    display: flex;
    flex-direction: column;
    overflow: visible;

    .search-card {
      flex-shrink: 0;
    }

    .s-table-wrapper {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 0;
    }

    .s-table-header {
      flex-shrink: 0;
    }

    :deep(.ant-table-wrapper) {
      flex: 1;
      overflow: auto;
    }
  }
}

@media (max-width: 480px) {
  .page-container {
    :deep(.ant-table) {
      width: 100%;
      overflow-x: auto;
    }
  }
}

:deep(.hljs) {
  background: #f0f0f0;
  max-width: 400px;
  max-height: 300px;
  overflow: auto;
}

.code {
  border-radius: 5px;
  margin: 5px;
  display: flex;
}
</style>
