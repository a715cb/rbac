<template>
  <a-modal
    :title="isEdit ? '编辑接口' : '新增接口'"
    :open="visible"
    :confirm-loading="loading"
    :confirm-disabled="detailLoading"
    :width="600"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <a-spin :spinning="detailLoading">
      <a-form ref="formRef" :model="formState" :rules="rules" layout="vertical">
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="接口名称" name="name" html-for="api-name">
              <a-input
                id="api-name"
                v-model:value="formState.name"
                name="name"
                placeholder="请输入接口名称"
              />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="接口标识" name="code" html-for="api-code">
              <a-input
                id="api-code"
                v-model:value="formState.code"
                name="code"
                placeholder="请输入接口标识"
              />
            </a-form-item>
          </a-col>
        </a-row>

        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="请求方法" name="method" html-for="api-method">
              <a-select
                id="api-method"
                v-model:value="formState.method"
                name="method"
                placeholder="请选择请求方法"
              >
                <a-select-option v-for="m in HTTP_METHODS" :key="m.value" :value="m.value">
                  {{ m.label }}
                </a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="接口路径" name="path" html-for="api-path">
              <a-input
                id="api-path"
                v-model:value="formState.path"
                name="path"
                placeholder="请输入接口路径，如 /admin/users"
              />
            </a-form-item>
          </a-col>
        </a-row>

        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="所属菜单" name="menu_id" html-for="api-menu-id">
              <a-tree-select
                id="api-menu-id"
                v-model:value="formState.menu_id"
                name="menu_id"
                :tree-data="menuTreeData"
                placeholder="请选择所属菜单"
                allow-clear
                tree-default-expand-all
                :field-names="{ children: 'children', label: 'name', value: 'id' }"
              />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="分组" name="group" html-for="api-group">
              <a-input
                id="api-group"
                v-model:value="formState.group"
                name="group"
                placeholder="请输入分组名称"
              />
            </a-form-item>
          </a-col>
        </a-row>

        <a-form-item label="状态" name="status" html-for="api-status">
          <a-radio-group id="api-status" v-model:value="formState.status" name="status">
            <a-radio :value="1">正常</a-radio>
            <a-radio :value="0">禁用</a-radio>
          </a-radio-group>
        </a-form-item>
      </a-form>
    </a-spin>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed, onMounted } from 'vue'
import type { FormInstance } from 'ant-design-vue'
import { message } from 'ant-design-vue'
import { createApi, updateApi, getApiDetail, HTTP_METHODS } from '@/api/api'
import type { ApiInfo, ApiForm } from '@/api/api'
import { useMenuTree } from '@/composables/useTreeData'

interface Props {
  visible: boolean
  record: ApiInfo | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'success'): void
}>()

const formRef = ref<FormInstance>()
const loading = ref(false)
const detailLoading = ref(false)
const { menuTreeData, fetchMenuTree } = useMenuTree()

const isEdit = computed(() => !!props.record)

const formState = reactive<ApiForm>({
  name: '',
  code: '',
  method: 'GET',
  path: '',
  menu_id: undefined,
  group: '',
  status: 1
})

const rules = {
  name: [{ required: true, message: '请输入接口名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入接口标识', trigger: 'blur' }],
  method: [{ required: true, message: '请选择请求方法', trigger: 'change' }],
  path: [
    { required: true, message: '请输入接口路径', trigger: 'blur' },
    { pattern: /^\//, message: '路径必须以 / 开头', trigger: 'blur' }
  ]
}

const resetForm = () => {
  formState.name = ''
  formState.code = ''
  formState.method = 'GET'
  formState.path = ''
  formState.menu_id = undefined
  formState.group = ''
  formState.status = 1
}

const loadFormData = async () => {
  if (props.record) {
    detailLoading.value = true
    try {
      const res = await getApiDetail(props.record.id)
      const detail = res.data
      formState.name = detail.name
      formState.code = detail.code
      formState.method = detail.method
      formState.path = detail.path
      formState.menu_id = detail.menu_id || undefined
      formState.group = detail.group || ''
      formState.status = detail.status
    } catch (error: unknown) {
      if (import.meta.env.DEV) console.error('[ApiFormModal] loadFormData failed:', error)
      message.error('获取接口详情失败')
      emit('update:visible', false)
    } finally {
      detailLoading.value = false
    }
  } else {
    resetForm()
  }
}

const handleSubmit = async () => {
  if (!formRef.value) return
  try {
    await formRef.value.validate()
  } catch {
    return
  }

  loading.value = true
  try {
    if (isEdit.value && props.record) {
      await updateApi(props.record.id, formState)
      message.success('更新成功')
    } else {
      await createApi(formState)
      message.success('创建成功')
    }
    emit('success')
    emit('update:visible', false)
    resetForm()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[ApiFormModal] handleSubmit failed:', error)
    // error handled by request interceptor
  } finally {
    loading.value = false
  }
}

const handleCancel = () => {
  emit('update:visible', false)
  formRef.value?.resetFields()
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

onMounted(() => {
  fetchMenuTree()
})
</script>
