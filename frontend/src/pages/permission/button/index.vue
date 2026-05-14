<!--
  @文件: index.vue
  @用途: 按钮管理页面，提供按钮的列表查询、批量操作、状态切换和详情查看
  @描述: 系统管理-按钮管理核心页面，页面结构为搜索栏→操作工具栏→数据表格→弹窗（新增/编辑、详情）。
         支持按关键词、状态、所属菜单筛选按钮列表，支持批量启用/禁用/删除操作，
         集成字典组件用于状态筛选，菜单树选择器用于所属菜单筛选。
  @核心逻辑:
    1. 页面挂载 → fetchData() 加载按钮列表，useMenuTree 加载菜单树供筛选
    2. 搜索/筛选变更 → 重置页码 → fetchData() 刷新数据
    3. 新增/编辑/删除/状态变更/批量操作 → 操作成功后 fetchData() 刷新列表
    4. 批量操作需先勾选表格行，操作后自动清空选中项
-->
<template>
  <div ref="wrapRef" class="page-container">
    <!-- 搜索区域：支持按关键词、状态和所属菜单筛选按钮 -->
    <div class="search-card">
      <a-form layout="inline" :model="searchForm">
        <a-form-item label="关键词" html-for="btn-search-keyword">
          <a-input
            id="btn-search-keyword"
            v-model:value="searchForm.keyword"
            name="keyword"
            placeholder="按钮名称/编码"
            allow-clear
            @press-enter="handleSearch"
          />
        </a-form-item>
        <a-form-item label="状态" html-for="btn-search-status">
          <DictSelect
            id="btn-search-status"
            v-model:value="searchForm.status"
            name="status"
            dict-code="role_status"
            placeholder="全部状态"
            width="120px"
          />
        </a-form-item>
        <a-form-item label="所属菜单" html-for="btn-search-menu">
          <a-tree-select
            id="btn-search-menu"
            v-model:value="searchForm.menu_id"
            name="menu_id"
            :tree-data="menuTreeData"
            :field-names="{ label: 'name', value: 'id', children: 'children' }"
            placeholder="全部菜单"
            allow-clear
            tree-default-expand-all
            style="width: 200px"
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

    <!-- 表格区域：包含操作工具栏和数据列表 -->
    <div class="s-table-wrapper">
      <div class="s-table-header">
        <div class="table-header-container">
          <div class="flex items-center">
            <div class="table-header-toolbar">
              <div>
                <a-button v-auth="'permission_button:add'" type="primary" @click="handleAdd">
                  <PlusOutlined />
                  新增
                </a-button>
                <span v-auth="'permission_button:batch_enable'">
                  <a-popconfirm title="确定要启用选中的按钮吗？" @confirm="handleBatchStatus(1)">
                    <a-button :disabled="selectedRowKeys.length === 0">
                      <CheckCircleOutlined />
                      批量启用
                    </a-button>
                  </a-popconfirm>
                </span>
                <span v-auth="'permission_button:batch_disable'">
                  <a-popconfirm title="确定要禁用选中的按钮吗？" @confirm="handleBatchStatus(0)">
                    <a-button :disabled="selectedRowKeys.length === 0">
                      <StopOutlined />
                      批量禁用
                    </a-button>
                  </a-popconfirm>
                </span>
                <span v-auth="'permission_button:batch_delete'">
                  <a-popconfirm title="确定要删除选中的按钮吗？" @confirm="handleBatchDelete">
                    <a-button danger :disabled="selectedRowKeys.length === 0">
                      <DeleteOutlined />
                      批量删除
                    </a-button>
                  </a-popconfirm>
                </span>
              </div>
              <TableSetting class="table-header__toolbar-desktop" />
            </div>
          </div>
        </div>
      </div>

      <!-- 按钮数据表格 -->
      <a-table
        :columns="visibleColumns"
        :data-source="tableData"
        :loading="loading"
        :pagination="pagination"
        :size="tableSettingState.size"
        :row-selection="{ selectedRowKeys, onChange: onSelectChange }"
        :scroll="{ x: 1100 }"
        row-key="id"
        @change="handleTableChange"
      />
    </div>

    <!-- 新增/编辑按钮弹窗 -->
    <ButtonFormModal
      v-model:visible="modalVisible"
      :record="currentRecord"
      :menu-tree-data="menuTreeData"
      @success="handleSuccess"
    />

    <!-- 按钮详情弹窗 -->
    <ButtonDetailModal v-model:visible="detailVisible" :record="currentRecord" />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, h } from 'vue'
