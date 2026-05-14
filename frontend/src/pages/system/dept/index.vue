<!--
  @文件: dept/index.vue
  @用途: 部门管理主页面
  @描述: 系统管理模块下的部门管理页面，以树形表格展示部门层级结构。
         支持部门的增删改查、状态切换、展开/折叠、名称搜索高亮、
         查看部门成员等操作。依赖组件：DeptFormModal（新增/编辑弹窗）、
         DeptUsersModal（成员查看弹窗）、TableSetting（表格列设置）。
  @核心逻辑:
    - 树形表格展示部门层级数据，支持展开/折叠控制
    - 名称列支持自定义筛选下拉搜索，搜索关键词高亮显示
    - 状态列使用 Switch 开关，支持直接切换部门启用/禁用
    - 操作列提供添加子部门、修改、查看成员、删除功能
    - 使用 usePageTable 组合式函数管理表格列配置
    - 使用 useTreeSearch 组合式函数管理树形搜索和展开状态
-->
<template>
  <div ref="wrapRef" class="page-container">
    <div class="s-table-wrapper">
      <!-- 表格顶部工具栏：新增按钮、展开/折叠按钮、表格设置 -->
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

      <!-- 部门树形表格：不分页，支持横向滚动 -->
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
        <!-- 名称列自定义筛选下拉面板 -->
        <template
          #customFilterDropdown="{ setSelectedKeys, selectedKeys, confirm, clearFilters, column }"
        >
          <div style="padding: 8px">
            <a-input
              ref="searchInput"
              :placeholder="`搜索 ${column.title}`"
              :value="selectedKeys[0]"
              style="width: 188px; margin-bottom: 8px; display: block"
              @change="
                (e: Event) =>
                  setSelectedKeys(
                    (e.target as HTMLInputElement).value
                      ? [(e.target as HTMLInputElement).value]
                      : []
                  )
              "
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

        <!-- 筛选图标：激活时显示蓝色 -->
        <template #customFilterIcon="{ filtered }">
          <SearchOutlined :style="{ color: filtered ? '#1890ff' : undefined }" />
        </template>

        <!-- 树形展开图标：自定义箭头样式，支持旋转动画 -->
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

    <!-- 部门新增/编辑弹窗 -->
    <DeptFormModal
      v-model:visible="modalVisible"
      :record="currentRecord"
      :parent-id="parentId"
      :tree-data="treeData"
      @success="handleSuccess"
    />

    <!-- 部门成员查看弹窗 -->
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
  EditOutlined,
  DeleteOutlined,
  TeamOutlined,
  ExpandOutlined,
  ShrinkOutlined,
  CaretRightOutlined
} from '@ant-design/icons-vue'
import { message, Space, Button, Popconfirm, Switch } from 'ant-design-vue'
import { getDeptList, deleteDept, changeDeptStatus } from '@/api/dept'
import type { DeptInfo } from '@/api/dept'
import DeptFormModal from './components/DeptFormModal.vue'
import DeptUsersModal from './components/DeptUsersModal.vue'
import TableSetting from '@/components/TableSetting/TableSetting.vue'
import { usePageTable } from '@/composables/usePageTable'
import type { ColumnItem } from '@/components/TableSetting/types'
import { useTreeSearch } from '@/composables/useTreeSearch'

/** 页面加载状态 */
const loading = ref(false)

/** 部门表格数据：树形结构的部门列表 */
const tableData = ref<DeptInfo[]>([])

/** 部门表单弹窗可见状态 */
const modalVisible = ref(false)

/** 当前操作的部门记录：编辑时传入，新增时为 null */
const currentRecord = ref<DeptInfo | null>(null)

/** 新增子部门时的上级部门ID */
const parentId = ref<number | undefined>(undefined)

/** 页面容器引用，用于全屏等功能 */
const wrapRef = ref<HTMLElement | null>(null)

/** 搜索输入框引用，用于筛选下拉打开时自动聚焦 */
const searchInput = ref<HTMLInputElement | null>(null)

/** 部门树形数据：与 tableData 同步，供 DeptFormModal 的上级部门选择器使用 */
const treeData = ref<DeptInfo[]>([])

/** 部门成员弹窗可见状态 */
const usersModalVisible = ref(false)

/** 当前查看成员的部门ID */
const currentDeptId = ref<number | undefined>(undefined)

/** 当前查看成员的部门名称 */
const currentDeptName = ref('')

/** 树形搜索组合式函数：管理搜索文本、展开行和关键词高亮 */
const { searchText, expandedRowKeys, highlightText, doSearch, resetSearch } = useTreeSearch('name')

