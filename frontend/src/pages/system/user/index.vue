<!--
  @文件: index.vue
  @用途: 用户管理页面，提供用户的增删改查、状态切换、密码重置及数据导出等功能
  @描述: 系统用户管理核心页面，页面布局采用左右分栏结构：左侧为部门树（按部门筛选用户），右侧为搜索表单与用户数据表格。
         依赖组件：DeptTree（部门树）、UserFormModal（用户新增/编辑弹窗）、ResetPasswordModal（重置密码弹窗）、
         TableSetting（表格列设置）、DictSelect（字典选择器）、DictTag（字典标签）。
  @核心逻辑:
    1. 页面挂载 → fetchData() 加载用户列表，useDict 加载字典选项
    2. 搜索/部门选择/字典筛选变更 → 重置页码 → fetchData() 刷新数据
    3. 新增/编辑/删除/状态变更 → 操作成功后 fetchData() 刷新列表
-->
<template>
  <!-- 页面根容器，wrapRef 用于全屏表格模式的 DOM 引用 -->
  <div ref="wrapRef" class="page-container">
    <a-row :gutter="16" class="user-layout">
      <!-- 左侧：部门树区域，占 5/24 宽度 -->
      <a-col :span="5" class="dept-tree-col">
        <div class="tree-wrapper">
          <!-- 部门树组件，选中部门时触发 handleDeptSelect 按部门筛选用户 -->
          <DeptTree ref="deptTreeRef" @select="handleDeptSelect" />
        </div>
      </a-col>

      <!-- 右侧：搜索表单 + 数据表格区域，占 19/24 宽度 -->
      <a-col :span="19">
        <!-- 搜索表单区域 -->
        <div class="search-card">
          <a-form layout="inline" :model="searchForm">
            <!-- 关键词搜索：支持按用户名/昵称/邮箱/手机号模糊查询，按 Enter 或点击查询按钮生效 -->
            <a-form-item label="关键词" html-for="user-search-keyword">
              <a-input
                id="user-search-keyword"
                v-model:value="searchForm.keyword"
                name="keyword"
                placeholder="用户名/昵称/邮箱/手机号"
                allow-clear
                @press-enter="handleSearch"
              />
            </a-form-item>
            <!-- 状态筛选：选择后需点击"查询"按钮手动生效，支持多次调整后再统一查询 -->
            <a-form-item label="状态" html-for="user-search-status">
              <DictSelect
                id="user-search-status"
                v-model:value="searchForm.status"
                name="status"
                dict-code="user_status"
                placeholder="全部状态"
                width="120px"
              />
            </a-form-item>
            <!-- 性别筛选：选择后需点击"查询"按钮手动生效，支持多次调整后再统一查询 -->
            <a-form-item label="性别" html-for="user-search-gender">
              <DictSelect
                id="user-search-gender"
                v-model:value="searchForm.gender"
                name="gender"
                dict-code="user_gender"
                placeholder="全部性别"
                width="120px"
              />
            </a-form-item>
            <!-- 查询与重置按钮：筛选条件变更后需点击"查询"按钮手动生效 -->
            <a-form-item>
              <a-space>
                <a-tooltip
                  :title="hasPendingChanges ? '筛选条件已变更，点击查询生效' : '查询当前筛选条件'"
                >
                  <a-button
                    type="primary"
                    :class="{ 'has-pending': hasPendingChanges }"
                    @click="handleSearch"
                  >
                    <SearchOutlined />
                    查询
                  </a-button>
                </a-tooltip>
                <a-button @click="handleReset">
                  <ReloadOutlined />
                  重置
                </a-button>
              </a-space>
            </a-form-item>
          </a-form>
        </div>

        <!-- 数据表格区域 -->
        <div class="s-table-wrapper">
          <!-- 表格顶部工具栏：新增、导出按钮及表格设置 -->
          <div class="s-table-header">
            <div class="table-header-container">
              <div class="flex items-center">
                <div class="table-header-toolbar">
                  <div>
                    <a-button type="primary" @click="handleAdd">
                      <PlusOutlined />
                      新增
                    </a-button>
                    <a-button @click="handleExport">
                      <ExportOutlined />
                      导出
                    </a-button>
                  </div>
                  <!-- 表格列设置组件（桌面端显示），支持列显隐切换、密度调整、全屏模式 -->
                  <TableSetting class="table-header__toolbar-desktop" />
                </div>
              </div>
            </div>
          </div>

          <!-- 用户数据表格
            - visibleColumns：经 TableSetting 过滤后的可见列
            - tableSettingState.size：表格密度（大/中/小）
            - row-key：以 id 作为行唯一标识
            - @change：分页/排序/筛选变化时触发
          -->
          <a-table
            :columns="visibleColumns"
            :data-source="tableData"
            :loading="loading"
            :pagination="pagination"
            :size="tableSettingState.size"
            :scroll="{ x: 1600 }"
            row-key="id"
            @change="handleTableChange"
          />
        </div>
      </a-col>
    </a-row>

    <!-- 用户新增/编辑弹窗
      - visible：控制弹窗显隐
      - record：传入当前用户数据时为编辑模式，传入 null 时为新增模式
      - @success：表单提交成功后刷新列表
    -->
    <UserFormModal
      v-model:visible="modalVisible"
      :record="currentRecord"
      @success="handleSuccess"
    />

    <!-- 重置密码弹窗
      - userId：需要重置密码的用户 ID
      - @success：重置成功后刷新列表
    -->
    <ResetPasswordModal
      v-model:visible="resetPwdVisible"
      :user-id="currentRecord?.id"
      @success="handleSearch"
    />
  </div>
