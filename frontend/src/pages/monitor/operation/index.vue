<!--
  @文件: index.vue
  @用途: 操作日志管理页面，提供系统操作日志的查询、清除、批量删除和导出功能
  @描述: 支持按操作人、请求方式、IP地址、操作时间范围进行多条件筛选，
         表格展示日志详情，请求参数列通过"查看"标签 + Popover 悬停展示 highlightjs 高亮的 JSON，
         集成 TableSetting 组件支持列配置、全屏、密度调整等表格个性化设置。
  @核心逻辑:
    1. 支持按操作人、请求方式、IP地址、操作时间范围进行多条件筛选
    2. 表格展示日志详情，请求参数列"查看"标签悬停 Popover 展示高亮 JSON
    3. 集成 TableSetting 组件，支持列配置、全屏、密度调整等表格个性化设置
    4. 支持选中行批量删除及全量日志清除（二次确认）
    5. 支持将日志数据导出为 CSV 文件（选中导出或全量导出）
-->
<template>
  <!-- 页面根容器 -->
  <div ref="wrapRef" class="page-container">
    <!-- 搜索区域：多条件筛选表单 -->
    <div class="search-card">
      <a-form layout="inline" :model="searchForm">
        <!-- 操作人筛选 -->
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
        <!-- 请求方式筛选 -->
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
        <!-- IP地址筛选 -->
        <a-form-item label="IP地址" html-for="operate-log-search-ip">
          <a-input
            id="operate-log-search-ip"
            v-model:value="searchForm.ip"
            placeholder="请输入IP地址"
            style="width: 160px"
            allow-clear
          />
        </a-form-item>
        <!-- 操作时间范围筛选（含快捷预设） -->
        <a-form-item label="操作时间" html-for="operate-log-search-time">
          <a-range-picker
            id="operate-log-search-time"
            v-model:value="searchForm.dateRange"
            :presets="datePresets"
            style="width: 280px"
          />
        </a-form-item>
        <!-- 查询、清空与重置按钮 -->
        <a-form-item>
          <a-space>
            <a-button type="primary" @click="handleSearch">
              <SearchOutlined />
              查询
            </a-button>
            <a-button @click="handleClearForm">
              <ClearOutlined />
              清空
            </a-button>
            <a-button @click="handleReset">
              <ReloadOutlined />
              重置
            </a-button>
          </a-space>
        </a-form-item>
      </a-form>
    </div>

    <!-- 表格区域 -->
    <div class="s-table-wrapper">
      <!-- 表格顶部工具栏：清除日志、批量删除、导出、表格设置 -->
      <div class="s-table-header">
        <div class="table-header-container">
          <div class="flex items-center">
            <div class="table-header-toolbar">
              <div>
                <!-- 清除全部日志按钮（需 admin:operation-log:clear 权限） -->
                <a-button
                  v-auth="'admin:operation-log:clear'"
                  danger
                  type="primary"
                  @click="handleClear"
                >
                  <DeleteOutlined />
                  清除日志
                </a-button>
                <!-- 批量删除按钮（仅选中行时显示，需 admin:operation-log:delete 权限） -->
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
                <!-- 导出按钮 -->
                <a-button @click="handleExport">
                  <ExportOutlined />
                  导出
                </a-button>
              </div>
              <!-- 表格个性化设置组件（列配置、密度、全屏等） -->
              <TableSetting class="table-header__toolbar-desktop" />
            </div>
          </div>
        </div>
      </div>

      <!-- 操作日志数据表格 -->
      <a-table
        :columns="visibleColumns"
        :data-source="tableData"
        :loading="loading"
        :pagination="pagination"
        :row-selection="rowSelection"
        :size="tableSettingState.size"
        row-key="id"
        @change="handleTableChange"
      >
        <template #bodyCell="{ text, column }">
          <!-- 请求参数列：悬停"查看"标签显示 highlightjs 高亮的 JSON -->
          <template v-if="column.dataIndex === 'param'">
            <a-popover placement="top">
              <template #content>
                <highlightjs autodetect :code="text || '{}'" class="code" />
              </template>
              <a-tag color="blue" class="text-[12px]">查看</a-tag>
            </a-popover>
          </template>
        </template>
      </a-table>
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
  ClearOutlined
} from '@ant-design/icons-vue'
import { message, Modal, Button, Tag } from 'ant-design-vue'
import type { Dayjs } from 'dayjs'
import dayjs from 'dayjs'
import { getOperateLogList, clearAllOperateLog, deleteOperateLog } from '@/api/operateLog'
import type { OperateLogInfo, OperateLogQuery } from '@/api/operateLog'
import { createPagination, type TablePaginationConfig } from '@/utils/common'
import TableSetting from '@/components/TableSetting/TableSetting.vue'
import { usePageTable } from '@/composables/usePageTable'
import { useExport } from '@/composables'
import type { ColumnItem } from '@/components/TableSetting/types'
import { getUserList } from '@/api/user'