/** 表格列配置项：定义部门表格的所有列信息 */
const columnItems: ColumnItem[] = [
  { key: 'name', title: '名称', dataIndex: 'name', width: 200 },
  { key: 'code', title: '部门编码', dataIndex: 'code', width: 120 },
  { key: 'leader', title: '负责人', dataIndex: 'leader', width: 100 },
  { key: 'phone', title: '联系电话', dataIndex: 'phone', width: 140 },
  { key: 'email', title: '邮箱', dataIndex: 'email', width: 180 },
  { key: 'sort', title: '排序', dataIndex: 'sort', width: 80, align: 'center' },
  { key: 'status', title: '状态', dataIndex: 'status', width: 100, align: 'center' },
  { key: 'create_time', title: '创建时间', dataIndex: 'create_time', width: 180 },
  { key: 'action', title: '操作', dataIndex: 'action', width: 320, fixed: 'right' }
]

/**
 * 渲染搜索高亮文本
 * @param text - 原始文本
 * @returns 带有高亮 HTML 标记的文本，无搜索关键词时返回原文
 */
const renderHighlightText = (text: string): string => {
  return searchText.value ? highlightText(text, searchText.value) : text
}

/**
 * 递归收集所有可展开节点的ID
 * @param data - 树形部门数据
 * @returns 所有拥有子节点的部门ID数组
 * @description 遍历整棵部门树，收集所有包含 children 的节点ID，用于"展开全部"功能
 */
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

/**
 * 展开全部树形节点
 * @description 收集所有可展开节点ID并设置到 expandedRowKeys
 */
const expandAll = () => {
  expandedRowKeys.value = collectExpandableIds(tableData.value)
}

/**
 * 折叠全部树形节点
 * @description 清空 expandedRowKeys，使所有节点折叠
 */
const collapseAll = () => {
  expandedRowKeys.value = []
}

/**
 * 处理单个节点展开/折叠
 * @param expanded - 是否展开
 * @param record - 当前操作的部门记录
 * @description 手动维护 expandedRowKeys，实现受控展开状态
 */
const handleExpand = (expanded: boolean, record: DeptInfo) => {
  if (expanded) {
    expandedRowKeys.value = [...expandedRowKeys.value, record.id]
  } else {
    expandedRowKeys.value = expandedRowKeys.value.filter((id) => id !== record.id)
  }
}

/** 防抖搜索：300ms 延迟，避免频繁触发搜索 */
const debouncedDoSearch = useDebounceFn((keyword: string) => {
  doSearch(keyword, tableData)
}, 300)

/**
 * 筛选搜索处理
 * @param selectedKeys - 选中的筛选关键词数组
 * @param confirm - 确认筛选回调，关闭下拉面板
 * @description 确认筛选并执行防抖搜索
 */
const handleFilterSearch = (selectedKeys: string[], confirm: () => void) => {
  confirm()
  debouncedDoSearch(selectedKeys[0] || '')
}

/**
 * 筛选重置处理
 * @param clearFilters - 清除筛选条件回调
 * @param confirm - 确认回调，关闭下拉面板
 * @description 清除筛选条件并重置搜索状态
 */
const handleFilterReset = (clearFilters: (() => void) | undefined, confirm: () => void) => {
  clearFilters?.()
  resetSearch()
  confirm()
}

/**
 * 获取部门列表数据
 * @description 调用 API 获取部门树形数据，同步更新表格数据和树形选择器数据，
 *              并默认展开所有节点
 */
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

/** 表格设置组合式函数：管理表格尺寸和列可见性 */
const { tableSettingState, visibleColumns: baseColumns } = usePageTable({
  columns: columnItems,
  fetchData,
  wrapRef
})

/**
 * 最终可见列配置
 * @computed 在基础列配置上增强特定列的渲染逻辑：
 *   - name 列：添加自定义筛选下拉、搜索高亮渲染
 *   - status 列：渲染为 Switch 开关，支持直接切换状态
 *   - action 列：渲染操作按钮组（PlusOutlined 添加、EditOutlined 修改、TeamOutlined 成员、DeleteOutlined 删除）
 */