</template>

<script setup lang="ts">
import {
  ref,
  reactive,
  computed,
  onMounted,
  onErrorCaptured,
  h,
  type ComponentPublicInstance
} from 'vue'
import {
  SearchOutlined,
  ReloadOutlined,
  PlusOutlined,
  ExportOutlined,
  EditOutlined,
  DeleteOutlined,
  KeyOutlined,
  UserOutlined
} from '@ant-design/icons-vue'
import { message, Avatar, Tag, Switch, Space, Button, Popconfirm } from 'ant-design-vue'
import { getUserList, deleteUser, changeUserStatus, exportUsers } from '@/api/user'
import type { UserInfo, UserQuery } from '@/api/user'
import { createPagination, type TablePaginationConfig } from '@/utils/common'
import UserFormModal from './components/UserFormModal.vue'
import ResetPasswordModal from './components/ResetPasswordModal.vue'
import DeptTree from './components/DeptTree.vue'
import TableSetting from '@/components/TableSetting/TableSetting.vue'
import { usePageTable } from '@/composables/usePageTable'
import type { ColumnItem } from '@/components/TableSetting/types'
import { DictSelect } from '@/components/Dict'
import { useDict, getDictLabel } from '@/composables/useDict'
import { useExport } from '@/composables'

/** 表格加载状态 */
const loading = ref(false)
/** 用户列表数据 */
const tableData = ref<UserInfo[]>([])
/** 用户新增/编辑弹窗是否可见 */
const modalVisible = ref(false)
/** 重置密码弹窗是否可见 */
const resetPwdVisible = ref(false)
/** 当前操作的用户记录（新增时为 null，编辑/重置密码时为对应用户信息） */
const currentRecord = ref<UserInfo | null>(null)
/** 部门树组件实例引用，用于调用 resetSelection 方法 */
const deptTreeRef = ref<InstanceType<typeof DeptTree> | null>(null)
/** 当前选中的部门 ID，用于按部门筛选用户列表 */
const selectedDeptId = ref<number | undefined>(undefined)
/** 页面根容器 DOM 引用，供全屏表格模式使用 */
const wrapRef = ref<HTMLElement | null>(null)

/**
 * 搜索表单数据
 * @property page - 当前页码
 * @property limit - 每页条数
 * @property keyword - 搜索关键词（匹配用户名/昵称/邮箱/手机号）
 * @property status - 用户状态筛选（1-正常，0-禁用），由字典组件 DictSelect 管理
 * @property gender - 性别筛选（0-未知，1-男，2-女），由字典组件 DictSelect 管理
 */
