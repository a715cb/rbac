<template>
  <a-modal
    :title="`部门成员 - ${deptName}`"
    :open="visible"
    :footer="null"
    :width="640"
    :destroy-on-close="true"
    @cancel="handleCancel"
  >
    <a-table
      :columns="columns"
      :data-source="userList"
      :loading="loading"
      row-key="id"
      :pagination="false"
      size="small"
    >
      <template #bodyCell="{ text, column, record }">
        <template v-if="column.dataIndex === 'status'">
          <a-tag :color="record.status === 1 ? 'green' : 'red'">
            {{ record.status === 1 ? '启用' : '禁用' }}
          </a-tag>
        </template>
        <template v-if="column.dataIndex === 'is_primary'">
          <a-tag :color="record.is_primary === 1 ? 'blue' : 'orange'">
            {{ record.is_primary === 1 ? '主部门' : '兼职' }}
          </a-tag>
        </template>
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

interface Props {
  visible: boolean
  deptId?: number
  deptName?: string
}

const props = withDefaults(defineProps<Props>(), {
  deptId: undefined,
  deptName: ''
})

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
}>()

const loading = ref(false)
const userList = ref<DeptUserItem[]>([])

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

const handleCancel = () => {
  emit('update:visible', false)
}

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