import {
  SearchOutlined,
  ReloadOutlined,
  PlusOutlined,
  EditOutlined,
  DeleteOutlined,
  EyeOutlined,
  CheckCircleOutlined,
  StopOutlined
} from '@ant-design/icons-vue'
import { message, Switch, Space, Button, Popconfirm } from 'ant-design-vue'
import {
  getButtonList,
  changeButtonStatus,
  batchButtonStatus,
  batchDeleteButtons
} from '@/api/button'
import type { ButtonInfo, ButtonQuery } from '@/api/button'
import { createPagination, type TablePaginationConfig } from '@/utils/common'
import ButtonFormModal from './components/ButtonFormModal.vue'
import ButtonDetailModal from './components/ButtonDetailModal.vue'
import TableSetting from '@/components/TableSetting/TableSetting.vue'
import { usePageTable } from '@/composables/usePageTable'
import type { ColumnItem } from '@/components/TableSetting/types'
import { DictSelect } from '@/components/Dict'
import { useMenuTree } from '@/composables/useTreeData'
import { useUserStore } from '@/stores/user'
import SIcon from '@/components/Icon'

const userStore = useUserStore()

const { menuTreeData, fetchMenuTree } = useMenuTree()

const loading = ref(false)
const tableData = ref<ButtonInfo[]>([])
const modalVisible = ref(false)
const detailVisible = ref(false)
const currentRecord = ref<ButtonInfo | null>(null)
const wrapRef = ref<HTMLElement | null>(null)
const selectedRowKeys = ref<number[]>([])

const searchForm = reactive<ButtonQuery>({
  page: 1,
  limit: 10,
  keyword: '',
  status: undefined,
  menu_id: undefined
})

const pagination = reactive(createPagination())

const columnItems: ColumnItem[] = [
  { key: 'name', title: '按钮名称', dataIndex: 'name', width: 140 },
  { key: 'code', title: '按钮编码', dataIndex: 'code', width: 180 },
  { key: 'menu_name', title: '所属菜单', dataIndex: 'menu_name', width: 150 },
  { key: 'icon', title: '图标', dataIndex: 'icon', width: 80, align: 'center' },
  { key: 'sort', title: '排序', dataIndex: 'sort', width: 80, align: 'center' },
  { key: 'status', title: '状态', dataIndex: 'status', width: 100, align: 'center' },
  { key: 'create_time', title: '创建时间', dataIndex: 'create_time', width: 170 },
  { key: 'action', title: '操作', dataIndex: 'action', width: 220, fixed: 'right' }
]

const fetchData = async () => {
  loading.value = true
  try {
    const res = await getButtonList({
      page: pagination.current,
      limit: pagination.pageSize,
      keyword: searchForm.keyword,
      status: searchForm.status,
      menu_id: searchForm.menu_id
    })
    tableData.value = res.data.list.map((r: ButtonInfo) => ({ ...r, id: Number(r.id) }))
    pagination.total = res.data.pagination.total
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[ButtonPage] fetchData failed:', error)
  } finally {
    loading.value = false
  }
}

const { tableSettingState, visibleColumns: baseColumns } = usePageTable({
  columns: columnItems,
  fetchData,
  wrapRef
})

