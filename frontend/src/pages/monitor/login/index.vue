<template>
  <div ref="wrapRef" class="page-container">
    <div class="search-card">
      <a-form layout="inline" :model="searchForm">
        <a-form-item label="关键词" html-for="login-log-search-keyword">
          <a-input-group compact>
            <a-select
              id="login-log-search-keyword"
              v-model:value="searchForm.keywordField"
              style="width: 110px"
            >
              <a-select-option value="username">用户名</a-select-option>
              <a-select-option value="ip">IP地址</a-select-option>
            </a-select>
            <a-input
              v-model:value="searchForm.keyword"
              :placeholder="keywordPlaceholder"
              style="width: 200px"
              allow-clear
              @press-enter="handleSearch"
            />
          </a-input-group>
        </a-form-item>
        <a-form-item label="状态" html-for="login-log-search-status">
          <a-select
            id="login-log-search-status"
            v-model:value="searchForm.status"
            name="status"
            placeholder="全部状态"
            allow-clear
            style="width: 120px"
          >
            <a-select-option :value="1">成功</a-select-option>
            <a-select-option :value="0">失败</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="登录时间" html-for="login-log-search-time">
          <a-range-picker
            id="login-log-search-time"
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
                  v-auth="'admin:login-log:clear'"
                  danger
                  type="primary"
                  @click="handleClean"
                >
                  <DeleteOutlined />
                  清除日志
                </a-button>
                <a-button
                  v-if="selectedRowKeys.length"
                  v-auth="'admin:login-log:delete'"
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
  ExportOutlined,
  AppleFilled,
  WindowsFilled
} from '@ant-design/icons-vue'
import { message, Modal } from 'ant-design-vue'
import type { Dayjs } from 'dayjs'
import dayjs from 'dayjs'
import { getLoginLogList, clearLoginLog, deleteLoginLog } from '@/api/loginLog'
import type { LoginLogInfo, LoginLogQuery } from '@/api/loginLog'
import { createPagination, type TablePaginationConfig } from '@/utils/common'
import TableSetting from '@/components/TableSetting/TableSetting.vue'
import {
  useTableSetting,
  createTableSettingContext
} from '@/components/TableSetting/useTableSetting'
import type { ColumnItem } from '@/components/TableSetting/types'

const loading = ref(false)
const tableData = ref<LoginLogInfo[]>([])
const selectedRowKeys = ref<number[]>([])
const wrapRef = ref<HTMLElement | null>(null)

const searchForm = reactive({
  keywordField: 'username',
  keyword: '',
  status: undefined as number | undefined,
  dateRange: undefined as [Dayjs, Dayjs] | undefined
})

const pagination = reactive(createPagination())

const isMac = (os: string) => {
  return os?.toLowerCase().includes('mac')
}

const keywordPlaceholder = computed(() => {
  return searchForm.keywordField === 'username' ? '请输入用户名搜索' : '请输入IP地址搜索'
})

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
    title: '登录账号',
    dataIndex: 'username',
    width: 120,
    customRender: ({ record }: { record: LoginLogInfo }) =>
      h('a-button', { type: 'link', size: 'small', style: { padding: '0' } }, () => record.username)
  },
  { key: 'ip', title: '登录IP', dataIndex: 'ip', width: 140 },
  {
    key: 'os',
    title: '操作系统',
    dataIndex: 'os',
    width: 150,
    customRender: ({ record }: { record: LoginLogInfo }) => {
      const isMacOS = isMac(record.os)
      return h('span', {}, [
        h(isMacOS ? AppleFilled : WindowsFilled, {
          style: {
            color: isMacOS ? '#aaa' : '#40a9ff',
            fontSize: '18px',
            verticalAlign: 'middle'
          }
        }),
        h('span', { style: { verticalAlign: 'middle' } }, record.os)
      ])
    }
  },
  { key: 'browser', title: '浏览器', dataIndex: 'browser', width: 140 },
  {
    key: 'status',
    title: '登录状态',
    dataIndex: 'status',
    width: 100,
    align: 'center',
    customRender: ({ record }: { record: LoginLogInfo }) =>
      h('a-tag', { color: record.status === 1 ? 'green' : 'red' }, () =>
        record.status === 1 ? '成功' : '失败'
      )
  },
  { key: 'login_time', title: '登录时间', dataIndex: 'login_time', width: 180 }
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

const fetchData = async () => {
  loading.value = true
  try {
    const params: LoginLogQuery = {
      page: pagination.current,
      limit: pagination.pageSize,
      keyword: searchForm.keyword || undefined,
      status: searchForm.status
    }
    if (searchForm.dateRange) {
      params.start_date = searchForm.dateRange[0].format('YYYY-MM-DD')
      params.end_date = searchForm.dateRange[1].format('YYYY-MM-DD')
    }
    const res = await getLoginLogList(params)
    tableData.value = res.data.list.map((l: LoginLogInfo) => ({ ...l, id: Number(l.id) }))
    pagination.total = res.data.pagination.total
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[LoginLogPage] fetchData failed:', error)
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
  searchForm.keywordField = 'username'
  searchForm.keyword = ''
  searchForm.status = undefined
  searchForm.dateRange = undefined
  pagination.current = 1
  fetchData()
}

const handleTableChange = (pag: TablePaginationConfig) => {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  fetchData()
}

const handleClean = () => {
  Modal.confirm({
    title: '确定要清除所有日志记录吗？',
    content: '此操作不可恢复。',
    okText: '确认',
    cancelText: '取消',
    okType: 'danger',
    onOk: async () => {
      try {
        await clearLoginLog()
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
        const res = await deleteLoginLog(selectedRowKeys.value)
        const count = res.data?.deleted_count ?? 0
        message.success(`已删除 ${count} 条登录日志`)
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
  const headers = ['ID', '登录账号', '登录IP', '操作系统', '浏览器', '登录状态', '登录时间']
  const rows = data.map((item) => [
    item.id,
    item.username,
    item.ip,
    item.os,
    item.browser,
    item.status === 1 ? '成功' : '失败',
    item.login_time
  ])
  const csvContent = [headers.join(','), ...rows.map((row) => row.join(','))].join('\n')
  const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `login_logs_${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
  message.success('导出成功')
}

onMounted(() => {
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
</style>
