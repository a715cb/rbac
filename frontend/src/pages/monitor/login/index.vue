<!--
  @文件: index.vue
  @用途: 登录日志管理页面，提供登录日志的查询、筛选、清除、批量删除及导出功能
  @描述: 展示系统所有用户的登录记录，支持按用户名/IP/状态/时间范围筛选，
         表格列包含ID、登录账号、登录IP、操作系统、浏览器、登录状态、登录时间，
         操作栏支持清除全部日志、批量删除选中日志、导出CSV文件。
  @核心逻辑:
    1. 通过 searchForm 收集筛选条件，调用 getLoginLogList 获取分页数据
    2. 使用 useTableSetting 管理表格列显隐、密度、全屏等设置
    3. 导出功能支持选中导出与全量导出，生成带BOM的UTF-8 CSV文件
-->
<template>
  <!-- 页面根容器 -->
  <div ref="wrapRef" class="page-container">
    <!-- 搜索筛选区域 -->
    <div class="search-card">
      <a-form layout="inline" :model="searchForm">
        <!-- 关键词搜索：支持按用户名或IP地址搜索 -->
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
        <!-- 登录状态筛选：成功/失败 -->
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
        <!-- 登录时间范围筛选：支持快捷预设 -->
        <a-form-item label="登录时间" html-for="login-log-search-time">
          <a-range-picker
            id="login-log-search-time"
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
      <!-- 表格顶部工具栏 -->
      <div class="s-table-header">
        <div class="table-header-container">
          <div class="flex items-center">
            <div class="table-header-toolbar">
              <div>
                <!-- 清除全部日志按钮（需 admin:login-log:clear 权限） -->
                <a-button
                  v-auth="'admin:login-log:clear'"
                  danger
                  type="primary"
                  @click="handleClean"
                >
                  <DeleteOutlined />
                  清除日志
                </a-button>
                <!-- 批量删除按钮：仅选中行时显示（需 admin:login-log:delete 权限） -->
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
                <!-- 导出CSV按钮 -->
                <a-button @click="handleExport">
                  <ExportOutlined />
                  导出
                </a-button>
              </div>
              <!-- 表格设置组件（列显隐、密度、全屏等） -->
              <TableSetting class="table-header__toolbar-desktop" />
            </div>
          </div>
        </div>
      </div>

      <!-- 登录日志数据表格 -->
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
  WindowsFilled,
  ClearOutlined
} from '@ant-design/icons-vue'
import { message, Modal, Button, Tag } from 'ant-design-vue'
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

/** 表格数据加载状态 */
const loading = ref(false)

/** 登录日志列表数据 */
const tableData = ref<LoginLogInfo[]>([])

/** 表格多选选中的行ID列表 */
const selectedRowKeys = ref<number[]>([])

/** 页面根容器DOM引用，用于表格设置组件的弹窗定位 */
const wrapRef = ref<HTMLElement | null>(null)

/** 搜索表单状态 */
const searchForm = reactive({
  /** 关键词搜索字段类型：username 或 ip */
  keywordField: 'username',
  /** 关键词搜索内容 */
  keyword: '',
  /** 登录状态筛选：1=成功, 0=失败, undefined=全部 */
  status: undefined as number | undefined,
  /** 登录时间范围 */
  dateRange: undefined as [Dayjs, Dayjs] | undefined
})

/** 分页配置 */
const pagination = reactive(createPagination())

/**
 * 判断操作系统是否为 macOS
 * @param os - 操作系统标识字符串
 * @returns 是否为 macOS 系统
 */
const isMac = (os: string) => {
  return os?.toLowerCase().includes('mac')
}

/** 关键词输入框占位提示文本，根据所选字段类型动态切换 */
const keywordPlaceholder = computed(() => {
  return searchForm.keywordField === 'username' ? '请输入用户名搜索' : '请输入IP地址搜索'
})

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

/** 表格列配置项，定义各列的渲染方式 */
const columnItems: ColumnItem[] = [
  { key: 'id', title: 'ID', dataIndex: 'id', width: 80 },
  {
    key: 'username',
    title: '登录账号',
    dataIndex: 'username',
    width: 120,
    /** 登录账号渲染为链接样式按钮 */
    customRender: ({ record }: { record: LoginLogInfo }) =>
      h(Button, { type: 'link', size: 'small', style: { padding: '0' } }, () => record.username)
  },
  { key: 'ip', title: '登录IP', dataIndex: 'ip', width: 140 },
  {
    key: 'os',
    title: '操作系统',
    dataIndex: 'os',
    width: 150,
    /** 操作系统列：根据OS类型显示对应图标（Apple/Windows） */
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
    title: '状态',
    dataIndex: 'status',
    width: 100,
    align: 'center',
    /** 登录状态列：成功显示绿色标签，失败显示红色标签 */
    customRender: ({ record }: { record: LoginLogInfo }) =>
      h(Tag, { color: record.status === 1 ? 'green' : 'red' }, () =>
        record.status === 1 ? '成功' : '失败'
      )
  },
  { key: 'login_time', title: '登录时间', dataIndex: 'login_time', width: 180 }
]