const visibleColumns = computed(() =>
  baseColumns.value.map((col) => {
    const base = { ...col } as Record<string, unknown>
    if (col.key === 'icon') {
      base.customRender = ({ record }: { record: ButtonInfo }) => {
        if (record.icon) {
          return h(SIcon, { type: record.icon, size: 16 })
        }
        return h('span', '-')
      }
    } else if (col.key === 'status') {
      base.customRender = ({ record }: { record: ButtonInfo }) => {
        return h(Switch, {
          checked: record.status === 1,
          disabled: !userStore.hasPermission('permission_button:status'),
          onChange: (checked: boolean | string | number) =>
            handleStatusChange(record, Boolean(checked))
        })
      }
    } else if (col.key === 'action') {
      base.customRender = ({ record }: { record: ButtonInfo }) => {
        const buttons: ReturnType<typeof h>[] = []
        if (userStore.hasPermission('permission_button:edit')) {
          buttons.push(
            h(Button, { type: 'link', size: 'small', onClick: () => handleEdit(record) }, () => [
              h(EditOutlined),
              ' 编辑'
            ])
          )
        }
        if (userStore.hasPermission('permission_button:detail')) {
          buttons.push(
            h(Button, { type: 'link', size: 'small', onClick: () => handleDetail(record) }, () => [
              h(EyeOutlined),
              ' 详情'
            ])
          )
        }
        if (userStore.hasPermission('permission_button:delete')) {
          buttons.push(
            h(
              Popconfirm,
              { title: '确定要删除该按钮吗？', onConfirm: () => handleDelete(record) },
              () =>
                h(Button, { type: 'link', danger: true, size: 'small' }, () => [
                  h(DeleteOutlined),
                  ' 删除'
                ])
            )
          )
        }
        return h(Space, {}, () => buttons)
      }
    }
    return base
  })
)

const handleSearch = () => {
  pagination.current = 1
  fetchData()
}

const handleReset = () => {
  searchForm.keyword = ''
  searchForm.status = undefined
  searchForm.menu_id = undefined
  pagination.current = 1
  fetchData()
}

const handleTableChange = (pag: TablePaginationConfig) => {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  fetchData()
}

const onSelectChange = (keys: number[]) => {
  selectedRowKeys.value = keys
}

const handleAdd = () => {
  currentRecord.value = null
  modalVisible.value = true
}

const handleEdit = (record: ButtonInfo) => {
  currentRecord.value = record
  modalVisible.value = true
}

/**
 * 表单提交成功回调（局部数据更新）
 * @param record - 提交后的按钮数据
 * @description 编辑模式：更新匹配行；新增模式：插入到开头
 */
const handleSuccess = (record: ButtonInfo) => {
  const normalized = { ...record, id: Number(record.id) }
  const index = tableData.value.findIndex((item) => item.id === normalized.id)
  if (index !== -1) {
    tableData.value[index] = { ...tableData.value[index], ...normalized }
  } else {
    tableData.value.unshift(normalized)
    pagination.total += 1
  }
}

const handleDetail = (record: ButtonInfo) => {
  currentRecord.value = record
  detailVisible.value = true
}

const handleDelete = async (record: ButtonInfo) => {
  try {
    await batchDeleteButtons([record.id])
    message.success('删除成功')
    tableData.value = tableData.value.filter((item) => item.id !== record.id)
    pagination.total = Math.max(0, pagination.total - 1)
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[ButtonPage] handleDelete failed:', error)
  }
}

/**
 * 切换按钮状态（乐观更新）
 * @param record - 按钮记录
 * @param checked - 开关状态，true 为启用，false 为禁用
 * @description 先更新本地状态，调用 API 失败后回滚并提示错误
 */
const handleStatusChange = async (record: ButtonInfo, checked: boolean) => {
  const oldStatus = record.status
  record.status = checked ? 1 : 0
  try {
    await changeButtonStatus(record.id, checked ? 1 : 0)
    message.success(checked ? '按钮已启用' : '按钮已禁用')
  } catch (error: unknown) {
    record.status = oldStatus
    message.error('状态变更失败，请重试')
    if (import.meta.env.DEV) console.error('[ButtonPage] handleStatusChange failed:', error)
  }
}

const handleBatchStatus = async (status: number) => {
  if (selectedRowKeys.value.length === 0) return
  try {
    await batchButtonStatus(selectedRowKeys.value, status)
    message.success(status === 1 ? '批量启用成功' : '批量禁用成功')
    selectedRowKeys.value = []
    fetchData()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[ButtonPage] handleBatchStatus failed:', error)
  }
}

const handleBatchDelete = async () => {
  if (selectedRowKeys.value.length === 0) return
  try {
    await batchDeleteButtons(selectedRowKeys.value)
    message.success('批量删除成功')
    selectedRowKeys.value = []
    fetchData()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[ButtonPage] handleBatchDelete failed:', error)
  }
}

onMounted(() => {
  fetchMenuTree()
  fetchData()
})
</script>

<style lang="less" scoped></style>