/** 表格加载状态 */
const loading = ref(false)

/** 操作日志列表数据 */
const tableData = ref<OperateLogInfo[]>([])

/** 当前选中行的主键数组，用于批量操作 */
const selectedRowKeys = ref<number[]>([])

/** 页面容器 DOM 引用，供 TableSetting 全屏等功能使用 */
const wrapRef = ref<HTMLElement | null>(null)

/** 操作人下拉选项列表 */
const userOptions = ref<{ id: number; username: string }[]>([])

/** 搜索表单响应式数据 */
const searchForm = reactive({
  /** 操作人用户ID */
  user_id: undefined as number | undefined,
  /** HTTP 请求方式 */
  method: undefined as string | undefined,
  /** IP 地址 */
  ip: '',
  /** 操作时间范围 */
  dateRange: undefined as [Dayjs, Dayjs] | undefined
})

/** 分页配置（响应式） */
const pagination = reactive(createPagination())

/** 日期范围快捷预设选项 */
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

/** 表格列配置项，定义各列的标题、数据字段、宽度及自定义渲染逻辑 */
const columnItems: ColumnItem[] = [
  { key: 'id', title: 'ID', dataIndex: 'id', width: 80 },
  {
    key: 'username',
    title: '操作人',
    dataIndex: 'username',
    width: 120,
    /* 操作人渲染为链接按钮样式 */
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
    /* 请求方式渲染为彩色标签，颜色由 methodColor 函数决定 */
    customRender: ({ record }: { record: OperateLogInfo }) =>
      h(Tag, { color: methodColor(record.method) }, () => record.method)
  },
  { key: 'ip', title: 'IP地址', dataIndex: 'ip', width: 140 },
  {
    key: 'param',
    title: '请求参数',
    dataIndex: 'param',
    width: 100,
    align: 'center'
  },
  {
    key: 'status',
    title: '状态',
    dataIndex: 'status',
    width: 80,
    align: 'center',
    /* 状态渲染为标签：1-成功(绿色)，其他-失败(红色) */
    customRender: ({ record }: { record: OperateLogInfo }) =>
      h(Tag, { color: record.status === 1 ? 'green' : 'red' }, () =>
        record.status === 1 ? '成功' : '失败'
      )
  },
  { key: 'create_time', title: '操作时间', dataIndex: 'create_time', width: 180 }
]

/**
 * 获取操作日志列表数据
 * @description 根据当前搜索条件和分页参数请求日志数据，并更新表格和分页状态
 */
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
    /* 若选择了时间范围，格式化为 YYYY-MM-DD 传入查询参数 */
    if (searchForm.dateRange) {
      params.start_date = searchForm.dateRange[0].format('YYYY-MM-DD')
      params.end_date = searchForm.dateRange[1].format('YYYY-MM-DD')
    }
    const res = await getOperateLogList(params)
    tableData.value = res.data.list.map((l: OperateLogInfo) => ({ ...l, id: Number(l.id) }))
    pagination.total = res.data.pagination.total
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[OperationLogPage] fetchData failed:', error)
    // 错误由请求拦截器统一处理
  } finally {
    loading.value = false
  }
}

/** usePageTable 组合式函数：管理表格列显隐和表格设置状态 */
const { tableSettingState, visibleColumns } = usePageTable({
  columns: columnItems,
  fetchData,
  wrapRef
})

