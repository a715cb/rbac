<!--
  @文件: ApiFormModal.vue
  @用途: 接口新增/编辑表单弹窗组件
  @描述: 接口（API）新增/编辑表单弹窗组件，通过 v-model:visible 双向绑定控制弹窗显隐，success 事件通知父组件刷新列表
  @核心逻辑:
    1. 根据 props.record 是否存在判断为编辑或新增模式
    2. 编辑模式下，弹窗打开时自动加载接口详情并回填表单
    3. 新增模式下，弹窗打开时重置表单为默认值
    4. 提交时先进行表单校验，校验通过后调用对应的新增/更新接口
    5. 通过 v-model:visible 双向绑定控制弹窗显隐，success 事件通知父组件刷新列表
-->
<template>
  <!-- 接口新增/编辑弹窗，标题根据模式动态切换 -->
  <a-modal
    :title="isEdit ? '编辑接口' : '新增接口'"
    :open="visible"
    :confirm-loading="loading"
    :confirm-disabled="detailLoading"
    :width="600"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <!-- 详情加载中的遮罩层，编辑模式下加载数据时显示 -->
    <a-spin :spinning="detailLoading">
      <a-form ref="formRef" :model="formState" :rules="rules" layout="vertical">
        <!-- 第一行：接口名称 + 接口标识 -->
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

        <!-- 第二行：请求方法 + 接口路径 -->
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="请求方法" name="method" html-for="api-method">
              <a-select
                id="api-method"
                v-model:value="formState.method"
                name="method"
                placeholder="请选择请求方法"
              >
                <!-- 遍历 HTTP_METHODS 常量渲染请求方法选项 -->
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

        <!-- 第三行：所属菜单（树形选择）+ 分组 -->
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="所属菜单" name="menu_id" html-for="api-menu-id">
              <!-- 树形下拉选择器，用于关联接口与菜单节点 -->
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

        <!-- 状态选择：正常 / 禁用 -->
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

/** 组件属性定义 */
interface Props {
  /** 弹窗是否可见 */
  visible: boolean
  /** 当前操作的接口记录，为 null 时表示新增模式 */
  record: ApiInfo | null
}

const props = defineProps<Props>()

/** 组件事件定义 */
const emit = defineEmits<{
  /** 更新弹窗可见状态（支持 v-model:visible） */
  (e: 'update:visible', value: boolean): void
  /** 操作成功后触发，通知父组件刷新数据 */
  (e: 'success'): void
}>()

/** 表单实例引用，用于调用校验和重置方法 */
const formRef = ref<FormInstance>()

/** 提交按钮加载状态 */
const loading = ref(false)

/** 详情数据加载状态，加载期间禁用确认按钮 */
const detailLoading = ref(false)

/** 菜单树数据及获取方法 */
const { menuTreeData, fetchMenuTree } = useMenuTree()

/** 是否为编辑模式：record 存在即为编辑，否则为新增 */
const isEdit = computed(() => !!props.record)

/** 表单数据对象 */
const formState = reactive<ApiForm>({
  name: '',
  code: '',
  method: 'GET',
  path: '',
  menu_id: undefined,
  group: '',
  status: 1
})

/** 表单校验规则 */
const rules = {
  name: [{ required: true, message: '请输入接口名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入接口标识', trigger: 'blur' }],
  method: [{ required: true, message: '请选择请求方法', trigger: 'change' }],
  path: [
    { required: true, message: '请输入接口路径', trigger: 'blur' },
    { pattern: /^\//, message: '路径必须以 / 开头', trigger: 'blur' }
  ]
}

/**
 * 重置表单数据为默认值
 * 将所有字段恢复到初始状态，用于新增模式或提交成功后清理
 */
const resetForm = () => {
  formState.name = ''
  formState.code = ''
  formState.method = 'GET'
  formState.path = ''
  formState.menu_id = undefined
  formState.group = ''
  formState.status = 1
}

/**
 * 加载表单数据
 * 编辑模式下调用接口获取详情并回填表单；新增模式下重置表单为默认值
 * 加载失败时关闭弹窗并提示错误信息
 */
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

/**
 * 提交表单
 * 先进行表单校验，校验通过后根据模式调用新增或更新接口
 * 操作成功后触发 success 事件并关闭弹窗
 */
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
    // 错误由请求拦截器统一处理
  } finally {
    loading.value = false
  }
}

/**
 * 取消操作
 * 关闭弹窗并重置表单校验状态和数据
 */
const handleCancel = () => {
  emit('update:visible', false)
  formRef.value?.resetFields()
  resetForm()
}

/**
 * 监听弹窗可见状态变化
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

/** 组件挂载时预加载菜单树数据，供所属菜单下拉选择使用 */
onMounted(() => {
  fetchMenuTree()
})
</script>
