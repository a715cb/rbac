<!--
  @文件: RoleDataScopeModal.vue
  @用途: 角色数据权限配置弹窗，用于设置角色可访问的数据范围
  @描述: 支持五种数据范围：全部数据、本部门数据、本部门及下级数据、仅本人数据、自定义；
         当选择"自定义"时展示部门树供用户勾选指定部门；弹窗打开时自动加载部门树数据，关闭时重置表单状态。
  @核心逻辑:
    1. 支持五种数据范围：全部数据、本部门数据、本部门及下级数据、仅本人数据、自定义
    2. 当选择"自定义"时，展示部门树供用户勾选指定部门
    3. 弹窗打开时自动加载部门树数据，提交时调用接口保存配置
    4. 关闭弹窗时重置表单状态
-->
<template>
  <!-- 数据权限配置弹窗 -->
  <a-modal
    :title="`${roleName} - 数据权限`"
    :open="visible"
    :confirm-loading="loading"
    :width="600"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <a-form ref="formRef" :model="formState" :rules="rules" layout="vertical">
      <!-- 数据范围单选组：1-全部数据 2-本部门数据 3-本部门及下级数据 4-仅本人数据 5-自定义 -->
      <a-form-item label="数据范围" name="data_scope" html-for="role-data-scope">
        <a-radio-group id="role-data-scope" v-model:value="formState.data_scope" name="data_scope">
          <a-radio :value="1">全部数据</a-radio>
          <a-radio :value="2">本部门数据</a-radio>
          <a-radio :value="3">本部门及下级数据</a-radio>
          <a-radio :value="4">仅本人数据</a-radio>
          <a-radio :value="5">自定义</a-radio>
        </a-radio-group>
      </a-form-item>

      <!-- 自定义数据范围：选择"自定义"时展示部门树，供用户勾选可访问的部门 -->
      <a-form-item
        v-if="formState.data_scope === 5"
        label="选择部门"
        name="data_scope_dept_ids"
        html-for="role-data-scope-dept"
      >
        <a-tree
          id="role-data-scope-dept"
          v-model:checked-keys="checkedDeptKeys"
          checkable
          :tree-data="deptTreeData"
          :field-names="{ children: 'children', title: 'name', key: 'id' }"
          default-expand-all
        />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import type { FormInstance } from 'ant-design-vue'
import { message } from 'ant-design-vue'
import { setRoleDataScope } from '@/api/role'
import { useDeptTree } from '@/composables/useTreeData'

/** 组件属性接口 */
interface Props {
  /** 弹窗是否可见，支持 v-model 双向绑定 */
  visible: boolean
  /** 当前操作的角色 ID，用于接口请求 */
  roleId?: number
  /** 当前角色名称，用于弹窗标题展示 */
  roleName?: string
}

const props = defineProps<Props>()

/** 组件事件定义 */
const emit = defineEmits<{
  /** 更新弹窗可见状态，配合 v-model:visible 使用 */
  (e: 'update:visible', value: boolean): void
  /** 数据权限保存成功后触发，通知父组件刷新数据 */
  (e: 'success'): void
}>()

/** 表单实例引用，用于调用 validate 等方法 */
const formRef = ref<FormInstance>()

/** 提交按钮加载状态 */
const loading = ref(false)

/** 部门树数据及加载方法，来自 useDeptTree 组合式函数 */
const { deptTreeData, fetchDeptTree } = useDeptTree()

/** 自定义数据范围时，用户勾选的部门 ID 列表 */
const checkedDeptKeys = ref<(number | string)[]>([])

/** 表单状态：data_scope-数据范围类型，data_scope_dept_ids-自定义部门ID(逗号分隔) */
const formState = reactive({
  data_scope: 1,
  data_scope_dept_ids: ''
})

/** 表单校验规则 */
const rules = {
  data_scope: [{ required: true, message: '请选择数据范围', trigger: 'change' }]
}

/**
 * 提交数据权限配置
 * @description 校验表单后调用接口保存角色的数据权限配置，成功后关闭弹窗并通知父组件
 */
const handleSubmit = async () => {
  // 表单校验
  try {
    await formRef.value?.validate()
  } catch {
    return
  }

  // 角色ID有效性校验
  if (!props.roleId) {
    message.error('角色 ID 无效')
    return
  }

  // 自定义数据范围时，必须选择至少一个部门
  if (formState.data_scope === 5 && checkedDeptKeys.value.length === 0) {
    message.error('自定义数据权限必须选择部门')
    return
  }

  loading.value = true
  try {
    // 自定义范围时传递部门ID，其他范围传递空字符串
    const deptIds = formState.data_scope === 5 ? checkedDeptKeys.value.join(',') : ''
    await setRoleDataScope(props.roleId, formState.data_scope, deptIds)
    message.success('数据权限配置成功')
    emit('success')
    emit('update:visible', false)
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[RoleDataScopeModal] handleSubmit failed:', error)
    // 错误由请求拦截器统一处理
  } finally {
    loading.value = false
  }
}

/**
 * 取消/关闭弹窗
 * @description 关闭弹窗并重置表单状态，防止残留数据影响下次打开
 */
const handleCancel = () => {
  emit('update:visible', false)
  formState.data_scope = 1
  checkedDeptKeys.value = []
}

/**
 * 监听弹窗可见状态
 * @description 弹窗打开时自动加载部门树数据，确保数据最新
 */
watch(
  () => props.visible,
  (val) => {
    if (val) {
      fetchDeptTree()
    }
  }
)
</script>
