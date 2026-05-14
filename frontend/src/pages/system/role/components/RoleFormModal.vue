<!--
  @文件: RoleFormModal.vue
  @用途: 角色表单弹窗组件，用于新增和编辑角色信息
  @描述: 通过 visible 属性控制弹窗显示/隐藏，支持 v-model 双向绑定；根据 record 是否存在自动判断编辑或新增模式；
         编辑模式下自动回填角色数据，角色标识(code)不可修改；提交时调用对应的创建/更新 API，成功后通知父组件刷新列表。
  @核心逻辑:
    1. 通过 visible 属性控制弹窗显示/隐藏，支持 v-model 双向绑定
    2. 根据 record 是否存在自动判断为编辑模式或新增模式
    3. 编辑模式下自动回填角色数据，角色标识(code)不可修改
    4. 提交时调用对应的创建/更新 API，成功后通知父组件刷新列表
-->
<template>
  <!-- 角色表单弹窗：标题根据模式动态切换 -->
  <a-modal
    :title="isEdit ? '编辑角色' : '新增角色'"
    :open="visible"
    :confirm-loading="loading"
    :width="600"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <a-form ref="formRef" :model="formState" :rules="rules" layout="vertical">
      <!-- 第一行：角色名称 + 角色标识 -->
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
            <!-- 编辑模式下角色标识不可修改 -->
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

      <!-- 第二行：排序 + 状态 -->
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

      <!-- 备注 -->
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

/** 组件属性接口 */
interface Props {
  /** 弹窗是否可见，支持 v-model 双向绑定 */
  visible: boolean
  /** 当前操作的角色记录，为 null 时表示新增模式 */
  record: RoleInfo | null
}

const props = defineProps<Props>()

/** 组件事件定义 */
const emit = defineEmits<{
  /** 更新 visible 状态，实现弹窗关闭 */
  (e: 'update:visible', value: boolean): void
  /** 操作成功后触发，传递保存后的完整记录，供父组件局部更新 */
  (e: 'success', record: RoleInfo): void
}>()

/** 表单实例引用，用于调用 validate 等方法 */
const formRef = ref<FormInstance>()

/** 提交按钮加载状态 */
const loading = ref(false)

/** 是否为编辑模式：record 存在即为编辑模式 */
const isEdit = computed(() => !!props.record)

/** 表单数据，响应式对象 */
const formState = reactive<RoleForm>({
  name: '',
  code: '',
  sort: 0,
  status: 1,
  remark: ''
})

/** 表单校验规则 */
const rules = {
  name: [{ required: true, message: '请输入角色名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入角色标识', trigger: 'blur' }]
}

/**
 * 重置表单数据为默认值
 * 将所有字段恢复到初始状态
 */
const resetForm = () => {
  formState.name = ''
  formState.code = ''
  formState.sort = 0
  formState.status = 1
  formState.remark = ''
}

/**
 * 加载表单数据
 * 编辑模式下将角色记录回填到表单，新增模式下重置表单
 */
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

/**
 * 提交表单
 * 先进行表单校验，校验通过后根据模式调用创建或更新 API
 * 成功后关闭弹窗并通知父组件刷新数据
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
      await updateRole(props.record.id, formState)
      message.success('更新成功')
      emit('success', { ...formState, id: props.record.id } as RoleInfo)
    } else {
      const res = await createRole(formState)
      message.success('创建成功')
      emit('success', { ...formState, id: res.data.id } as RoleInfo)
    }
    emit('update:visible', false)
    resetForm()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[RoleFormModal] handleSubmit failed:', error)
    // error handled by request interceptor
  } finally {
    loading.value = false
  }
}

/**
 * 取消操作
 * 关闭弹窗并重置表单数据
 */
const handleCancel = () => {
  emit('update:visible', false)
  resetForm()
}

/**
 * 监听弹窗可见性变化
 * 弹窗打开时自动加载表单数据（编辑回填或新增重置）
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
