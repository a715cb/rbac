<!--
  @文件: index.vue
  @用途: 系统菜单管理页面，以树形表格展示菜单层级结构
  @描述: 以树形表格展示菜单层级结构，支持增删改查、展开折叠、搜索高亮，
         包含菜单类型标签渲染、状态展示、图标渲染等自定义列功能，
         操作成功后自动刷新列表和菜单缓存
  @核心逻辑:
    1. 树形表格展示菜单层级数据，支持展开/折叠全部节点
    2. 基于名称字段的搜索筛选，支持防抖搜索和文本高亮
    3. 新增/编辑菜单通过 MenuFormModal 弹窗实现，删除后刷新列表和菜单缓存
    4. 使用 usePageTable 组合式函数管理表格设置和列可见性
-->
<template>
  <div ref="wrapRef" class="page-container">
    <div class="s-table-wrapper">
      <!-- 表格顶部工具栏：新增、展开、折叠按钮及表格设置 -->
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
              <!-- 表格列设置组件（桌面端显示） -->
              <TableSetting class="table-header__toolbar-desktop" />
            </div>
          </div>
        </div>
      </div>

      <!-- 树形表格：展示菜单层级数据，禁用分页 -->
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
        <!-- 自定义筛选下拉框：用于菜单名称搜索 -->
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

        <!-- 筛选图标：激活时高亮显示 -->
        <template #customFilterIcon="{ filtered }">
          <SearchOutlined :style="{ color: filtered ? '#1890ff' : undefined }" />
        </template>

        <!-- 自定义展开图标：使用右箭头旋转表示展开/折叠状态 -->
        <template #expandIcon="{ expandable, expanded, record, onExpand }">
          <a
            v-if="expandable"
            class="tree-expand-icon"
            :class="{ expanded }"
            @click="(e: Event) => onExpand(record, e)"
          >
            <CaretRightOutlined />
          </a>
          <!-- 叶子节点占位符，保持对齐 -->
          <span v-else class="tree-expand-icon-placeholder"></span>
        </template>
      </a-table>
    </div>

    <!-- 菜单表单弹窗：新增/编辑菜单 -->
    <MenuFormModal
      v-model:visible="modalVisible"
      :record="currentRecord"
      :parent-id="parentId"
      @success="handleModalSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, h } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import {
  SearchOutlined,
  PlusOutlined,
  EditOutlined,
  DeleteOutlined,
  ExpandOutlined,
  ShrinkOutlined,
  CaretRightOutlined
} from '@ant-design/icons-vue'
import { message, Tag, Popconfirm, Space, Button } from 'ant-design-vue'
import { getMenuList, deleteMenu } from '@/api/menu'
import type { MenuInfo } from '@/api/menu'
import MenuFormModal from './components/MenuFormModal.vue'
import SIcon from '@/components/Icon'
import TableSetting from '@/components/TableSetting/TableSetting.vue'
import { usePageTable } from '@/composables/usePageTable'
import type { ColumnItem } from '@/components/TableSetting/types'
import { useTreeSearch } from '@/composables/useTreeSearch'
import { StorageManager } from '@/utils/storage'
import { AppConfig } from '@/config/app'
import { useUserStore } from '@/stores/user'

const userStore = useUserStore()

/** 清除前端菜单缓存，使下次路由导航时重新从后端获取菜单数据 */
const refreshMenuCache = () => {
  StorageManager.removeItem('session', AppConfig.menusKey)
  userStore.dynamicRoutesAdded = false
}

const loading = ref(false) // 表格加载状态
const tableData = ref<MenuInfo[]>([]) // 菜单树形数据
const modalVisible = ref(false) // 表单弹窗可见性
const currentRecord = ref<MenuInfo | null>(null) // 当前编辑的菜单记录，null 表示新增模式
const parentId = ref<number | undefined>(undefined) // 新增子菜单时的父级 ID
const wrapRef = ref<HTMLElement | null>(null) // 页面容器引用，用于全屏等功能
const searchInput = ref() // 搜索输入框引用，用于自动聚焦

// 树形搜索组合式函数：基于 name 字段进行搜索、高亮、展开匹配节点
const { searchText, expandedRowKeys, highlightText, doSearch, resetSearch } = useTreeSearch('name')

