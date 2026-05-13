<!--
  @文件: index.vue
  @用途: 角色管理页面，提供角色的增删改查、状态切换、权限分配和数据范围设置
  @描述: 系统管理-角色管理核心页面，页面结构为搜索栏→操作工具栏→数据表格→弹窗（新增/编辑、权限分配、数据范围）。
         集成字典组件：DictSelect用于状态筛选（role_status）、表格列标签（data_scope），useDict提供字典缓存与自动刷新。
  @核心逻辑:
    1. 页面挂载 → fetchData() 加载角色列表，useDict 加载字典选项
    2. 搜索/字典筛选变更 → 重置页码 → fetchData() 刷新数据
    3. 新增/编辑/删除/状态变更 → 操作成功后 fetchData() 刷新列表
-->
<template>
  <div ref="wrapRef" class="page-container">
    <!-- 搜索区域：支持按关键词和状态筛选角色，状态选项来源于字典管理系统 -->
    <div class="search-card">
      <a-form layout="inline" :model="searchForm">
        <a-form-item label="关键词" html-for="role-search-keyword">
          <a-input
            id="role-search-keyword"
            v-model:value="searchForm.keyword"
            name="keyword"
            placeholder="角色名称/标识"
            allow-clear
            @press-enter="handleSearch"
          />
        </a-form-item>
        <!-- 状态筛选：通过 DictSelect 组件从字典管理系统动态加载选项（role_status），
             字典数据变更后自动刷新选项，无需重启系统 -->
        <a-form-item label="状态" html-for="role-search-status">
          <DictSelect
            id="role-search-status"
            v-model:value="searchForm.status"
            name="status"
            dict-code="role_status"
            placeholder="全部状态"
            width="120px"
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
      <!-- 表格顶部工具栏：新增按钮 + 表格设置（列显隐、密度等） -->
      <div class="s-table-header">
        <div class="table-header-container">
          <div class="flex items-center">
            <div class="table-header-toolbar">
              <div>
                <a-button v-auth="'permission_role:add'" type="primary" @click="handleAdd">
                  <PlusOutlined />
                  新增
                </a-button>
              </div>
              <TableSetting class="table-header__toolbar-desktop" />
            </div>
          </div>
        </div>
      </div>

      <!-- 角色数据表格，visibleColumns 由 usePageTable 根据用户列设置过滤生成 -->
      <a-table
        :columns="visibleColumns"
        :data-source="tableData"
        :loading="loading"
        :pagination="pagination"
        :size="tableSettingState.size"
        :scroll="{ x: 1100 }"
        row-key="id"
        @change="handleTableChange"
      />
    </div>

    <!-- 新增/编辑角色弹窗 -->
    <RoleFormModal v-model:visible="modalVisible" :record="currentRecord" @success="handleSearch" />

    <!-- 权限分配弹窗：为角色分配菜单/按钮权限 -->
    <RolePermissionModal
      v-model:visible="permissionModalVisible"
      :role-id="currentRecord?.id"
      :role-name="currentRecord?.name"
      @success="handleSearch"
    />

    <!-- 数据范围弹窗：设置角色可访问的数据边界（全部/本部门/自定义等） -->
    <RoleDataScopeModal
      v-model:visible="dataScopeModalVisible"
      :role-id="currentRecord?.id"
      :role-name="currentRecord?.name"
      @success="handleSearch"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, h } from 'vue'
import {
  SearchOutlined,
  ReloadOutlined,
  PlusOutlined,
  EditOutlined,
  DeleteOutlined,
  SafetyOutlined,
  ClusterOutlined
} from '@ant-design/icons-vue'
import { message, Tag, Switch, Badge, Space, Button, Popconfirm } from 'ant-design-vue'
import { getRoleList, deleteRole, changeRoleStatus } from '@/api/role'
import type { RoleInfo, RoleQuery } from '@/api/role'
import { createPagination, type TablePaginationConfig } from '@/utils/common'
import RoleFormModal from './components/RoleFormModal.vue'
import RolePermissionModal from './components/RolePermissionModal.vue'
import RoleDataScopeModal from './components/RoleDataScopeModal.vue'
import TableSetting from '@/components/TableSetting/TableSetting.vue'
import { usePageTable } from '@/composables/usePageTable'
import type { ColumnItem } from '@/components/TableSetting/types'
import { DictSelect } from '@/components/Dict'
import { useDict, getDictLabel } from '@/composables/useDict'
import { useUserStore } from '@/stores/user'

