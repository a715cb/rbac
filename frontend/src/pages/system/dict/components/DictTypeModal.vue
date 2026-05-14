<!--
  @文件: DictTypeModal.vue
  @用途: 字典类型新增/编辑弹窗组件
  @描述: 提供字典类型的新增和编辑功能，以模态框形式展示表单，编辑模式下字典编码字段禁止修改
  @核心逻辑:
    1. 根据是否传入 record 判断当前为编辑或新增模式
    2. 弹窗打开时自动加载表单数据（编辑回填 / 新增重置）
    3. 提交时进行表单校验，调用对应 API 完成创建或更新
    4. 编辑模式下字典编码字段禁止修改
-->
<template>
  <!-- 字典类型新增/编辑模态框 -->
  <a-modal
    :title="isEdit ? '编辑字典类型' : '新增字典类型'"
    :open="visible"
    :confirm-loading="loading"
    :width="500"
    :destroy-on-close="true"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <!-- 字典类型表单，垂直布局 -->
    <a-form ref="formRef" :model="formState" :rules="rules" layout="vertical">
      <!-- 字典名称输入项 -->
      <a-form-item label="字典名称" name="name" html-for="dict-type-name">
        <a-input
          id="dict-type-name"
          v-model:value="formState.name"
          name="name"
          placeholder="请输入字典名称"
        />
      </a-form-item>

      <!-- 字典编码输入项，编辑模式下禁用 -->
      <a-form-item label="字典编码" name="code" html-for="dict-type-code">
        <a-input
          id="dict-type-code"
          v-model:value="formState.code"
          name="code"
          placeholder="请输入字典编码"
          :disabled="isEdit"
        />
      </a-form-item>

      <!-- 数据类型与排序并排布局 -->
      <a-row :gutter="16">
        <a-col :span="12">
          <!-- 数据类型选择项：字符串/数字/日期/时间 -->
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
          <!-- 排序数值输入项，最小值为 0 -->
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

      <!-- 状态单选项：正常/禁用 -->
      <a-form-item label="状态" name="status" html-for="dict-type-status">
        <a-radio-group id="dict-type-status" v-model:value="formState.status" name="status">
          <a-radio :value="1">正常</a-radio>
          <a-radio :value="0">禁用</a-radio>
        </a-radio-group>
      </a-form-item>

      <!-- 备注文本域 -->
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

/** 组件 Props 接口定义 */
interface Props {
  /** 弹窗是否可见 */
  visible: boolean
  /** 当前操作的字典类型记录，为 null 时表示新增模式 */
  record: DictTypeInfo | null
}

const props = defineProps<Props>()

/** 组件事件定义 */
const emit = defineEmits<{
  /** 更新弹窗可见状态（支持 v-model:visible 双向绑定） */
  (e: 'update:visible', value: boolean): void
  /** 操作成功后触发，传递保存后的完整记录，供父组件局部更新 */
  (e: 'success', record: DictTypeInfo): void
}>()

/** 表单实例引用，用于调用表单校验方法 */
const formRef = ref<FormInstance>()

/** 提交按钮加载状态 */
const loading = ref(false)

/** 是否为编辑模式：record 存在即为编辑，否则为新增 */
const isEdit = computed(() => !!props.record)

/** 表单数据响应式对象 */
const formState = reactive<DictTypeForm>({
  name: '',
  code: '',
  type: 'string',
  status: 1,
  sort: 0,
  remark: ''
})

/** 表单校验规则 */
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

/**
 * 重置表单数据为默认值
 * 将所有字段恢复到初始状态，用于新增模式或提交/取消后清理
 */
const resetForm = () => {
  formState.name = ''
  formState.code = ''
  formState.type = 'string'
  formState.status = 1
  formState.sort = 0
  formState.remark = ''
}

/**
 * 加载表单数据
 * 编辑模式时将 record 数据回填到表单，新增模式时重置表单
 */
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

/**
 * 处理表单提交
 * 先进行表单校验，校验通过后根据模式调用创建或更新 API
 * @returns {Promise<void>}
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
      await updateDictType(props.record.id, formState)
      message.success('更新成功')
      emit('success', { ...formState, id: props.record.id } as DictTypeInfo)
    } else {
      const res = await createDictType(formState)
      message.success('创建成功')
      emit('success', { ...formState, id: res.data.id } as DictTypeInfo)
    }
    emit('update:visible', false)
    resetForm()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[DictTypeModal] handleSubmit failed:', error)
    // 错误由请求拦截器统一处理
  } finally {
    loading.value = false
  }
}

/**
 * 处理取消操作
 * 关闭弹窗并重置表单数据
 */
const handleCancel = () => {
  emit('update:visible', false)
  resetForm()
}

/**
 * 监听弹窗可见状态变化
 * 弹窗打开时自动加载表单数据，确保每次打开弹窗时数据状态正确
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
