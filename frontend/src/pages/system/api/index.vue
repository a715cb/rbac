<!--
  @文件: index.vue
  @用途: 接口管理页面
  @描述: 系统管理模块下的接口（API）管理页面，提供接口的完整 CRUD 功能
  @核心逻辑:
    1. 搜索筛选 - 支持按关键词、请求方法、分组、状态多维度筛选接口
    2. 数据展示 - 表格展示接口列表，请求方法以彩色标签区分，路径以代码样式展示
    3. 状态切换 - 通过开关组件直接切换接口启用/禁用状态，失败时自动回滚
    4. 新增/编辑 - 共用 ApiFormModal 弹窗，通过 currentRecord 是否为 null 区分模式
    5. 表格设置 - 支持列显隐、密度调整、全屏模式
-->
<template>
  <!-- 页面根容器，供全屏表格等功能获取 DOM 引用 -->
  <div ref="wrapRef" class="page-container">
    <!-- 搜索区域：支持按关键词、请求方法、分组、状态筛选接口 -->
    <div class="search-card">
      <a-form layout="inline" :model="searchForm">
        <a-form-item label="关键词" html-for="api-search-keyword">
          <a-input
            id="api-search-keyword"
            v-model:value="searchForm.keyword"
            name="keyword"
            placeholder="接口名称/标识/路径"
            allow-clear
            @press-enter="handleSearch"
          />
        </a-form-item>
        <a-form-item label="请求方法" html-for="api-search-method">
          <a-select
            id="api-search-method"
            v-model:value="searchForm.method"
            name="method"
            placeholder="全部方法"
            allow-clear
            style="width: 120px"
          >
            <a-select-option v-for="m in HTTP_METHODS" :key="m.value" :value="m.value">
              {{ m.label }}
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="分组" html-for="api-search-group">
          <a-select
            id="api-search-group"
            v-model:value="searchForm.group"
            name="group"
            placeholder="全部分组"
            allow-clear
            style="width: 140px"
          >
            <a-select-option v-for="group in groupList" :key="group" :value="group">
              {{ group }}
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="状态" html-for="api-search-status">
          <a-select
            id="api-search-status"
            v-model:value="searchForm.status"
            name="status"
            placeholder="全部状态"
            allow-clear
            style="width: 120px"
          >
            <a-select-option :value="1">正常</a-select-option>
            <a-select-option :value="0">禁用</a-select-option>
          </a-select>
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

    <!-- 表格区域：展示接口列表，支持新增、编辑、删除、状态切换 -->
    <div class="s-table-wrapper">
      <!-- 表格顶部工具栏：新增按钮 + 表格设置（列显隐、密度、全屏） -->
      <div class="s-table-header">
        <div class="table-header-container">
          <div class="flex items-center">
            <div class="table-header-toolbar">
              <div>
                <a-button type="primary" @click="handleAdd">
                  <PlusOutlined />
                  新增
                </a-button>
              </div>
              <!-- 表格设置组件：列显隐、密度、全屏等 -->
              <TableSetting class="table-header__toolbar-desktop" />
            </div>
          </div>
        </div>
      </div>

      <!-- 接口数据表格：横向滚动阈值 1200px，行唯一键为 id -->
      <a-table
        :columns="visibleColumns"
        :data-source="tableData"
        :loading="loading"
        :pagination="pagination"
        :size="tableSettingState.size"
        :scroll="{ x: 1200 }"
        row-key="id"
        @change="handleTableChange"
      >
        <template #bodyCell="{ text, column, record }">
          <!-- 请求方法列：以彩色标签展示不同HTTP方法 -->
          <template v-if="column.dataIndex === 'method'">
            <a-tag :color="methodColor(record.method)">
              {{ record.method }}
            </a-tag>
          </template>
          <!-- 接口路径列：以代码样式展示路径 -->
          <template v-else-if="column.dataIndex === 'path'">
            <code class="api-path">{{ record.path }}</code>
          </template>
          <!-- 状态列：开关组件，直接切换启用/禁用 -->
          <template v-else-if="column.dataIndex === 'status'">
            <a-switch
              :checked="record.status === 1"
              @change="(checked: boolean) => handleStatusChange(record, checked)"
            />
          </template>
          <!-- 操作列：编辑与删除（删除需二次确认） -->
          <template v-else-if="column.dataIndex === 'action'">
            <a-space>
              <a-button type="link" size="small" @click="handleEdit(record)">
                <EditOutlined />
                编辑
              </a-button>
              <a-popconfirm title="确定要删除该接口吗？" @confirm="handleDelete(record)">
                <a-button type="link" danger size="small">
                  <DeleteOutlined />
                  删除
                </a-button>
              </a-popconfirm>
            </a-space>
          </template>
          <!-- 默认列：直接渲染文本内容 -->
          <template v-else>
            {{ text }}
          </template>
        </template>
      </a-table>
    </div>

    <!-- 接口表单弹窗：新增/编辑共用，通过 currentRecord 区分模式 -->
    <ApiFormModal v-model:visible="modalVisible" :record="currentRecord" @success="handleSearch" />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import {
  SearchOutlined,
  ReloadOutlined,
  PlusOutlined,
  EditOutlined,
  DeleteOutlined
} from '@ant-design/icons-vue'
import { message } from 'ant-design-vue'
import { getApiList, deleteApi, changeApiStatus, HTTP_METHODS } from '@/api/api'
import type { ApiInfo, ApiQuery } from '@/api/api'
import { createPagination, type TablePaginationConfig } from '@/utils/common'
import ApiFormModal from './components/ApiFormModal.vue'
import TableSetting from '@/components/TableSetting/TableSetting.vue'
import { usePageTable } from '@/composables/usePageTable'
import type { ColumnItem } from '@/components/TableSetting/types'

