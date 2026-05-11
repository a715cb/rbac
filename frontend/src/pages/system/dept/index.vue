<template>
  <div ref="wrapRef" class="page-container">
    <div class="s-table-wrapper">
      <div class="s-table-header">
        <div class="table-header-container">
          <div class="flex items-center">
            <div class="table-header-toolbar">
              <div>
                <a-button type="primary" @click="handleAdd">
                  <PlusOutlined />
                  新增
                </a-button>
                <a-button @click="expandAll">
                  <ExpandOutlined />
                  展开
                </a-button>
                <a-button @click="collapseAll">
                  <ShrinkOutlined />
                  折叠
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
        row-key="id"
        :pagination="false"
        :size="tableSettingState.size"
        :expanded-row-keys="expandedRowKeys"
        :indent-size="16"
        :scroll="{ x: 1300 }"
        @expand="handleExpand"
      >
        <template
          #customFilterDropdown="{ setSelectedKeys, selectedKeys, confirm, clearFilters, column }"
        >
          <div style="padding: 8px">
            <a-input
              ref="searchInput"
              :placeholder="`搜索 ${column.title}`"
              :value="selectedKeys[0]"
              style="width: 188px; margin-bottom: 8px; display: block"
              @change="(e: any) => setSelectedKeys(e.target.value ? [e.target.value] : [])"
              @press-enter="handleFilterSearch(selectedKeys, confirm)"
            />
            <a-button
              type="primary"
              size="small"
              style="width: 90px; margin-right: 8px"
              @click="handleFilterSearch(selectedKeys, confirm)"
            >
              <template #icon><SearchOutlined /></template>
              搜索
            </a-button>
            <a-button
              size="small"
              style="width: 90px"
              @click="handleFilterReset(clearFilters, confirm)"
            >
              重置
            </a-button>
          </div>
        </template>

        <template #customFilterIcon="{ filtered }">
          <SearchOutlined :style="{ color: filtered ? '#1890ff' : undefined }" />
        </template>

        <template #expandIcon="{ expandable, expanded, record, onExpand }">
          <a
            v-if="expandable"
            class="tree-expand-icon"
            :class="{ expanded }"
            @click="(e: Event) => onExpand(record, e)"
          >
            <CaretRightOutlined />
          </a>
          <span v-else class="tree-expand-icon-placeholder"></span>
        </template>
      </a-table>
    </div>

    <DeptFormModal
      v-model:visible="modalVisible"
      :record="currentRecord"
      :parent-id="parentId"
      :tree-data="treeData"
      @success="fetchData"
    />

    <DeptUsersModal
      v-model:visible="usersModalVisible"
      :dept-id="currentDeptId"
      :dept-name="currentDeptName"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, h } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import {
  SearchOutlined,
  PlusOutlined,
  ExpandOutlined,
  ShrinkOutlined,
  CaretRightOutlined
} from '@ant-design/icons-vue'
import { message } from 'ant-design-vue'
import { getDeptList, deleteDept, changeDeptStatus } from '@/api/dept'
import type { DeptInfo } from '@/api/dept'
import DeptFormModal from './components/DeptFormModal.vue'
import DeptUsersModal from './components/DeptUsersModal.vue'
import TableSetting from '@/components/TableSetting/TableSetting.vue'
import { usePageTable } from '@/composables/usePageTable'
import type { ColumnItem } from '@/components/TableSetting/types'
import { useTreeSearch } from '@/composables/useTreeSearch'

const loading = ref(false)
const tableData = ref<DeptInfo[]>([])
const modalVisible = ref(false)
const currentRecord = ref<DeptInfo | null>(null)
const parentId = ref<number | undefined>(undefined)
const wrapRef = ref<HTMLElement | null>(null)
const searchInput = ref()
const treeData = ref<DeptInfo[]>([])
const usersModalVisible = ref(false)
const currentDeptId = ref<number | undefined>(undefined)
const currentDeptName = ref('')

const { searchText, expandedRowKeys, highlightText, doSearch, resetSearch } = useTreeSearch('name')

const columnItems: ColumnItem[] = [
  {
    key: 'name',
    title: '名称',
    dataIndex: 'name'
  },
  { key: 'code', title: '部门编码', dataIndex: 'code', width: 120 },
  { key: 'leader', title: '负责人', dataIndex: 'leader', width: 100 },
  { key: 'phone', title: '联系电话', dataIndex: 'phone', width: 140 },
  { key: 'email', title: '邮箱', dataIndex: 'email', width: 180 },
  { key: 'sort', title: '排序', dataIndex: 'sort', width: 80, align: 'center' },
  { key: 'status', title: '状态', dataIndex: 'status', width: 100, align: 'center' },
  { key: 'create_time', title: '创建时间', dataIndex: 'create_time', width: 180 },
  { key: 'action', title: '操作', dataIndex: 'action', width: 280, fixed: 'right' }
]

const renderHighlightText = (text: string): string => {
  return searchText.value ? highlightText(text, searchText.value) : text
}

const collectExpandableIds = (data: DeptInfo[]): (string | number)[] => {
  const ids: (string | number)[] = []
  const walk = (list: DeptInfo[]) => {
    for (const item of list) {
      if (item.children?.length) {
        ids.push(item.id)
        walk(item.children)
      }
    }
  }
  walk(data)
  return ids
}

