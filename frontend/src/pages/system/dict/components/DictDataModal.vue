<template>
  <a-modal
    :title="isEdit ? '编辑字典数据' : '新增字典数据'"
    :open="visible"
    :confirm-loading="loading"
    :width="500"
    :destroy-on-close="true"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <a-form ref="formRef" :model="formState" :rules="rules" layout="vertical">
      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="字典标签" name="label" html-for="dict-data-label">
            <a-input
              id="dict-data-label"
              v-model:value="formState.label"
              name="label"
              placeholder="请输入字典标签"
            />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="字典键值" name="value" html-for="dict-data-value">
            <a-input
              id="dict-data-value"
              v-model:value="formState.value"
              name="value"
              placeholder="请输入字典键值"
            />
          </a-form-item>
        </a-col>
      </a-row>
      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="排序" name="sort" html-for="dict-data-sort">
            <a-input-number
              id="dict-data-sort"
              v-model:value="formState.sort"
              name="sort"
              :min="0"
              style="width: 100%"
            />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="状态" name="status" html-for="dict-data-status">
            <a-radio-group id="dict-data-status" v-model:value="formState.status" name="status">
              <a-radio :value="1">正常</a-radio>
              <a-radio :value="0">禁用</a-radio>
            </a-radio-group>
          </a-form-item>
        </a-col>
      </a-row>
      <a-form-item label="备注" name="remark" html-for="dict-data-remark">
        <a-textarea
          id="dict-data-remark"
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
import { createDictData, updateDictData } from '@/api/dict'
import type { DictDataInfo, DictDataForm } from '@/api/dict'

interface Props {
  visible: boolean
  record: DictDataInfo | null
  dictTypeId: number
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'success'): void
}>()

const formRef = ref<FormInstance>()
const loading = ref(false)

const isEdit = computed(() => !!props.record)

const formState = reactive<DictDataForm>({
  dict_type_id: props.dictTypeId,
  label: '',
  value: '',
  status: 1,
  sort: 0,
  remark: ''
})

const rules = {
  label: [{ required: true, message: '请输入字典标签', trigger: 'blur' }],
  value: [{ required: true, message: '请输入字典键值', trigger: 'blur' }]
}

const resetForm = () => {
  formState.dict_type_id = props.dictTypeId
  formState.label = ''
  formState.value = ''
  formState.status = 1
  formState.sort = 0
  formState.remark = ''
}

const loadFormData = () => {
  if (props.record) {
    formState.dict_type_id = props.record.dict_type_id
    formState.label = props.record.label
    formState.value = props.record.value
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
      await updateDictData(props.record.id, formState)
      message.success('更新成功')
    } else {
      await createDictData(formState)
      message.success('创建成功')
    }
    emit('success')
    emit('update:visible', false)
    resetForm()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[DictDataModal] handleSubmit failed:', error)
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