/** 表格加载状态 */
const loading = ref(false)
/** 接口列表数据 */
const tableData = ref<ApiInfo[]>([])
/** 分组下拉选项列表，由后端接口动态获取 */
const groupList = ref<string[]>([])
/** 表单弹窗可见性控制 */
const modalVisible = ref(false)
/** 当前操作的记录，null 表示新增模式，非 null 表示编辑模式 */
const currentRecord = ref<ApiInfo | null>(null)
/** 页面容器 DOM 引用，供全屏表格等功能使用 */
const wrapRef = ref<HTMLElement | null>(null)

/** 搜索表单，字段与后端 ApiQuery 接口对齐 */
const searchForm = reactive<ApiQuery>({
  page: 1,
  limit: 10,
  keyword: '',
  method: undefined,
  group: undefined,
  status: undefined
})

/** 分页配置，由 createPagination 工具函数初始化默认值 */
const pagination = reactive(createPagination())

/**
 * 表格列定义
 * key - 列唯一标识，用于列显隐控制
 * title - 列标题
 * dataIndex - 对应数据字段名
 * width - 列宽（px）
 * align - 对齐方式
 * fixed - 固定列位置
 */
const columnItems: ColumnItem[] = [
  { key: 'name', title: '接口名称', dataIndex: 'name', width: 180 },
  { key: 'code', title: '接口标识', dataIndex: 'code', width: 150 },
  { key: 'method', title: '请求方法', dataIndex: 'method', width: 100, align: 'center' },
  { key: 'path', title: '接口路径', dataIndex: 'path', width: 250 },
  { key: 'menu_name', title: '所属菜单', dataIndex: 'menu_name', width: 140 },
  { key: 'group', title: '分组', dataIndex: 'group', width: 120 },
  { key: 'status', title: '状态', dataIndex: 'status', width: 100, align: 'center' },
  { key: 'action', title: '操作', dataIndex: 'action', width: 150, fixed: 'right' }
]

/**
 * 根据请求方法返回对应的标签颜色
 * @param method - HTTP 请求方法（GET/POST/PUT/DELETE）
 * @returns Ant Design Tag 组件的 color 属性值
 */
const methodColor = (method: string): string => {
  return HTTP_METHODS.find((m) => m.value === method)?.color || 'default'
}

/**
 * 获取接口列表数据
 * 将当前分页参数与搜索条件合并后请求后端接口，
 * 同时更新表格数据、分页总数和分组列表。
 * id 统一转为 Number 类型，确保 row-key 比较一致性。
 */
const fetchData = async () => {
  loading.value = true
  try {
    const res = await getApiList({
      page: pagination.current,
      limit: pagination.pageSize,
      keyword: searchForm.keyword,
      method: searchForm.method,
      group: searchForm.group,
      status: searchForm.status
    })
    tableData.value = res.data.list.map((a: ApiInfo) => ({ ...a, id: Number(a.id) }))
    pagination.total = res.data.pagination.total
    groupList.value = res.data.groups || []
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[ApiPage] fetchData failed:', error)
  } finally {
    loading.value = false
  }
}

/**
 * 初始化表格设置（列显隐、密度、全屏等）
 * - tableSettingState: 表格设置状态（密度等）
 * - visibleColumns: 根据用户配置过滤后的可见列
 */
const { tableSettingState, visibleColumns } = usePageTable({
  columns: columnItems,
  fetchData,
  wrapRef
})

/**
 * 点击查询按钮处理
 * 重置页码为 1 后重新拉取数据，确保搜索结果从第一页开始
 */
const handleSearch = () => {
  pagination.current = 1
  fetchData()
}

