<!--
  @文件: ButtonFormModal.vue
  @用途: 按钮表单弹窗组件，用于新增和编辑按钮信息
  @描述: 通过 visible 属性控制弹窗显示/隐藏，支持 v-model 双向绑定；根据 record 是否存在自动判断编辑或新增模式；
         编辑模式下自动回填按钮数据，按钮编码(code)不可修改；提交时调用对应的创建/更新 API，成功后通知父组件刷新列表。
  @核心逻辑:
    1. 通过 visible 属性控制弹窗显示/隐藏，支持 v-model 双向绑定
    2. 根据 record 是否存在自动判断为编辑模式或新增模式
    3. 编辑模式下自动回填按钮数据，按钮编码(code)不可修改
    4. 提交时调用对应的创建/更新 API，成功后通知父组件刷新列表
-->
<template>
  <a-modal
    :title="isEdit ? '编辑按钮' : '新增按钮'"
    :open="visible"
    :confirm-loading="loading"
    :width="600"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <a-form ref="formRef" :model="formState" :rules="rules" layout="vertical">
      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="按钮名称" name="name" html-for="btn-name">
            <a-input
              id="btn-name"
              v-model:value="formState.name"
              name="name"
              placeholder="请输入按钮名称"
            />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="按钮编码" name="code" html-for="btn-code">
            <a-input
              id="btn-code"
              v-model:value="formState.code"
              name="code"
              placeholder="请输入按钮编码"
              :disabled="isEdit"
            />
          </a-form-item>
        </a-col>
      </a-row>

      <a-form-item label="所属菜单" name="menu_id" html-for="btn-menu">
        <a-tree-select
          id="btn-menu"
          v-model:value="formState.menu_id"
          name="menu_id"
          :tree-data="menuTreeData"
          :field-names="{ label: 'name', value: 'id', children: 'children' }"
          placeholder="请选择所属菜单"
          tree-default-expand-all
          allow-clear
          :disabled="isEdit"
        />
      </a-form-item>

      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="图标" name="icon" html-for="btn-icon">
            <a-input
              id="btn-icon"
              v-model:value="formState.icon"
              name="icon"
              placeholder="请输入图标名称"
            />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="排序" name="sort" html-for="btn-sort">
            <a-input-number
              id="btn-sort"
              v-model:value="formState.sort"
              name="sort"
              :min="0"
              style="width: 100%"
            />
          </a-form-item>
        </a-col>
      </a-row>

      <a-form-item label="状态" name="status" html-for="btn-status">
        <a-radio-group id="btn-status" v-model:value="formState.status" name="status">
          <a-radio :value="1">正常</a-radio>
          <a-radio :value="0">禁用</a-radio>
        </a-radio-group>
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import type { FormInstance } from 'ant-design-vue'
import { message } from 'ant-design-vue'
import { createMenuButton, updateMenuButton } from '@/api/menu'
import type { ButtonInfo, ButtonForm } from '@/api/button'

interface TreeNode {
  id: number
  name: string
  children?: TreeNode[]
}

interface Props {
  visible: boolean
  record: ButtonInfo | null
  menuTreeData: TreeNode[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'success'): void
}>()

const formRef = ref<FormInstance>()
const loading = ref(false)
const isEdit = computed(() => !!props.record)

const formState = reactive<ButtonForm>({
  name: '',
  code: '',
  menu_id: undefined as unknown as number,
  icon: '',
  sort: 0,
  status: 1
})

const rules = {
  name: [{ required: true, message: '请输入按钮名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入按钮编码', trigger: 'blur' }],
  menu_id: [{ required: true, message: '请选择所属菜单', trigger: 'change' }]
}

const resetForm = () => {
  formState.name = ''
  formState.code = ''
  formState.menu_id = undefined as unknown as number
  formState.icon = ''
  formState.sort = 0
  formState.status = 1
}

const loadFormData = () => {
  if (props.record) {
    formState.name = props.record.name
    formState.code = props.record.code
    formState.menu_id = props.record.menu_id
    formState.icon = props.record.icon || ''
    formState.sort = props.record.sort || 0
    formState.status = props.record.status
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
      await updateMenuButton(props.record.menu_id, props.record.id, {
        name: formState.name,
        code: formState.code,
        icon: formState.icon || '',
        sort: formState.sort ?? 0,
        status: formState.status ?? 1
      })
      message.success('更新成功')
    } else {
      await createMenuButton(formState.menu_id, {
        name: formState.name,
        code: formState.code,
        icon: formState.icon || '',
        sort: formState.sort ?? 0,
        status: formState.status ?? 1
      })
      message.success('创建成功')
    }
    emit('success')
    emit('update:visible', false)
    resetForm()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[ButtonFormModal] handleSubmit failed:', error)
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