const columnItems: ColumnItem[] = [
  { key: 'name', title: '菜单名称', dataIndex: 'name' },
  { key: 'icon', title: '图标', dataIndex: 'icon', width: 80, align: 'center' },
  { key: 'path', title: '路由地址', dataIndex: 'path' },
  { key: 'component', title: '路由组件', dataIndex: 'component' },
  { key: 'menu_type', title: '菜单类型', dataIndex: 'menu_type', width: 100, align: 'center' },
  { key: 'code', title: '菜单标识', dataIndex: 'code' },
  { key: 'status', title: '菜单状态', dataIndex: 'status', width: 100, align: 'center' },
  { key: 'sort', title: '排序', dataIndex: 'sort', width: 80, align: 'center' },
  { key: 'action', title: '操作', dataIndex: 'action', width: 280, fixed: 'right' }
]

/** 渲染搜索高亮文本，无搜索关键词时原样返回 */
const renderHighlightText = (text: string): string => {
  return searchText.value ? highlightText(text, searchText.value) : text
}

/** 根据菜单类型返回对应的标签颜色：1-目录(蓝)、2-菜单(绿)、3-按钮(橙) */
const menuTypeColor = (type: number): string => {
  const colors: Record<number, string> = { 1: 'blue', 2: 'green', 3: 'orange' }
  return colors[type] || 'default'
}

/** 根据菜单类型返回对应的中文文本 */
const menuTypeText = (type: number): string => {
  const texts: Record<number, string> = { 1: '目录', 2: '菜单', 3: '按钮' }
  return texts[type] || '未知'
}

/** 递归收集所有可展开的节点 ID（只有存在子节点的才需要展开） */
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

/** 展开所有树形节点 */
const expandAll = () => {
  expandedRowKeys.value = collectExpandableIds(tableData.value)
}

/** 折叠所有树形节点 */
const collapseAll = () => {
  expandedRowKeys.value = []
}

/** 处理单个节点的展开/折叠事件，同步更新 expandedRowKeys */
const handleExpand = (expanded: boolean, record: MenuInfo) => {
  if (expanded) {
    expandedRowKeys.value = [...expandedRowKeys.value, record.id]
  } else {
    expandedRowKeys.value = expandedRowKeys.value.filter((id) => id !== record.id)
  }
}

/** 防抖搜索：300ms 延迟避免频繁触发 */
const debouncedDoSearch = useDebounceFn((keyword: string) => {
  doSearch(keyword, tableData)
}, 300)

/** 筛选下拉框搜索按钮回调：确认筛选并触发防抖搜索 */
const handleFilterSearch = (selectedKeys: string[], confirm: () => void) => {
  confirm()
  debouncedDoSearch(selectedKeys[0] || '')
}

/** 筛选下拉框重置按钮回调：清除筛选条件并重置搜索状态 */
const handleFilterReset = (clearFilters: (() => void) | undefined, confirm: () => void) => {
  clearFilters?.()
  resetSearch()
  confirm()
}

/** 获取菜单列表数据，将 id 统一转为数字类型 */
const fetchData = async () => {
  loading.value = true
  try {
    const res = await getMenuList()
    tableData.value = res.data.list.map((m: MenuInfo) => ({ ...m, id: Number(m.id) }))
    expandedRowKeys.value = []
  } catch (error) {
    if (import.meta.env.DEV) console.error('[Menu] Fetch data failed:', error)
  } finally {
    loading.value = false
  }
}

// 使用 usePageTable 组合式函数，获取表格设置状态和基础可见列
const { tableSettingState, visibleColumns: baseColumns } = usePageTable({
  columns: columnItems,
  fetchData,
  wrapRef
})

