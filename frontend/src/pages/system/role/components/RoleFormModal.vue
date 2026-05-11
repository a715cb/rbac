<template>
  <a-modal
    :title="isEdit ? '编辑角色' : '新增角色'"
    :open="visible"
    :confirm-loading="loading"
    :width="600"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <a-form ref="formRef" :model="formState" :rules="rules" layout="vertical">
      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="角色名称" name="name" html-for="role-name">
            <a-input
              id="role-name"
              v-model:value="formState.name"
              name="name"
              placeholder="请输入角色名称"
            />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="角色标识" name="code" html-for="role-code">
            <a-input
              id="role-code"
              v-model:value="formState.code"
              name="code"
              placeholder="请输入角色标识"
              :disabled="isEdit"
            />
          </a-form-item>
        </a-col>
      </a-row>

      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="排序" name="sort" html-for="role-sort">
            <a-input-number
              id="role-sort"
              v-model:value="formState.sort"
              name="sort"
              :min="0"
              style="width: 100%"
            />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="状态" name="status" html-for="role-status">
            <a-radio-group id="role-status" v-model:value="formState.status" name="status">
              <a-radio :value="1">正常</a-radio>
              <a-radio :value="0">禁用</a-radio>
            </a-radio-group>
          </a-form-item>
        </a-col>
      </a-row>

      <a-form-item label="备注" name="remark" html-for="role-remark">
        <a-textarea
          id="role-remark"
          v-model:value="formState.remark"
          name="remark"
          :rows="3"
          placeholder="请输入备注"
        />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import type { FormInstance } from 'ant-design-vue'
import { message } from 'ant-design-vue'
import { createRole, updateRole } from '@/api/role'
import type { RoleInfo, RoleForm } from '@/api/role'

interface Props {
  visible: boolean
  record: RoleInfo | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'success'): void
}>()

const formRef = ref<FormInstance>()
const loading = ref(false)

const isEdit = computed(() => !!props.record)

const formState = reactive<RoleForm>({
  name: '',
  code: '',
  sort: 0,
  status: 1,
  remark: ''
})

const rules = {
  name: [{ required: true, message: '请输入角色名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入角色标识', trigger: 'blur' }]
}

const resetForm = () => {
  formState.name = ''
  formState.code = ''
  formState.sort = 0
  formState.status = 1
  formState.remark = ''
}

const loadFormData = () => {
  if (props.record) {
    formState.name = props.record.name
    formState.code = props.record.code
    formState.sort = props.record.sort || 0
    formState.status = props.record.status
    formState.remark = props.record.remark || ''
  } else {
    resetForm()
  }
}

const handleSubmit = async () => {
  try {
    await formRef.value?.validate()
  } catch {
    return
  }

  loading.value = true
  try {
    if (isEdit.value && props.record) {
      await updateRole(props.record.id, {
        name: formState.name,
        code: formState.code,
        sort: formState.sort,
        status: formState.status,
        remark: formState.remark
      })
      message.success('更新成功')
    } else {
      await createRole(formState)
      message.success('创建成功')
    }
    emit('success')
    emit('update:visible', false)
    resetForm()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[RoleFormModal] handleSubmit failed:', error)
    // error handled by request interceptor
  } finally {
    loading.value = false
  }
}

const handleCancel = () => {
  emit('update:visible', false)
  resetForm()
}

watch(
  () => props.visible,
  (val) => {
    if (val) {
      loadFormData()
    }
  }
)
</script>