/** 初始化表格设置（列显隐、密度、全屏等） */
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

/** 创建表格设置上下文，供 TableSetting 子组件访问状态和操作方法 */
createTableSettingContext({
  state: tableSettingState,
  actions: {
    /** 刷新表格数据 */
    refresh: () => fetchData(),
    /** 切换全屏显示 */
    toggleFullscreen: () => {
      tableSettingState.isFullscreen = !tableSettingState.isFullscreen
    },
    /** 切换表格密度 */
    changeSize: (size) => {
      tableSettingState.size = size
    },
    /** 设置可见列配置 */
    setColumns: (columns) => {
      tableSettingState.columns = columns
    },
    /** 重置列配置为默认值 */
    resetColumns: () => {
      tableSettingState.columns = columnItems.map((col) => ({ ...col }))
    }
  },
  wrapRef: settingWrapRef,
  getVisibleColumns,
  getPopupContainer
})

/** 当前可见的表格列，根据用户设置过滤后映射为 a-table 所需格式（保留 customRender 等渲染属性） */
const visibleColumns = computed(() => {
  return getVisibleColumns.value.map((col) => ({
    ...col,
    align: col.align as 'left' | 'center' | 'right' | undefined
  }))
})

/** 表格行选择配置，控制多选行为 */
const rowSelection = computed(() => ({
  selectedRowKeys: selectedRowKeys.value,
  onChange: (keys: number[]) => {
    selectedRowKeys.value = keys
  }
}))

/**
 * 获取登录日志列表数据
 * @description 根据当前搜索条件和分页参数请求后端接口，更新表格数据
 */
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

/**
 * 执行搜索查询
 * @description 重置页码为第1页后重新获取数据
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
  searchForm.keywordField = 'username'
  searchForm.keyword = ''
  searchForm.status = undefined
  searchForm.dateRange = undefined
  message.success('搜索条件已清空')
}

/**
 * 重置搜索条件
 * @description 将所有搜索条件恢复默认值，重置页码后重新获取数据
 */
const handleReset = () => {
  searchForm.keywordField = 'username'
  searchForm.keyword = ''
  searchForm.status = undefined
  searchForm.dateRange = undefined
  pagination.current = 1
  fetchData()
}

/**
 * 处理表格分页变化
 * @param pag - 分页配置信息
 */
const handleTableChange = (pag: TablePaginationConfig) => {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  fetchData()
}

/**
 * 清除全部登录日志
 * @description 弹出确认对话框，确认后调用清除接口并刷新数据
 */
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

/**
 * 批量删除选中的登录日志
 * @description 校验是否已选中行，弹出确认对话框后调用删除接口并刷新数据
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

/**
 * 导出登录日志为CSV文件
 * @description 若有选中行则仅导出选中数据，否则导出当前页全部数据。
 *              生成带BOM头的UTF-8 CSV文件以确保中文兼容性
 */
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
  /* 添加BOM头(\ufeff)确保Excel正确识别UTF-8编码 */
  const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `login_logs_${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
  message.success('导出成功')
}

/** 页面挂载时加载初始数据 */
onMounted(() => {
  fetchData()
})
</script>

<style lang="less" scoped>
.page-container {
  /* 搜索区域卡片样式 */
  .search-card {
    background: var(--ant-color-bg-container, #fff);
    border-radius: var(--ant-border-radius, 8px);
    padding: 16px;
    margin-bottom: 16px;

    /* 搜索区域内表单项去除底部间距 */
    :deep(.ant-form-item) {
      margin-bottom: 0;
    }
  }

  /* 表格区域容器样式 */
  .s-table-wrapper {
    background: var(--ant-color-bg-container, #fff);
    border-radius: var(--ant-border-radius, 8px);
    padding: 16px;
  }

  /* 表格顶部工具栏与表格的间距 */
  .s-table-header {
    margin-bottom: 16px;
  }

  .table-header-container {
    width: 100%;
    padding: 0;
  }

  /* 工具栏布局：操作按钮左对齐，设置组件右对齐 */
  .table-header-toolbar {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;

    div > * {
      margin-right: 8px;
    }
  }

  /* 桌面端表格设置组件靠右对齐 */
  .table-header__toolbar-desktop {
    margin-left: auto;
  }

  /* 全屏模式样式：固定定位覆盖整个视口 */
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

    /* 全屏模式下表格区域自适应剩余空间 */
    :deep(.ant-table-wrapper) {
      flex: 1;
      overflow: auto;
    }
  }
}

/* 移动端小屏幕适配：表格横向可滚动 */
@media (max-width: 480px) {
  .page-container {
    :deep(.ant-table) {
      width: 100%;
      overflow-x: auto;
    }
  }
}
</style>