const searchForm = reactive<UserQuery>({
  page: 1,
  limit: 10,
  keyword: '',
  status: undefined,
  gender: undefined
})

/** 分页配置，由 createPagination 工具函数创建默认值 */
const pagination = reactive(createPagination())

/**
 * 已应用的筛选条件快照
 * 用于对比当前表单值与上次查询时的值，判断是否存在未提交的变更。
 * 每次执行 handleSearch 时同步更新。
 */
const appliedFilters = reactive({
  keyword: '' as string | undefined,
  status: undefined as number | undefined,
  gender: undefined as number | undefined,
  dept_id: undefined as number | undefined
})

/**
 * 判断当前筛选条件是否存在未提交的变更
 * 当表单值与已应用快照不一致时返回 true，查询按钮显示视觉提示。
 */
const hasPendingChanges = computed(() => {
  return (
    searchForm.keyword !== appliedFilters.keyword ||
    searchForm.status !== appliedFilters.status ||
    searchForm.gender !== appliedFilters.gender ||
    selectedDeptId.value !== appliedFilters.dept_id
  )
})

/**
 * 字典数据加载
 * 通过 useDict 组合式函数按字典编码加载选项数据，支持全局缓存避免重复请求。
 * - genderDict：用户性别字典（user_gender），用于表格性别列的标签渲染
 * - statusDict：用户状态字典（user_status），用于表格状态列的标签渲染
 */
const genderDict = useDict({ code: 'user_gender' })
const statusDict = useDict({ code: 'user_status' })

/**
 * 表格列配置项
 * 每个列项包含 key（唯一标识）、title（列标题）、dataIndex（数据字段名）、width（列宽）等属性。
 * 需要自定义渲染的列通过 customRender 使用 h() 函数创建 VNode。
 * 该配置同时供 TableSetting 组件使用，支持用户自定义列的显隐和顺序。
 */
const columnItems: ColumnItem[] = [
  {
    key: 'avatar',
    title: '头像',
    dataIndex: 'avatar',
    width: 80,
    align: 'center',
    // 头像列：使用 Avatar 组件渲染，无头像时显示 UserOutlined 图标作为兜底
    customRender: ({ record }: { record: UserInfo }) =>
      h(Avatar, { src: record.avatar, size: 32 }, () => h(UserOutlined))
  },
  { key: 'username', title: '用户名', dataIndex: 'username', width: 120 },
  { key: 'nickname', title: '昵称', dataIndex: 'nickname', width: 120 },
  { key: 'email', title: '邮箱', dataIndex: 'email', width: 180 },
  { key: 'mobile', title: '手机号', dataIndex: 'mobile', width: 140 },
  {
    key: 'gender',
    title: '性别',
    dataIndex: 'gender',
    width: 80,
    align: 'center',
    // 性别列：复用页面级 genderDict 渲染 Tag，避免逐行创建 DictTag 实例
    customRender: ({ record }: { record: UserInfo }) => {
      const label = getDictLabel(genderDict.options.value, record.gender)
      const color = record.gender === 1 ? 'blue' : record.gender === 2 ? 'pink' : undefined
      return h(Tag, { color }, () => label || '--')
    }
  },
  {
    key: 'dept_name',
    title: '部门',
    dataIndex: 'dept_name',
    width: 200,
    customRender: ({ record }: { record: UserInfo }) => {
      const depts = record.depts
      if (!depts || depts.length === 0) {
        return record.dept_name || '--'
      }
      return h(Space, { wrap: true }, () =>
        depts.map((dept: { dept_id: number; dept_name: string; is_primary: number }) =>
          h(
            Tag,
            {
              key: dept.dept_id,
              color: dept.is_primary ? 'blue' : 'default'
            },
            () => (dept.is_primary ? `${dept.dept_name}(主)` : dept.dept_name)
          )
        )
      )
    }
  },
  {
    key: 'roles',
    title: '角色',
    dataIndex: 'roles',
    width: 200,
    // 角色列：遍历 roles 数组，每个角色渲染为蓝色 Tag，使用 Space 组件自动换行
    customRender: ({ record }: { record: UserInfo }) =>
      h(
        Space,
        { wrap: true },
        () =>
          record.roles?.map((role: { id: number; name: string }) =>
            h(Tag, { key: role.id, color: 'blue' }, () => role.name)
          ) || []
      )
  },
  {
    key: 'status',
    title: '状态',
    dataIndex: 'status',
    width: 100,
    align: 'center',
    // 状态列：使用 Switch 组件渲染，切换时调用 handleStatusChange 修改用户状态
    customRender: ({ record }: { record: UserInfo }) =>
      h(Switch, {
        checked: record.status === 1,
        onChange: (checked: string | number | boolean) => {
          handleStatusChange(record, Boolean(checked))
        }
      })
  },
  { key: 'last_login_time', title: '最后登录', dataIndex: 'last_login_time', width: 160 },
  {
    key: 'action',
    title: '操作',
    dataIndex: 'action',
    width: 300,
    fixed: 'right',
    // 操作列：固定在右侧，包含编辑、重置密码、删除三个操作按钮
    // 删除按钮使用 Popconfirm 二次确认，防止误操作
    customRender: ({ record }: { record: UserInfo }) =>
      h(Space, null, () => [
        h(Button, { type: 'link', size: 'small', onClick: () => handleEdit(record) }, () => [
          h(EditOutlined),
          ' 编辑'
        ]),
        h(Button, { type: 'link', size: 'small', onClick: () => handleResetPwd(record) }, () => [
          h(KeyOutlined),
          ' 重置密码'
        ]),
        h(
          Popconfirm,
          { title: '确定要删除该用户吗？', onConfirm: () => handleDelete(record) },
          () =>
            h(Button, { type: 'link', danger: true, size: 'small' }, () => [
              h(DeleteOutlined),
              ' 删除'
            ])
        )
      ])
  }
]

