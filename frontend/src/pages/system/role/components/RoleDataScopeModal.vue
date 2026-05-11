<template>
  <a-modal
    :title="`${roleName} - 数据权限`"
    :open="visible"
    :confirm-loading="loading"
    :width="600"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <a-form ref="formRef" :model="formState" :rules="rules" layout="vertical">
      <a-form-item label="数据范围" name="data_scope" html-for="role-data-scope">
        <a-radio-group id="role-data-scope" v-model:value="formState.data_scope" name="data_scope">
          <a-radio :value="1">全部数据</a-radio>
          <a-radio :value="2">本部门数据</a-radio>
          <a-radio :value="3">本部门及下级数据</a-radio>
          <a-radio :value="4">仅本人数据</a-radio>
          <a-radio :value="5">自定义</a-radio>
        </a-radio-group>
      </a-form-item>

      <a-form-item
        v-if="formState.data_scope === 5"
        label="选择部门"
        name="data_scope_dept_ids"
        html-for="role-data-scope-dept"
      >
        <a-tree
          id="role-data-scope-dept"
          v-model:checked-keys="checkedDeptKeys"
          checkable
          :tree-data="deptTreeData"
          :field-names="{ children: 'children', title: 'name', key: 'id' }"
          default-expand-all
        />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import type { FormInstance } from 'ant-design-vue'
import { message } from 'ant-design-vue'
import { setRoleDataScope } from '@/api/role'
import { useDeptTree } from '@/composables/useTreeData'

interface Props {
  visible: boolean
  roleId?: number
  roleName?: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'success'): void
}>()

const formRef = ref<FormInstance>()
const loading = ref(false)
const { deptTreeData, fetchDeptTree } = useDeptTree()
const checkedDeptKeys = ref<(number | string)[]>([])

const formState = reactive({
  data_scope: 1,
  data_scope_dept_ids: ''
})

const rules = {
  data_scope: [{ required: true, message: '请选择数据范围', trigger: 'change' }]
}

const handleSubmit = async () => {
  try {
    await formRef.value?.validate()
  } catch {
    return
  }

  if (!props.roleId) {
    message.error('角色 ID 无效')
    return
  }

  if (formState.data_scope === 5 && checkedDeptKeys.value.length === 0) {
    message.error('自定义数据权限必须选择部门')
    return
  }

  loading.value = true
  try {
    const deptIds = formState.data_scope === 5 ? checkedDeptKeys.value.join(',') : ''
    await setRoleDataScope(props.roleId, formState.data_scope, deptIds)
    message.success('数据权限配置成功')
    emit('success')
    emit('update:visible', false)
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[RoleDataScopeModal] handleSubmit failed:', error)
    // error handled by request interceptor
  } finally {
    loading.value = false
  }
}

const handleCancel = () => {
  emit('update:visible', false)
  formState.data_scope = 1
  checkedDeptKeys.value = []
}

watch(
  () => props.visible,
  (val) => {
    if (val) {
      fetchDeptTree()
    }
  }
)
</script>