/** 计算最终可见列配置，为各列添加自定义渲染逻辑 */
const visibleColumns = computed(() =>
  baseColumns.value.map((col) => {
    const base: Record<string, any> = { ...col }
    if (col.key === 'name') {
      // 菜单名称列：启用自定义筛选下拉框，搜索时高亮匹配文本
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
      // 图标列：使用 SIcon 组件渲染，无图标时显示 "-"
      base.customRender = ({ record }: { record: MenuInfo }) => {
        if (record.icon) {
          return h(SIcon, { type: record.icon, size: 16 })
        }
        return h('span', '-')
      }
    } else if (col.key === 'menu_type') {
      // 菜单类型列：使用彩色标签展示（目录/菜单/按钮）
      base.customRender = ({ record }: { record: MenuInfo }) => {
        return h(Tag, { color: menuTypeColor(record.menu_type) }, () =>
          menuTypeText(record.menu_type)
        )
      }
    } else if (col.key === 'status') {
      // 菜单状态列：0-禁用(红色)，1-正常
      base.customRender = ({ record }: { record: MenuInfo }) => {
        if (record.status === 0) {
          return h('span', { class: 'text-red-500' }, '禁用')
        }
        return h('span', {}, '正常')
      }
    } else if (col.key === 'action') {
      // 操作列：添加子菜单(PlusOutlined)、修改(EditOutlined)、删除(DeleteOutlined)
      base.customRender = ({ record }: { record: MenuInfo }) => {
        return h(Space, null, () => [
          h(Button, { type: 'link', size: 'small', onClick: () => handleAddChild(record) }, () => [
            h(PlusOutlined),
            ' 添加'
          ]),
          h(Button, { type: 'link', size: 'small', onClick: () => handleEdit(record) }, () => [
            h(EditOutlined),
            ' 编辑'
          ]),
          h(Popconfirm, { title: '确认要删除吗?', onConfirm: () => handleDelete(record) }, () =>
            h(Button, { type: 'link', danger: true, size: 'small' }, () => [
              h(DeleteOutlined),
              ' 删除'
            ])
          )
        ])
      }
    }
    return base
  })
)

/** 新增顶级菜单 */
const handleAdd = () => {
  currentRecord.value = null
  parentId.value = undefined
  modalVisible.value = true
}

/** 新增子菜单：设置父级 ID */
const handleAddChild = (record: MenuInfo) => {
  currentRecord.value = null
  parentId.value = record.id
  modalVisible.value = true
}

/** 编辑菜单：传入当前记录数据 */
const handleEdit = (record: MenuInfo) => {
  currentRecord.value = record
  parentId.value = undefined
  modalVisible.value = true
}

/** 删除菜单：调用删除接口后刷新列表和菜单缓存 */
const handleDelete = async (record: MenuInfo) => {
  try {
    await deleteMenu(record.id)
    message.success('删除成功')
    fetchData()
    refreshMenuCache()
  } catch (error) {
    if (import.meta.env.DEV) console.error('[Menu] Delete menu failed:', error)
  }
}

/** 表单弹窗操作成功：刷新列表和菜单缓存 */
const handleModalSuccess = () => {
  fetchData()
  refreshMenuCache()
}

onMounted(() => {
  fetchData()
})
</script>

<style lang="less" scoped>
.page-container {
  /* 表格容器：白色背景、圆角、内边距 */
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

  /* 工具栏：弹性布局，操作按钮左对齐，表格设置右对齐 */
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

  /* 表格深度样式覆盖 */
  :deep(.ant-table) {
    .ant-table-thead > tr > th {
      font-weight: 500;
    }

    /* 树形展开图标：右箭头样式，展开时旋转90度 */
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

    /* 叶子节点占位符：与展开图标同宽，保持缩进对齐 */
    .tree-expand-icon-placeholder {
      display: inline-block;
      width: 20px;
      height: 20px;
      margin-right: 4px;
    }

    /* 表格行单元格内边距调整 */
    .ant-table-row {
      .ant-table-cell {
        padding-left: 8px;
        padding-right: 8px;
      }
    }
  }

  /* 全屏模式样式 */
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

/* 移动端适配：小屏幕下表格横向滚动 */
@media (max-width: 480px) {
  .page-container {
    :deep(.ant-table) {
      width: 100%;
      overflow-x: auto;
    }
  }
}

/* 禁用状态红色文本 */
.text-red-500 {
  color: #ff4d4f;
}

/* 暗色主题样式覆盖 */
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

  /* 暗色主题下禁用状态红色文本（使用更亮的红色以保证可读性） */
  .text-red-500 {
    color: #ff7875;
  }
}
</style>