/**
 * 获取用户列表数据
 * 综合分页参数、搜索条件及部门筛选条件请求后端接口，
 * 成功后更新表格数据和分页总数，失败由请求拦截器统一处理。
 * @param silent - 静默模式，为 true 时不显示加载动画，适用于筛选操作等需要即时响应的场景
 */
const fetchData = async (silent = false) => {
  if (!silent) loading.value = true
  try {
    const res = await getUserList({
      page: pagination.current,
      limit: pagination.pageSize,
      keyword: searchForm.keyword,
      status: searchForm.status,
      gender: searchForm.gender,
      dept_id: selectedDeptId.value
    })
    tableData.value = res.data.list.map((u: UserInfo) => ({ ...u, id: Number(u.id) }))
    pagination.total = res.data.pagination.total
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[UserPage] fetchData failed:', error)
  } finally {
    if (!silent) loading.value = false
  }
}

/**
 * 表格设置组合式函数
 * - tableSettingState：表格设置状态（密度、全屏等）
 * - visibleColumns：根据用户列设置过滤后的可见列配置
 */
const { tableSettingState, visibleColumns } = usePageTable({
  columns: columnItems,
  fetchData,
  wrapRef
})

/** useExport 组合式函数：CSV 导出工具 */
const { downloadCsv, escapeCsvField } = useExport()

/**
 * 部门树选中事件处理
 * 选中部门后按该部门筛选用户列表，同时重置页码到第一页。
 * 使用静默模式避免加载动画造成视觉干扰。
 * @param deptId - 选中的部门 ID，undefined 表示取消选中
 */
const handleDeptSelect = (deptId: number | undefined) => {
  selectedDeptId.value = deptId
  pagination.current = 1
  fetchData(true)
}

/**
 * 搜索按钮点击处理
 * 重置页码到第一页后重新加载数据，确保搜索结果从第一页开始展示。
 * 同步已应用筛选快照，清除 hasPendingChanges 状态。
 * 使用静默模式避免加载动画造成视觉干扰。
 */
const handleSearch = () => {
  pagination.current = 1
  appliedFilters.keyword = searchForm.keyword
  appliedFilters.status = searchForm.status
  appliedFilters.gender = searchForm.gender
  appliedFilters.dept_id = selectedDeptId.value
  fetchData(true)
}

