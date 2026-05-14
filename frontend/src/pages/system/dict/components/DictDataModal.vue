<!--
  @文件: DictDataModal.vue
  @用途: 字典数据新增/编辑弹窗组件
  @描述: 用于字典数据管理页面中新增或编辑字典数据项，支持表单校验、数据回填和提交操作
  @核心逻辑:
    1. 通过 props.record 是否存在判断当前为编辑或新增模式
    2. 弹窗打开时自动加载表单数据（编辑回填 / 新增重置）
    3. 提交时先进行表单校验，再根据模式调用对应的创建/更新 API
-->
<template>
  <!-- 字典数据新增/编辑弹窗 -->
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
      <!-- 第一行：字典标签 + 字典键值 -->
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
      <!-- 第二行：排序 + 状态 -->
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
      <!-- 备注 -->
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

/** 组件 Props 接口定义 */
interface Props {
  /** 弹窗是否可见，支持 v-model 双向绑定 */
  visible: boolean
  /** 当前编辑的字典数据记录，为 null 时表示新增模式 */
  record: DictDataInfo | null
  /** 所属字典类型 ID，用于关联字典数据与字典类型 */
  dictTypeId: number
}

const props = defineProps<Props>()

/** 组件事件定义 */
const emit = defineEmits<{
  /** 更新弹窗可见状态，用于 v-model:visible 双向绑定 */
  (e: 'update:visible', value: boolean): void
  /** 操作成功后触发，传递保存后的完整记录，供父组件局部更新 */
  (e: 'success', record: DictDataInfo): void
}>()

/** 表单实例引用，用于调用表单校验方法 */
const formRef = ref<FormInstance>()

/** 提交按钮加载状态，防止重复提交 */
const loading = ref(false)

/** 是否为编辑模式：根据 record 是否存在判断 */
const isEdit = computed(() => !!props.record)

/** 表单数据状态 */
const formState = reactive<DictDataForm>({
  dict_type_id: props.dictTypeId,
  label: '',
  value: '',
  status: 1,
  sort: 0,
  remark: ''
})

/** 表单校验规则 */
const rules = {
  label: [{ required: true, message: '请输入字典标签', trigger: 'blur' }],
  value: [{ required: true, message: '请输入字典键值', trigger: 'blur' }]
}

/**
 * 重置表单数据为初始默认值
 * @description 将所有表单字段恢复为新增模式的默认值，并重新绑定当前字典类型 ID
 */
const resetForm = () => {
  formState.dict_type_id = props.dictTypeId
  formState.label = ''
  formState.value = ''
  formState.status = 1
  formState.sort = 0
  formState.remark = ''
}

/**
 * 加载表单数据
 * @description 编辑模式下将 record 数据回填至表单，新增模式下重置表单为默认值
 */
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

/**
 * 提交表单
 * @description 先进行表单校验，校验通过后根据当前模式调用创建或更新 API，
 *              成功后关闭弹窗并通知父组件刷新数据
 */
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
      emit('success', { ...formState, id: props.record.id } as DictDataInfo)
    } else {
      const res = await createDictData(formState)
      message.success('创建成功')
      emit('success', { ...formState, id: res.data.id } as DictDataInfo)
    }
    emit('update:visible', false)
    resetForm()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[DictDataModal] handleSubmit failed:', error)
    // error handled by request interceptor
  } finally {
    loading.value = false
  }
}

/**
 * 取消操作
 * @description 关闭弹窗并重置表单数据
 */
const handleCancel = () => {
  emit('update:visible', false)
  resetForm()
}

/**
 * 监听弹窗可见状态变化
 * @description 弹窗打开时自动加载表单数据（编辑回填或新增重置），
 *              确保每次打开弹窗时表单数据与当前 record 同步
 */
watch(
  () => props.visible,
  (val) => {
    if (val) {
      loadFormData()
    }
  }
)
</script>