/** 用户权限 Store，用于按钮级权限判断 */
const userStore = useUserStore()

/** 表格加载状态 */
const loading = ref(false)
/** 角色列表数据 */
const tableData = ref<RoleInfo[]>([])
/** 新增/编辑弹窗可见性 */
const modalVisible = ref(false)
/** 权限分配弹窗可见性 */
const permissionModalVisible = ref(false)
/** 数据范围弹窗可见性 */
const dataScopeModalVisible = ref(false)
/** 当前操作的角色记录，新增时为 null，编辑/权限/数据范围操作时为对应角色 */
const currentRecord = ref<RoleInfo | null>(null)
/** 页面容器 DOM 引用，供 usePageTable 获取表格尺寸等信息 */
const wrapRef = ref<HTMLElement | null>(null)

/** 搜索表单，同时作为接口查询参数 */
const searchForm = reactive<RoleQuery>({
  page: 1,
  limit: 10,
  keyword: '',
  status: undefined
})

/** 分页配置，由 createPagination 工具函数创建默认值 */
const pagination = reactive(createPagination())

/**
 * 字典数据加载
 * 通过 useDict 组合式函数按字典编码加载选项数据，支持全局缓存避免重复请求。
 * - dataScopeDict：数据权限范围字典（data_scope），用于表格数据范围列的标签渲染
 *   状态筛选的字典数据由模板中 DictSelect 组件（dict-code="role_status"）自行加载，无需额外声明
 */
const dataScopeDict = useDict({ code: 'data_scope' })

/**
 * 获取数据范围对应的 Tag 颜色
 * 1-全部数据(blue) 2-本部门(green) 3-本部门及下级(orange) 4-仅本人(purple) 5-自定义(red)
 */
const dataScopeColor = (scope: number): string => {
  const colors: Record<number, string> = {
    1: 'blue',
    2: 'green',
    3: 'orange',
    4: 'purple',
    5: 'red'
  }
  return colors[scope] || 'default'
}

/**
 * 表格列定义
 * - data_scope：数据范围，以 Tag 形式展示，标签文本来源于字典（data_scope），颜色按范围类型映射
 * - user_count：关联用户数，以 Badge 数字徽标展示
 * - status：角色状态，以 Switch 开关展示，支持直接切换
 * - action：操作列，固定在右侧，包含编辑、权限、数据范围、删除四个操作
 */
