<template>
  <a-modal
    :title="isEdit ? '编辑菜单' : '新增菜单'"
    :open="visible"
    :confirm-loading="loading"
    :width="700"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <a-form ref="formRef" :model="formState" :rules="rules" layout="vertical">
      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="菜单类型" name="menu_type" html-for="menu-type">
            <a-radio-group id="menu-type" v-model:value="formState.menu_type" name="menu_type">
              <a-radio :value="1">目录</a-radio>
              <a-radio :value="2">菜单</a-radio>
            </a-radio-group>
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="上级菜单" name="parent_id" html-for="menu-parent-id">
            <a-tree-select
              id="menu-parent-id"
              v-model:value="formState.parent_id"
              name="parent_id"
              :tree-data="menuTreeData"
              placeholder="请选择上级菜单"
              allow-clear
              tree-default-expand-all
              :field-names="{ children: 'children', label: 'name', value: 'id' }"
            />
          </a-form-item>
        </a-col>
      </a-row>

      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="菜单名称" name="name" html-for="menu-name">
            <a-input
              id="menu-name"
              v-model:value="formState.name"
              name="name"
              placeholder="请输入菜单名称"
            />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="菜单标识" name="code" html-for="menu-code">
            <a-input
              id="menu-code"
              v-model:value="formState.code"
              name="code"
              placeholder="请输入菜单标识"
            />
          </a-form-item>
        </a-col>
      </a-row>

      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="路由路径" name="path" html-for="menu-path">
            <a-input
              id="menu-path"
              v-model:value="formState.path"
              name="path"
              placeholder="请输入路由路径"
            />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="组件路径" name="component" html-for="menu-component">
            <a-input
              id="menu-component"
              v-model:value="formState.component"
              name="component"
              placeholder="请输入组件路径"
            />
          </a-form-item>
        </a-col>
      </a-row>

      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="图标" name="icon" html-for="menu-icon">
            <a-input
              id="menu-icon"
              v-model:value="formState.icon"
              name="icon"
              placeholder="请输入图标名称"
            />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="排序" name="sort" html-for="menu-sort">
            <a-input-number
              id="menu-sort"
              v-model:value="formState.sort"
              name="sort"
              :min="0"
              style="width: 100%"
            />
          </a-form-item>
        </a-col>
      </a-row>

      <a-row :gutter="16">
        <a-col :span="8">
          <a-form-item label="显示状态" name="visible" html-for="menu-visible">
            <a-radio-group id="menu-visible" v-model:value="formState.visible" name="visible">
              <a-radio :value="1">显示</a-radio>
              <a-radio :value="0">隐藏</a-radio>
            </a-radio-group>
          </a-form-item>
        </a-col>
        <a-col :span="8">
          <a-form-item label="缓存状态" name="keep_alive" html-for="menu-keep-alive">
            <a-radio-group
              id="menu-keep-alive"
              v-model:value="formState.keep_alive"
              name="keep_alive"
            >
              <a-radio :value="1">缓存</a-radio>
              <a-radio :value="0">不缓存</a-radio>
            </a-radio-group>
          </a-form-item>
        </a-col>
        <a-col :span="8">
          <a-form-item label="菜单状态" name="status" html-for="menu-status">
            <a-radio-group id="menu-status" v-model:value="formState.status" name="status">
              <a-radio :value="1">正常</a-radio>
              <a-radio :value="0">禁用</a-radio>
            </a-radio-group>
          </a-form-item>
        </a-col>
      </a-row>

      <a-form-item label="备注" name="remark" html-for="menu-remark">
        <a-textarea
          id="menu-remark"
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
import { ref, reactive, watch, computed, onMounted } from 'vue'
import type { FormInstance } from 'ant-design-vue'
import { message } from 'ant-design-vue'
import { createMenu, updateMenu } from '@/api/menu'
import type { MenuInfo, MenuForm } from '@/api/menu'
import { useMenuTree } from '@/composables/useTreeData'

interface Props {
  visible: boolean
  record: MenuInfo | null
  parentId?: number
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'success'): void
}>()

const formRef = ref<FormInstance>()
const loading = ref(false)
const { menuTreeData, fetchMenuTree } = useMenuTree()

const isEdit = computed(() => !!props.record)

const formState = reactive<MenuForm>({
  name: '',
  code: '',
  menu_type: 1,
  parent_id: 0,
  path: '',
  icon: '',
  component: '',
  sort: 0,
  visible: 1,
  keep_alive: 1,
  always_show: 1,
  breadcrumb: 1,
  active_menu: '',
  is_external: 0,
  is_frame: 1,
  status: 1,
  remark: ''
})

const rules = {
  name: [{ required: true, message: '请输入菜单名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入菜单标识', trigger: 'blur' }],
  menu_type: [{ required: true, message: '请选择菜单类型', trigger: 'change' }]
}

const resetForm = () => {
  formState.name = ''
  formState.code = ''
  formState.menu_type = 1
  formState.parent_id = 0
  formState.path = ''
  formState.icon = ''
  formState.component = ''
  formState.sort = 0
  formState.visible = 1
  formState.keep_alive = 1
  formState.always_show = 1
  formState.breadcrumb = 1
  formState.active_menu = ''
  formState.is_external = 0
  formState.is_frame = 1
  formState.status = 1
  formState.remark = ''
}

const loadFormData = () => {
  if (props.record) {
    formState.name = props.record.name
    formState.code = props.record.code
    formState.menu_type = props.record.menu_type
    formState.parent_id = props.record.parent_id || 0
    formState.path = props.record.path || ''
    formState.icon = props.record.icon || ''
    formState.component = props.record.component || ''
    formState.sort = props.record.sort || 0
    formState.visible = props.record.visible
    formState.keep_alive = props.record.keep_alive
    formState.always_show = props.record.always_show
    formState.breadcrumb = props.record.breadcrumb
    formState.active_menu = props.record.active_menu || ''
    formState.is_external = props.record.is_external
    formState.is_frame = props.record.is_frame
    formState.status = props.record.status
    formState.remark = props.record.remark || ''
  } else if (props.parentId) {
    formState.parent_id = props.parentId
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
      await updateMenu(props.record.id, formState)
      message.success('更新成功')
    } else {
      await createMenu(formState)
      message.success('创建成功')
    }
    emit('success')
    emit('update:visible', false)
    resetForm()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[MenuFormModal] handleSubmit failed:', error)
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

onMounted(() => {
  fetchMenuTree()
})
</script>