const expandAll = () => {
  expandedRowKeys.value = collectExpandableIds(tableData.value)
}

const collapseAll = () => {
  expandedRowKeys.value = []
}

const handleExpand = (expanded: boolean, record: DeptInfo) => {
  if (expanded) {
    expandedRowKeys.value = [...expandedRowKeys.value, record.id]
  } else {
    expandedRowKeys.value = expandedRowKeys.value.filter((id) => id !== record.id)
  }
}

const debouncedDoSearch = useDebounceFn((keyword: string) => {
  doSearch(keyword, tableData)
}, 300)

const handleFilterSearch = (selectedKeys: string[], confirm: () => void) => {
  confirm()
  debouncedDoSearch(selectedKeys[0] || '')
}

const handleFilterReset = (clearFilters: (() => void) | undefined, confirm: () => void) => {
  clearFilters?.()
  resetSearch()
  confirm()
}

const fetchData = async () => {
  loading.value = true
  try {
    const res = await getDeptList()
    tableData.value = res.data.list.map((d: DeptInfo) => ({ ...d, id: Number(d.id) }))
    treeData.value = tableData.value
    expandedRowKeys.value = collectExpandableIds(res.data.list)
  } catch (error) {
    if (import.meta.env.DEV) console.error('[DeptPage] fetchData failed:', error)
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
    const base: Record<string, any> = { ...col }
    if (col.key === 'name') {
      base.customFilterDropdown = true
      base.onFilter = () => true
      base.onFilterDropdownOpenChange = (visible: boolean) => {
        if (visible) {
          setTimeout(() => (searchInput.value as any)?.focus?.(), 100)
        }
      }
      base.customRender = ({ text }: { text: string }) => {
        return h('span', { innerHTML: renderHighlightText(text) })
      }
    }
    if (col.key === 'status') {
      base.customRender = ({ record }: { record: DeptInfo }) => {
        return h('a-switch', {
          checked: record.status === 1,
          'onUpdate:checked': (checked: boolean) => handleStatusChange(record, checked)
        })
      }
    }
    if (col.key === 'action') {
      base.customRender = ({ record }: { record: DeptInfo }) => {
        return h('div', {}, [
          h('a', { onClick: () => handleAddChild(record) }, '添加'),
          h('a-divider', { type: 'vertical' }),
          h('a', { onClick: () => handleEdit(record) }, '修改'),
          h('a-divider', { type: 'vertical' }),
          h('a', { onClick: () => handleViewMembers(record) }, '查看成员'),
          h('a-divider', { type: 'vertical' }),
          h(
            'a-popconfirm',
            {
              title: '确认要删除吗?',
              okText: '确定',
              cancelText: '取消',
              onConfirm: () => handleDelete(record)
            },
            {
              default: () => h('a', { class: 'danger-link' }, '删除')
            }
          )
        ])
      }
    }
    return base
  })
)

const handleAdd = () => {
  currentRecord.value = null
  parentId.value = undefined
  modalVisible.value = true
}

const handleAddChild = (record: DeptInfo) => {
  currentRecord.value = null
  parentId.value = record.id
  modalVisible.value = true
}

const handleEdit = (record: DeptInfo) => {
  currentRecord.value = record
  parentId.value = undefined
  modalVisible.value = true
}

const handleDelete = async (record: DeptInfo) => {
  try {
    await deleteDept(record.id)
    message.success('删除成功')
    fetchData()
  } catch (error) {
    if (import.meta.env.DEV) console.error('[Dept] Delete dept failed:', error)
    // error handled by request interceptor
  }
}

const handleStatusChange = async (record: DeptInfo, checked: boolean) => {
  try {
    await changeDeptStatus(record.id, checked ? 1 : 0)
    message.success(checked ? '部门已启用' : '部门已禁用')
    fetchData()
  } catch (error) {
    if (import.meta.env.DEV) console.error('[Dept] Change dept status failed:', error)
    // error handled by request interceptor
  }
}

const handleViewMembers = (record: DeptInfo) => {
  currentDeptId.value = record.id
  currentDeptName.value = record.name
  usersModalVisible.value = true
}

onMounted(() => {
  fetchData()
})
</script>

<style lang="less" scoped>
.danger-link {
  color: #ff4d4f;
}

.page-container {
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

  :deep(.ant-table) {
    .ant-table-thead > tr > th {
      font-weight: 500;
    }

    .tree-expand-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 20px;
      height: 20px;
      margin-right: 4px;
      color: rgba(0, 0, 0, 0.45);
      cursor: pointer;
      transition: color 0.2s;

      .anticon {
        font-size: 11px;
        transition: transform 0.2s ease-in-out;
      }

      &.expanded .anticon {
        transform: rotate(90deg);
      }

      &:hover {
        color: #1890ff;
      }
    }

    .tree-expand-icon-placeholder {
      display: inline-block;
      width: 20px;
      height: 20px;
      margin-right: 4px;
    }

    .ant-table-row {
      .ant-table-cell {
        padding-left: 8px;
        padding-right: 8px;
      }
    }
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