const columnItems: ColumnItem[] = [
  { key: 'name', title: '角色名称', dataIndex: 'name', width: 150 },
  { key: 'code', title: '角色标识', dataIndex: 'code', width: 150 },
  {
    key: 'data_scope',
    title: '数据范围',
    dataIndex: 'data_scope',
    width: 120,
    align: 'center',
    customRender: ({ record }: { record: RoleInfo }) => {
      const label = getDictLabel(dataScopeDict.options.value, record.data_scope)
      return h(Tag, { color: dataScopeColor(record.data_scope) }, () => label || '--')
    }
  },
  { key: 'sort', title: '排序', dataIndex: 'sort', width: 80, align: 'center' },
  {
    key: 'user_count',
    title: '用户数',
    dataIndex: 'user_count',
    width: 80,
    align: 'center',
    customRender: ({ record }: { record: RoleInfo }) => {
      return h(Badge, { count: record.user_count, 'number-style': { backgroundColor: '#108ee9' } })
    }
  },
  {
    key: 'status',
    title: '状态',
    dataIndex: 'status',
    width: 100,
    align: 'center',
    customRender: ({ record }: { record: RoleInfo }) => {
      return h(Switch, {
        checked: record.status === 1,
        disabled: !userStore.hasPermission('permission_role:status'),
        onChange: (checked: boolean | string | number) =>
          handleStatusChange(record, Boolean(checked))
      })
    }
  },
  { key: 'remark', title: '备注', dataIndex: 'remark', width: 260 },
  {
    key: 'action',
    title: '操作',
    dataIndex: 'action',
    width: 350,
    fixed: 'right',
    customRender: ({ record }: { record: RoleInfo }) => {
      const buttons: ReturnType<typeof h>[] = []
      if (userStore.hasPermission('permission_role:edit')) {
        buttons.push(
          h(Button, { type: 'link', size: 'small', onClick: () => handleEdit(record) }, () => [
            h(EditOutlined),
            ' 编辑'
          ])
        )
      }
      if (userStore.hasPermission('permission_role:permission')) {
        buttons.push(
          h(
            Button,
            { type: 'link', size: 'small', onClick: () => handlePermissions(record) },
            () => [h(SafetyOutlined), ' 权限']
          )
        )
      }
      if (userStore.hasPermission('permission_role:data_scope')) {
        buttons.push(
          h(Button, { type: 'link', size: 'small', onClick: () => handleDataScope(record) }, () => [
            h(ClusterOutlined),
            ' 数据'
          ])
        )
      }
      if (userStore.hasPermission('permission_role:delete')) {
        buttons.push(
          h(
            Popconfirm,
            { title: '确定要删除该角色吗？', onConfirm: () => handleDelete(record) },
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
]

/**
 * 请求角色列表数据
 * 将接口返回的 id 统一转为 Number 类型，避免字符串 id 导致的比对问题
 */
const fetchData = async () => {
  loading.value = true
  try {
    const res = await getRoleList({
      page: pagination.current,
      limit: pagination.pageSize,
      keyword: searchForm.keyword,
      status: searchForm.status
    })
    tableData.value = res.data.list.map((r: RoleInfo) => ({ ...r, id: Number(r.id) }))
    pagination.total = res.data.pagination.total
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[RolePage] fetchData failed:', error)
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

/** 搜索：重置页码至第一页后请求数据 */
const handleSearch = () => {
  pagination.current = 1
  fetchData()
}

/** 重置：清空搜索条件并重新请求 */
const handleReset = () => {
  searchForm.keyword = ''
  searchForm.status = undefined
  pagination.current = 1
  fetchData()
}

/** 分页、排序变化回调：同步分页参数后重新请求数据 */
const handleTableChange = (pag: TablePaginationConfig) => {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  fetchData()
}

/** 新增角色：清空当前记录并打开表单弹窗 */
const handleAdd = () => {
  currentRecord.value = null
  modalVisible.value = true
}

/** 编辑角色：设置当前记录并打开表单弹窗（弹窗根据 currentRecord 是否为 null 区分新增/编辑模式） */
const handleEdit = (record: RoleInfo) => {
  currentRecord.value = record
  modalVisible.value = true
}

/** 删除角色：调用删除接口成功后刷新列表 */
const handleDelete = async (record: RoleInfo) => {
  try {
    await deleteRole(record.id)
    message.success('删除成功')
    fetchData()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[RolePage] handleDelete failed:', error)
  }
}

/**
 * 切换角色状态
 * @param record 目标角色
 * @param checked 开关状态，true-启用(1)，false-禁用(0)
 */
const handleStatusChange = async (record: RoleInfo, checked: boolean) => {
  try {
    await changeRoleStatus(record.id, checked ? 1 : 0)
    message.success(checked ? '角色已启用' : '角色已禁用')
    fetchData()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[RolePage] handleStatusChange failed:', error)
  }
}

/** 打开权限分配弹窗 */
const handlePermissions = (record: RoleInfo) => {
  currentRecord.value = record
  permissionModalVisible.value = true
}

/** 打开数据范围设置弹窗 */
const handleDataScope = (record: RoleInfo) => {
  currentRecord.value = record
  dataScopeModalVisible.value = true
}

/** 页面挂载时加载角色列表 */
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

  /* 全屏表格模式：覆盖整个视口，表格区域自适应填充剩余空间 */
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

/* 移动端小屏适配：表格横向可滚动 */
@media (max-width: 480px) {
  .page-container {
    :deep(.ant-table) {
      width: 100%;
      overflow-x: auto;
    }
  }
}
</style>