/**
 * 点击重置按钮处理
 * 清空所有搜索条件，重置页码后重新拉取数据
 */
const handleReset = () => {
  searchForm.keyword = ''
  searchForm.method = undefined
  searchForm.group = undefined
  searchForm.status = undefined
  pagination.current = 1
  fetchData()
}

/**
 * 表格分页/排序变化回调
 * @param pag - Ant Design Table 变更后的分页配置
 */
const handleTableChange = (pag: TablePaginationConfig) => {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  fetchData()
}

/**
 * 点击新增按钮处理
 * 清空当前记录（进入新增模式），打开弹窗
 */
const handleAdd = () => {
  currentRecord.value = null
  modalVisible.value = true
}

/**
 * 点击编辑按钮处理
 * @param record - 当前行的接口数据
 * 将当前行数据赋值给 currentRecord（进入编辑模式），打开弹窗
 */
const handleEdit = (record: ApiInfo) => {
  currentRecord.value = record
  modalVisible.value = true
}

/**
 * 删除接口
 * @param record - 待删除的接口数据，通过 id 调用删除接口
 * 删除成功后刷新列表数据；若当前页仅剩一条数据且非第一页，自动回退到上一页
 */
const handleDelete = async (record: ApiInfo) => {
  try {
    await deleteApi(record.id)
    message.success('删除成功')
    if (tableData.value.length <= 1 && (pagination.current ?? 1) > 1) {
      pagination.current = (pagination.current ?? 2) - 1
    }
    fetchData()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[ApiPage] handleDelete failed:', error)
  }
}

/**
 * 切换接口启用/禁用状态
 * @param record - 目标接口数据
 * @param checked - 开关状态，true 为启用，false 为禁用
 * 将布尔值映射为 1/0 后调用状态变更接口，成功后刷新列表；
 * 失败时自动回滚到原始状态
 */
const handleStatusChange = async (record: ApiInfo, checked: boolean) => {
  const oldStatus = record.status
  record.status = checked ? 1 : 0
  try {
    await changeApiStatus(record.id, checked ? 1 : 0)
    message.success(checked ? '接口已启用' : '接口已禁用')
  } catch (error: unknown) {
    record.status = oldStatus
    if (import.meta.env.DEV) console.error('[ApiPage] handleStatusChange failed:', error)
  }
}

/** 页面挂载时加载接口列表数据 */
onMounted(() => {
  fetchData()
})
</script>

<style lang="less" scoped>
.page-container {
  /* 搜索区域卡片样式：白色背景、圆角、底部间距 */
  .search-card {
    background: var(--ant-color-bg-container, #fff);
    border-radius: var(--ant-border-radius, 8px);
    padding: 16px;
    margin-bottom: 16px;

    /* 搜索表单行内布局时去除表单项底部间距 */
    :deep(.ant-form-item) {
      margin-bottom: 0;
    }
  }

  /* 表格区域容器：白色背景、圆角 */
  .s-table-wrapper {
    background: var(--ant-color-bg-container, #fff);
    border-radius: var(--ant-border-radius, 8px);
    padding: 16px;
  }

  /* 表格顶部工具栏区域底部间距 */
  .s-table-header {
    margin-bottom: 16px;
  }

  /* 工具栏容器：撑满宽度 */
  .table-header-container {
    width: 100%;
    padding: 0;
  }

  /* 工具栏布局：flex 横向排列，新增按钮与表格设置分居两侧 */
  .table-header-toolbar {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;

    /* 工具栏内按钮之间的右间距 */
    div > * {
      margin-right: 8px;
    }
  }

  /* 桌面端表格设置组件靠右对齐 */
  .table-header__toolbar-desktop {
    margin-left: auto;
  }

  /* 接口路径代码样式：通过全局 CSS 变量实现主题自适应 */
  .api-path {
    background-color: var(--api-path-bg);
    color: var(--api-path-text);
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 13px;
  }

  /* 全屏表格模式：覆盖页面容器为 fixed 定位，撑满视口 */
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

    /* 全屏模式下搜索区域不收缩 */
    .search-card {
      flex-shrink: 0;
    }

    /* 全屏模式下表格区域自适应剩余空间 */
    .s-table-wrapper {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 0;
    }

    /* 全屏模式下工具栏不收缩 */
    .s-table-header {
      flex-shrink: 0;
    }

    /* 全屏模式下表格内容区域可滚动 */
    :deep(.ant-table-wrapper) {
      flex: 1;
      overflow: auto;
    }
  }
}

/* 移动端适配：小屏幕下表格横向滚动 */
@media (max-width: 480px) {
  .page-container {
    :deep(.ant-table) {
      width: 100%;
      overflow-x: auto;
    }
  }
}
</style>