/** useExport 组合式函数：CSV 导出工具 */
const { downloadCsv, escapeCsvField } = useExport()

/** 表格行选择配置（复选框） */
const rowSelection = computed(() => ({
  selectedRowKeys: selectedRowKeys.value,
  onChange: (keys: number[]) => {
    selectedRowKeys.value = keys
  }
}))

/**
 * 根据 HTTP 请求方式返回对应的标签颜色
 * @param method - HTTP 请求方式（GET/POST/PUT/DELETE）
 * @returns Ant Design Tag 组件支持的颜色值
 */
const methodColor = (method: string) => {
  const colors: Record<string, string> = {
    GET: 'green',
    POST: 'blue',
    PUT: 'orange',
    DELETE: 'red'
  }
  return colors[method] || 'default'
}

/**
 * 获取操作人下拉选项列表
 * @description 请求用户列表接口，映射为 { id, username } 格式供选择器使用
 */
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

/**
 * 搜索按钮回调
 * @description 重置页码为第一页并重新请求数据
 */
const handleSearch = () => {
  pagination.current = 1
  fetchData()
}

/**
 * 清空搜索表单
 * @description 仅清除所有搜索条件，不重新请求数据
 */
const handleClearForm = () => {
  searchForm.user_id = undefined
  searchForm.method = undefined
  searchForm.ip = ''
  searchForm.dateRange = undefined
  message.success('搜索条件已清空')
}

/**
 * 重置按钮回调
 * @description 清空所有搜索条件，重置页码并重新请求数据
 */
const handleReset = () => {
  searchForm.user_id = undefined
  searchForm.method = undefined
  searchForm.ip = ''
  searchForm.dateRange = undefined
  pagination.current = 1
  fetchData()
}

/**
 * 表格分页/排序变化回调
 * @param pag - Ant Design 表格分页配置对象
 */
const handleTableChange = (pag: TablePaginationConfig) => {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  fetchData()
}

/**
 * 清除全部操作日志
 * @description 弹出二次确认对话框，确认后调用清除接口并刷新数据
 */
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
      } catch (error: unknown) {
        const messageText = error instanceof Error ? error.message : '清除日志失败，请稍后重试'
        message.error(messageText)
      }
    }
  })
}

/**
 * 批量删除选中日志
 * @description 校验是否已选中行，弹出二次确认对话框，确认后调用删除接口并刷新数据
 */
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
      } catch (error: unknown) {
        const msg = error instanceof Error ? error.message : '删除失败，请稍后重试'
        message.error(msg)
      }
    }
  })
}

/**
 * 导出操作日志为 CSV 文件
 * @description 若有选中行则仅导出选中数据，否则导出当前页全部数据。
 *              使用 downloadCsv 生成带 BOM 头的 UTF-8 CSV 文件并触发浏览器下载
 */
const handleExport = () => {
  /* 根据是否选中行决定导出数据范围 */
  const data = selectedRowKeys.value.length
    ? tableData.value.filter((item) => selectedRowKeys.value.includes(item.id))
    : tableData.value
  /* CSV 表头 */
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
  /* CSV 数据行，状态字段转换为中文 */
  const rows = data.map((item) => [
    escapeCsvField(item.id),
    escapeCsvField(item.username),
    escapeCsvField(item.module),
    escapeCsvField(item.action),
    escapeCsvField(item.url),
    escapeCsvField(item.method),
    escapeCsvField(item.ip),
    escapeCsvField(item.param),
    escapeCsvField(item.status === 1 ? '成功' : '失败'),
    escapeCsvField(item.create_time)
  ])
  downloadCsv({ filename: 'operate_logs', headers, rows })
  message.success('导出成功')
}

/** 组件挂载时初始化：加载操作人选项和日志数据 */
onMounted(() => {
  fetchUserOptions()
  fetchData()
})
</script>

<style lang="less" scoped>
/* 代码高亮弹窗中的样式 */
:deep(.hljs) {
  background: #f0f0f0;
  max-width: 400px;
  max-height: 300px;
  overflow: auto;
}

/* 请求参数代码块样式 */
.code {
  border-radius: 5px;
  margin: 5px;
  display: flex;
}
</style>