const visibleColumns = computed(() =>
  baseColumns.value.map((col) => {
    const base = { ...col } as Record<string, unknown>
    if (col.key === 'name') {
      base.customFilterDropdown = true
      base.onFilter = () => true
      base.onFilterDropdownOpenChange = (visible: boolean) => {
        if (visible) {
          setTimeout(() => searchInput.value?.focus(), 100)
        }
      }
      base.customRender = ({ text }: { text: string }) => {
        return h('span', { innerHTML: renderHighlightText(text) })
      }
    }
    if (col.key === 'status') {
      base.customRender = ({ record }: { record: DeptInfo }) => {
        return h(Switch, {
          checked: record.status === 1,
          onChange: (checked: boolean | string | number) =>
            handleStatusChange(record, Boolean(checked))
        })
      }
    }
    if (col.key === 'action') {
      base.customRender = ({ record }: { record: DeptInfo }) => {
        return h(Space, {}, () => [
          h(Button, { type: 'link', size: 'small', onClick: () => handleAddChild(record) }, () => [
            h(PlusOutlined),
            ' 添加'
          ]),
          h(Button, { type: 'link', size: 'small', onClick: () => handleEdit(record) }, () => [
            h(EditOutlined),
            ' 修改'
          ]),
          h(
            Button,
            { type: 'link', size: 'small', onClick: () => handleViewMembers(record) },
            () => [h(TeamOutlined), ' 成员']
          ),
          h(
            Popconfirm,
            {
              title: '确认要删除吗?',
              okText: '确定',
              cancelText: '取消',
              onConfirm: () => handleDelete(record)
            },
            () =>
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

/**
 * 新增顶级部门
 * @description 清空当前记录和上级部门ID，打开表单弹窗
 */
const handleAdd = () => {
  currentRecord.value = null
  parentId.value = undefined
  modalVisible.value = true
}

/**
 * 新增子部门
 * @param record - 父部门记录
 * @description 设置上级部门ID为当前记录的ID，打开表单弹窗
 */
const handleAddChild = (record: DeptInfo) => {
  currentRecord.value = null
  parentId.value = record.id
  modalVisible.value = true
}

/**
 * 编辑部门
 * @param record - 待编辑的部门记录
 * @description 设置当前记录，打开表单弹窗（编辑模式）
 */
const handleEdit = (record: DeptInfo) => {
  currentRecord.value = record
  parentId.value = undefined
  modalVisible.value = true
}

/**
 * 递归在树中查找并更新节点
 * @param list - 树节点列表
 * @param id - 目标节点 ID
 * @param updater - 更新函数，返回新节点
 * @returns 是否已找到并更新
 */
const updateTreeNode = (
  list: DeptInfo[],
  id: number,
  updater: (node: DeptInfo) => DeptInfo
): boolean => {
  for (let i = 0; i < list.length; i++) {
    if (list[i].id === id) {
      list[i] = updater(list[i])
      return true
    }
    if (list[i].children?.length) {
      if (updateTreeNode(list[i].children!, id, updater)) return true
    }
  }
  return false
}

/**
 * 递归在树中删除节点
 * @param list - 树节点列表
 * @param id - 目标节点 ID
 * @returns 是否已找到并删除
 */
const removeTreeNode = (list: DeptInfo[], id: number): boolean => {
  for (let i = 0; i < list.length; i++) {
    if (list[i].id === id) {
      list.splice(i, 1)
      return true
    }
    if (list[i].children?.length) {
      if (removeTreeNode(list[i].children!, id)) return true
    }
  }
  return false
}

/**
 * 表单提交成功回调（局部树数据更新）
 * @param record - 提交后的部门数据
 * @description 编辑模式：递归查找并更新树中对应节点；
 *              新增子部门：递归查找父节点并将新记录插入其 children；
 *              新增顶级部门：直接添加到列表末尾
 */
const handleSuccess = (record: DeptInfo) => {
  const normalized = { ...record, id: Number(record.id) }
  if (currentRecord.value) {
    updateTreeNode(tableData.value, normalized.id, (node) => ({
      ...node,
      ...normalized
    }))
  } else if (parentId.value !== undefined) {
    updateTreeNode(tableData.value, parentId.value, (parent) => ({
      ...parent,
      children: [...(parent.children || []), normalized]
    }))
  } else {
    tableData.value.push(normalized)
  }
  treeData.value = tableData.value
}

/**
 * 删除部门
 * @param record - 待删除的部门记录
 * @description 调用删除 API，成功后局部移除树节点
 */
const handleDelete = async (record: DeptInfo) => {
  try {
    await deleteDept(record.id)
    message.success('删除成功')
    removeTreeNode(tableData.value, record.id)
    treeData.value = tableData.value
  } catch (error) {
    if (import.meta.env.DEV) console.error('[Dept] Delete dept failed:', error)
  }
}

/**
 * 切换部门状态（乐观更新）
 * @param record - 部门记录
 * @param checked - 开关状态，true 为启用，false 为禁用
 * @description 先更新本地状态，调用 API 失败后回滚并提示错误
 */
const handleStatusChange = async (record: DeptInfo, checked: boolean) => {
  const oldStatus = record.status
  record.status = checked ? 1 : 0
  try {
    await changeDeptStatus(record.id, checked ? 1 : 0)
    message.success(checked ? '部门已启用' : '部门已禁用')
  } catch (error: unknown) {
    record.status = oldStatus
    message.error('状态变更失败，请重试')
    if (import.meta.env.DEV) console.error('[Dept] Change dept status failed:', error)
  }
}

/**
 * 查看部门成员
 * @param record - 部门记录
 * @description 设置当前部门ID和名称，打开成员查看弹窗
 */
const handleViewMembers = (record: DeptInfo) => {
  currentDeptId.value = Number(record.id)
  currentDeptName.value = record.name
  usersModalVisible.value = true
}

/** 页面挂载时加载部门数据 */
onMounted(() => {
  fetchData()
})
</script>

<style lang="less" scoped>
.page-container {
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
}
</style>