/**
 * 重置搜索条件
 * 清空所有搜索字段（关键词、状态、性别）及部门选择，
 * 同时重置部门树的选中状态和已应用筛选快照，然后重新加载数据。
 * 使用静默模式避免加载动画造成视觉干扰。
 */
const handleReset = () => {
  searchForm.keyword = ''
  searchForm.status = undefined
  searchForm.gender = undefined
  selectedDeptId.value = undefined
  deptTreeRef.value?.resetSelection()
  appliedFilters.keyword = ''
  appliedFilters.status = undefined
  appliedFilters.gender = undefined
  appliedFilters.dept_id = undefined
  pagination.current = 1
  fetchData(true)
}

/**
 * 表格分页/排序/筛选变化事件处理
 * 当用户切换页码或每页条数时，同步更新分页状态并重新加载数据。
 * @param pag - Ant Design 表格变更事件中的分页配置对象
 */
const handleTableChange = (pag: TablePaginationConfig) => {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  fetchData()
}

/**
 * 新增用户
 * 将当前记录置空（表示新增模式），打开用户表单弹窗。
 */
const handleAdd = () => {
  currentRecord.value = null
  modalVisible.value = true
}

/**
 * 编辑用户
 * 将当前记录设置为待编辑用户数据，打开用户表单弹窗（编辑模式）。
 * @param record - 待编辑的用户信息
 */
const handleEdit = (record: UserInfo) => {
  currentRecord.value = record
  modalVisible.value = true
}

/**
 * 表单提交成功回调（局部数据更新）
 * @param record - 提交后的用户数据
 * @description 编辑模式：更新匹配行；新增模式：插入到开头
 */
const handleSuccess = (record: UserInfo) => {
  const normalized = { ...record, id: Number(record.id) }
  const index = tableData.value.findIndex((item) => item.id === normalized.id)
  if (index !== -1) {
    tableData.value[index] = { ...tableData.value[index], ...normalized }
  } else {
    tableData.value.unshift(normalized)
    pagination.total = (pagination.total ?? 0) + 1
  }
}

/**
 * 删除用户
 * 调用删除接口，成功后提示并刷新列表。
 * @param record - 待删除的用户信息
 */
const handleDelete = async (record: UserInfo) => {
  try {
    await deleteUser(record.id)
    message.success('删除成功')
    tableData.value = tableData.value.filter((item) => item.id !== record.id)
    pagination.total = Math.max(0, (pagination.total ?? 0) - 1)
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[UserPage] handleDelete failed:', error)
  }
}

/**
 * 用户状态切换处理（乐观更新）
 * 先更新本地状态，调用 API 成功后提示；
 * 失败时回滚本地状态并提示错误信息。
 * @param record - 状态变更的用户信息
 * @param checked - 切换后的状态（true-启用，false-禁用）
 */
const handleStatusChange = async (record: UserInfo, checked: boolean) => {
  const oldStatus = record.status
  record.status = checked ? 1 : 0
  try {
    await changeUserStatus(record.id, checked ? 1 : 0)
    message.success(checked ? '用户已启用' : '用户已禁用')
  } catch (error: unknown) {
    record.status = oldStatus
    message.error('状态变更失败，请重试')
    if (import.meta.env.DEV) console.error('[UserPage] handleStatusChange failed:', error)
  }
}

/**
 * 重置密码
 * 将当前记录设置为对应用户，打开重置密码弹窗。
 * @param record - 需要重置密码的用户信息
 */
const handleResetPwd = (record: UserInfo) => {
  currentRecord.value = record
  resetPwdVisible.value = true
}

/**
 * 导出用户数据为 CSV 文件
 * @description 根据当前搜索条件调用导出接口获取数据，使用 downloadCsv 生成
 *              带 BOM 头的 UTF-8 CSV 文件，性别和状态字段通过字典标签转换为可读文本
 */
