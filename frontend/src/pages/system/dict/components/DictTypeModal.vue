<template>
  <a-modal
    :title="isEdit ? '编辑字典类型' : '新增字典类型'"
    :open="visible"
    :confirm-loading="loading"
    :width="500"
    :destroy-on-close="true"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <a-form ref="formRef" :model="formState" :rules="rules" layout="vertical">
      <a-form-item label="字典名称" name="name" html-for="dict-type-name">
        <a-input
          id="dict-type-name"
          v-model:value="formState.name"
          name="name"
          placeholder="请输入字典名称"
        />
      </a-form-item>
      <a-form-item label="字典编码" name="code" html-for="dict-type-code">
        <a-input
          id="dict-type-code"
          v-model:value="formState.code"
          name="code"
          placeholder="请输入字典编码"
          :disabled="isEdit"
        />
      </a-form-item>
      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="数据类型" name="type" html-for="dict-type-type">
            <a-select
              id="dict-type-type"
              v-model:value="formState.type"
              name="type"
              placeholder="请选择数据类型"
            >
              <a-select-option value="string">字符串</a-select-option>
              <a-select-option value="number">数字</a-select-option>
              <a-select-option value="date">日期</a-select-option>
              <a-select-option value="time">时间</a-select-option>
            </a-select>
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="排序" name="sort" html-for="dict-type-sort">
            <a-input-number
              id="dict-type-sort"
              v-model:value="formState.sort"
              name="sort"
              :min="0"
              style="width: 100%"
            />
          </a-form-item>
        </a-col>
      </a-row>
      <a-form-item label="状态" name="status" html-for="dict-type-status">
        <a-radio-group id="dict-type-status" v-model:value="formState.status" name="status">
          <a-radio :value="1">正常</a-radio>
          <a-radio :value="0">禁用</a-radio>
        </a-radio-group>
      </a-form-item>
      <a-form-item label="备注" name="remark" html-for="dict-type-remark">
        <a-textarea
          id="dict-type-remark"
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
import { createDictType, updateDictType } from '@/api/dict'
import type { DictTypeInfo, DictTypeForm } from '@/api/dict'

interface Props {
  visible: boolean
  record: DictTypeInfo | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'success'): void
}>()

const formRef = ref<FormInstance>()
const loading = ref(false)

const isEdit = computed(() => !!props.record)

const formState = reactive<DictTypeForm>({
  name: '',
  code: '',
  type: 'string',
  status: 1,
  sort: 0,
  remark: ''
})

const rules = {
  name: [{ required: true, message: '请输入字典名称', trigger: 'blur' }],
  code: [
    { required: true, message: '请输入字典编码', trigger: 'blur' },
    {
      pattern: /^[a-zA-Z][a-zA-Z0-9_]*$/,
      message: '编码只能包含字母、数字和下划线，且以字母开头',
      trigger: 'blur'
    }
  ]
}

const resetForm = () => {
  formState.name = ''
  formState.code = ''
  formState.type = 'string'
  formState.status = 1
  formState.sort = 0
  formState.remark = ''
}

const loadFormData = () => {
  if (props.record) {
    formState.name = props.record.name
    formState.code = props.record.code
    formState.type = props.record.type || 'string'
    formState.status = props.record.status
    formState.sort = props.record.sort || 0
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
      await updateDictType(props.record.id, formState)
      message.success('更新成功')
    } else {
      await createDictType(formState)
      message.success('创建成功')
    }
    emit('success')
    emit('update:visible', false)
    resetForm()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[DictTypeModal] handleSubmit failed:', error)
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
