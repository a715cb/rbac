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
        @expand="handleExpand"
      >
        <template #customFilterDropdown="{ setSelectedKeys, selectedKeys, confirm, clearFilters }">
          <div style="padding: 8px">
            <a-input
              ref="searchInput"
              placeholder="搜索菜单名称"
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

    <MenuFormModal
      v-model:visible="modalVisible"
      :record="currentRecord"
      :parent-id="parentId"
      @success="fetchData"
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
import { message, Tag, Popconfirm } from 'ant-design-vue'
import { getMenuList, deleteMenu } from '@/api/menu'
import type { MenuInfo } from '@/api/menu'
import MenuFormModal from '@/pages/system/menu/components/MenuFormModal.vue'
import SIcon from '@/components/Icon'
import TableSetting from '@/components/TableSetting/TableSetting.vue'
import { usePageTable } from '@/composables/usePageTable'
import type { ColumnItem } from '@/components/TableSetting/types'
import { useTreeSearch } from '@/composables/useTreeSearch'

const loading = ref(false)
const tableData = ref<MenuInfo[]>([])
const modalVisible = ref(false)
const currentRecord = ref<MenuInfo | null>(null)
const parentId = ref<number | undefined>(undefined)
const wrapRef = ref<HTMLElement | null>(null)
const searchInput = ref()

const { searchText, expandedRowKeys, highlightText, doSearch, resetSearch } = useTreeSearch('name')

const columnItems: ColumnItem[] = [
  {
    key: 'name',
    title: '菜单名称',
    dataIndex: 'name'
  },
  { key: 'icon', title: '图标', dataIndex: 'icon', width: 80, align: 'center' },
  { key: 'path', title: '路由地址', dataIndex: 'path' },
  { key: 'component', title: '路由组件', dataIndex: 'component' },
  { key: 'menu_type', title: '菜单类型', dataIndex: 'menu_type', width: 100, align: 'center' },
  { key: 'code', title: '菜单标识', dataIndex: 'code' },
  { key: 'status', title: '菜单状态', dataIndex: 'status', width: 100, align: 'center' },
  { key: 'sort', title: '排序', dataIndex: 'sort', width: 80, align: 'center' },
  { key: 'action', title: '操作', dataIndex: 'action', width: 200 }
]

const renderHighlightText = (text: string): string => {
  return searchText.value ? highlightText(text, searchText.value) : text
}

const menuTypeColor = (type: number): string => {
  const colors: Record<number, string> = { 1: 'blue', 2: 'green', 3: 'orange' }
  return colors[type] || 'default'
}

const menuTypeText = (type: number): string => {
  const texts: Record<number, string> = { 1: '目录', 2: '菜单', 3: '按钮' }
  return texts[type] || '未知'
}

// 收集可展开的节点 ID（只有存在子节点的才需要展开）
const collectExpandableIds = (data: MenuInfo[]): (string | number)[] => {
  const ids: (string | number)[] = []
  const walk = (list: MenuInfo[]) => {
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

// 处理展开/折叠事件
const handleExpand = (expanded: boolean, record: MenuInfo) => {
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
    const res = await getMenuList()
    tableData.value = res.data.list.map((m: MenuInfo) => ({ ...m, id: Number(m.id) }))
    // 数据加载后初始化展开状态
    expandedRowKeys.value = []
  } catch (error) {
    if (import.meta.env.DEV) console.error('[Menu] Fetch data failed:', error)
    // error handled by request interceptor
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
    } else if (col.key === 'icon') {
      base.customRender = ({ record }: { record: MenuInfo }) => {
        if (record.icon) {
          return h(SIcon, { type: record.icon, size: 16 })
        }
        return h('span', '-')
      }
    } else if (col.key === 'menu_type') {
      base.customRender = ({ record }: { record: MenuInfo }) => {
        return h(Tag, { color: menuTypeColor(record.menu_type) }, () =>
          menuTypeText(record.menu_type)
        )
      }
    } else if (col.key === 'status') {
      base.customRender = ({ record }: { record: MenuInfo }) => {
        if (record.status === 0) {
          return h('span', { class: 'text-red-500' }, '禁用')
        }
        return h('span', {}, '正常')
      }
    } else if (col.key === 'action') {
      base.customRender = ({ record }: { record: MenuInfo }) => {
        return h('div', {}, [
          h('a', { onClick: () => handleAddChild(record) }, '添加'),
          h('span', { class: 'ant-divider ant-divider-vertical' }),
          h('a', { onClick: () => handleEdit(record) }, '修改'),
          h('span', { class: 'ant-divider ant-divider-vertical' }),
          h(
            Popconfirm,
            {
              title: '确认要删除吗?',
              onConfirm: () => handleDelete(record)
            },
            {
              default: () => h('a', { class: 'text-red-500' }, '删除')
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

const handleAddChild = (record: MenuInfo) => {
  currentRecord.value = null
  parentId.value = record.id
  modalVisible.value = true
}

const handleEdit = (record: MenuInfo) => {
  currentRecord.value = record
  parentId.value = undefined
  modalVisible.value = true
}

const handleDelete = async (record: MenuInfo) => {
  try {
    await deleteMenu(record.id)
    message.success('删除成功')
    fetchData()
  } catch (error) {
    if (import.meta.env.DEV) console.error('[Menu] Delete menu failed:', error)
    // error handled by request interceptor
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style lang="less" scoped>
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

.text-red-500 {
  color: #ff4d4f;
}

[data-theme='dark'] {
  .page-container {
    .s-table-header {
      background: var(--ant-color-bg-container, #141414);
    }

    :deep(.ant-table) {
      .ant-table-thead > tr > th {
        background: #1f1f1f;
        color: rgba(255, 255, 255, 0.85);
        border-bottom-color: #303030;
      }

      .ant-table-tbody > tr > td {
        border-bottom-color: #303030;
      }

      .ant-table-tbody > tr:hover > td {
        background: #111b26;
      }

      .tree-expand-icon {
        color: rgba(255, 255, 255, 0.45);

        &:hover {
          color: #1890ff;
        }
      }
    }
  }

  .text-red-500 {
    color: #ff7875;
  }
}
</style>