const handleExport = async () => {
  try {
    const res = await exportUsers({
      keyword: searchForm.keyword,
      status: searchForm.status,
      gender: searchForm.gender,
      dept_id: selectedDeptId.value
    })
    const data = res.data
    const headers = [
      'ID',
      '用户名',
      '昵称',
      '邮箱',
      '手机号',
      '性别',
      '部门',
      '角色',
      '状态',
      '最后登录',
      '创建时间'
    ]
    const rows = data.map((item: Record<string, unknown>) => [
      escapeCsvField(item.id),
      escapeCsvField(item.username),
      escapeCsvField(item.nickname),
      escapeCsvField(item.email),
      escapeCsvField(item.mobile),
      escapeCsvField(getDictLabel(genderDict.options.value, item.gender as number)),
      escapeCsvField(item.dept_name),
      escapeCsvField(item.roles),
      escapeCsvField(getDictLabel(statusDict.options.value, item.status as number)),
      escapeCsvField(item.last_login_time),
      escapeCsvField(item.create_time)
    ])
    downloadCsv({ filename: 'users', headers, rows })
    message.success('导出成功')
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[UserPage] handleExport failed:', error)
  }
}

/** 页面挂载时加载用户列表数据 */
onMounted(() => {
  fetchData()
})

/** 错误边界：捕获子组件异常，防止页面白屏 */
onErrorCaptured((error: Error, _instance: ComponentPublicInstance | null, info: string) => {
  if (import.meta.env.DEV) console.error('[UserPage] Error captured:', error, info)
  message.error('页面加载异常，请刷新重试')
  return false
})
</script>

<style lang="less" scoped>
/* 页面根容器样式 */
.page-container {
  /* 覆盖 Ant Design Card 头部默认样式，调整内边距和边框 */
  :deep(.ant-card .ant-card-head) {
    display: flex;
    justify-content: center;
    flex-direction: column;
    min-height: 48px;
    margin-bottom: -1px;
    padding: 0 24px;
    color: rgba(0, 0, 0, 0.88);
    font-weight: 600;
    font-size: 16px;
    background: transparent;
    border-bottom: none;
    border-radius: 4px 4px 0 0;
  }

  /* 用户管理页面左右分栏布局 */
  .user-layout {
    /* 左侧部门树列 */
    .dept-tree-col {
      /* 树容器高度占满视口减去顶部导航栏和页面间距 */
      height: calc(100vh - 160px);

      .tree-wrapper {
        background: var(--ant-color-bg-container, #fff);
        border-radius: var(--ant-border-radius, 8px);
        height: 100%;
        overflow: hidden;
      }
    }
  }

  /* 搜索表单卡片样式 */
  .search-card {
    /* 查询按钮待提交状态：筛选条件变更后显示脉冲动画提示用户点击生效 */
    .has-pending {
      animation: pulse-pending 1.5s ease-in-out infinite;
      box-shadow: 0 0 0 0 rgba(22, 119, 255, 0.4);
    }

    @keyframes pulse-pending {
      0% {
        box-shadow: 0 0 0 0 rgba(22, 119, 255, 0.4);
      }

      70% {
        box-shadow: 0 0 0 6px rgba(22, 119, 255, 0);
      }

      100% {
        box-shadow: 0 0 0 0 rgba(22, 119, 255, 0);
      }
    }
  }

  /* 全屏表格模式样式
     通过给 page-container 添加 fullscreen-table 类名激活，
     将表格区域铺满整个视口，适用于数据量较大需要更多展示空间的场景。
  */
  &.fullscreen-table {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: var(--z-fullscreen-table);
    background: var(--ant-color-bg-container, #fff);
    padding: 16px;
    display: flex;
    flex-direction: column;
    overflow: visible;

    .user-layout {
      flex: 1;
      min-height: 0;
    }

    .search-card {
      flex-shrink: 0;
    }

    /* 表格区域在全屏模式下自适应填充剩余空间 */
    .s-table-wrapper {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 0;
    }

    .s-table-header {
      flex-shrink: 0;
    }

    /* 表格内容区域可滚动，避免全屏时内容溢出 */
    :deep(.ant-table-wrapper) {
      flex: 1;
      overflow: auto;
    }
  }
}
</style>
