<!--
  @文件: DeptUsersModal.vue
  @用途: 部门成员查看弹窗组件
  @描述: 以弹窗形式展示指定部门下的所有成员列表，包含用户名、昵称、手机号、
         部门关系（主部门/兼职）及状态信息。弹窗打开时自动加载该部门成员数据，
         关闭时清空列表。
  @核心逻辑:
    - 监听 visible 属性，打开时调用 fetchUsers 获取成员数据
    - 通过 getDeptUsers API 按部门ID查询成员列表
    - 使用 a-table 展示成员信息，支持状态和部门关系的标签化展示
-->
<template>
  <!-- 部门成员弹窗：标题动态显示部门名称，无底部按钮 -->
  <a-modal
    :title="`部门成员 - ${deptName}`"
    :open="visible"
    :footer="null"
    :width="640"
    :destroy-on-close="true"
    @cancel="handleCancel"
  >
    <!-- 成员列表表格：不分页，紧凑模式 -->
    <a-table
      :columns="columns"
      :data-source="userList"
      :loading="loading"
      row-key="id"
      :pagination="false"
      size="small"
    >
      <!-- 自定义单元格渲染 -->
      <template #bodyCell="{ text, column, record }">
        <!-- 状态列：启用显示绿色标签，禁用显示红色标签 -->
        <template v-if="column.dataIndex === 'status'">
          <a-tag :color="record.status === 1 ? 'green' : 'red'">
            {{ record.status === 1 ? '启用' : '禁用' }}
          </a-tag>
        </template>
        <!-- 部门关系列：主部门显示蓝色标签，兼职显示橙色标签 -->
        <template v-if="column.dataIndex === 'is_primary'">
          <a-tag :color="record.is_primary === 1 ? 'blue' : 'orange'">
            {{ record.is_primary === 1 ? '主部门' : '兼职' }}
          </a-tag>
        </template>
        <!-- 其他列：直接显示文本内容 -->
        <template v-if="column.dataIndex !== 'status' && column.dataIndex !== 'is_primary'">
          {{ text }}
        </template>
      </template>
    </a-table>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { getDeptUsers } from '@/api/dept'
import type { DeptUserItem } from '@/api/dept'

/** 组件属性定义 */
interface Props {
  visible: boolean // 弹窗是否可见，支持 v-model:visible 双向绑定
  deptId?: number // 部门ID，用于查询该部门下的成员
  deptName?: string // 部门名称，用于弹窗标题展示
}

const props = withDefaults(defineProps<Props>(), {
  deptId: undefined,
  deptName: ''
})

/** 组件事件定义 */
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void // 更新弹窗可见状态，实现 v-model:visible
}>()

/** 加载状态：标记成员数据是否正在请求中 */
const loading = ref(false)

/** 成员列表数据：存储当前部门下的所有成员信息 */
const userList = ref<DeptUserItem[]>([])

/** 表格列配置：定义成员列表的列标题、数据字段和样式 */
const columns = [
  { title: '用户名', dataIndex: 'username', key: 'username' },
  { title: '昵称', dataIndex: 'nickname', key: 'nickname' },
  { title: '手机号', dataIndex: 'mobile', key: 'mobile' },
  {
    title: '部门关系',
    dataIndex: 'is_primary',
    key: 'is_primary',
    width: 100,
    align: 'center' as const
  },
  { title: '状态', dataIndex: 'status', key: 'status', width: 80, align: 'center' as const }
]

/**
 * 获取部门成员列表
 * @description 根据部门ID调用 API 获取该部门下的所有成员，并将 id 转为数字类型
 */
const fetchUsers = async () => {
  if (!props.deptId) return
  loading.value = true
  try {
    const res = await getDeptUsers(props.deptId)
    userList.value = res.data.list.map((u: DeptUserItem) => ({ ...u, id: Number(u.id) }))
  } catch (error) {
    if (import.meta.env.DEV) console.error('[DeptUsersModal] Fetch users failed:', error)
  } finally {
    loading.value = false
  }
}

/**
 * 弹窗关闭处理
 * @description 通知父组件更新 visible 状态为 false，关闭弹窗
 */
const handleCancel = () => {
  emit('update:visible', false)
}

/**
 * 监听弹窗可见状态变化
 * @param val - 弹窗是否可见
 * @description 弹窗打开时加载成员数据，关闭时清空成员列表
 */
watch(
  () => props.visible,
  (val) => {
    if (val) {
      fetchUsers()
    } else {
      userList.value = []
    }
  }
)
</script>
