<!--
  @文件: MenuFormModal.vue
  @用途: 菜单表单弹窗组件，用于新增/编辑菜单
  @描述: 支持新增和编辑两种模式，包含菜单类型、上级菜单、路由信息、状态等字段，
         编辑模式通过 record 属性区分，新增子菜单通过 parentId 属性指定父级
  @核心逻辑:
    1. 根据 record 是否存在判断新增/编辑模式，动态加载表单数据
    2. 上级菜单使用树形选择器，数据来源于 useMenuTree 组合式函数
    3. 提交时校验表单，根据模式调用 createMenu 或 updateMenu 接口
    4. 弹窗关闭时重置表单数据，打开时根据模式回填或重置
-->
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
      <!-- 第一行：菜单类型 + 上级菜单 -->
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
            <!-- 树形选择器：从菜单树数据中选择上级菜单 -->
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

      <!-- 第二行：菜单名称 + 菜单标识 -->
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

      <!-- 第三行：路由路径 + 组件路径 -->
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

      <!-- 第四行：图标 + 排序 -->
      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="图标" name="icon" html-for="menu-icon">
            <s-icon-select v-model="formState.icon" />
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

      <!-- 第五行：显示状态 + 缓存状态 + 菜单状态（三列等分） -->
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

      <!-- 备注 -->
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
import { SIconSelect } from '@/components/Icon'

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

const formRef = ref<FormInstance>() // 表单实例引用，用于校验和重置
const loading = ref(false) // 提交加载状态
const { menuTreeData, fetchMenuTree } = useMenuTree() // 菜单树数据，用于上级菜单选择器

/** 是否为编辑模式（有传入记录则为编辑，否则为新增） */
const isEdit = computed(() => !!props.record)

/** 表单数据对象，包含菜单所有字段 */
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

/** 重置表单数据为默认值 */
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

/** 根据模式加载表单数据：编辑模式回填记录、新增子菜单模式设置父级 ID、新增顶级菜单模式重置表单 */
const loadFormData = () => {
  if (props.record) {
    // 编辑模式：将记录数据回填到表单
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
    // 新增子菜单模式：仅设置父级 ID，其余保持默认
    formState.parent_id = props.parentId
  } else {
    // 新增顶级菜单模式：重置表单
    resetForm()
  }
}

/** 提交表单：校验通过后调用新增/更新接口 */
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
  } finally {
    loading.value = false
  }
}

/** 取消操作：关闭弹窗并重置表单 */
const handleCancel = () => {
  emit('update:visible', false)
  resetForm()
}

/** 监听弹窗可见性变化，打开时加载表单数据 */
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
