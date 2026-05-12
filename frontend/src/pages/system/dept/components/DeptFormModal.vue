<!--
  @文件: DeptFormModal.vue
  @用途: 部门新增/编辑表单弹窗组件
  @描述: 提供部门信息的新增和编辑功能，以弹窗形式展示表单。支持选择上级部门、
         填写部门名称、负责人和排序。编辑模式下自动回填已有数据，新增模式下
         支持指定上级部门。
  @核心逻辑:
    - 通过 record 属性区分新增/编辑模式
    - 编辑时自动加载已有部门数据到表单
    - 使用 a-tree-select 展示部门树形结构供选择上级部门
    - 表单校验通过后调用对应 API 提交数据
-->
<template>
  <!-- 部门表单弹窗：根据编辑状态动态显示标题 -->
  <a-modal
    :title="isEdit ? '修改部门' : '添加部门'"
    :open="visible"
    :confirm-loading="loading"
    :width="560"
    :destroy-on-close="true"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <!-- 部门信息表单：水平布局，标签宽度固定90px -->
    <a-form
      ref="formRef"
      :model="formState"
      :rules="rules"
      layout="horizontal"
      :label-col="{ flex: '90px' }"
      :wrapper-col="{ flex: 1 }"
    >
      <div class="form-body">
        <!-- 上级部门选择：树形下拉，支持清空，默认展开全部节点 -->
        <a-form-item label="上级部门" name="parent_id" html-for="dept-parent-id">
          <a-tree-select
            id="dept-parent-id"
            v-model:value="formState.parent_id"
            name="parent_id"
            :tree-data="deptTreeData"
            placeholder="请选择上级部门"
            allow-clear
            tree-default-expand-all
            :field-names="{ children: 'children', label: 'name', value: 'id' }"
          />
        </a-form-item>

        <!-- 部门名称输入 -->
        <a-form-item label="部门名称" name="name" html-for="dept-name">
          <a-input
            id="dept-name"
            v-model:value="formState.name"
            name="name"
            placeholder="请输入部门名称"
          />
        </a-form-item>

        <!-- 负责人输入 -->
        <a-form-item label="负责人" name="leader" html-for="dept-leader">
          <a-input
            id="dept-leader"
            v-model:value="formState.leader"
            name="leader"
            placeholder="请选择部门负责人"
          />
        </a-form-item>

        <!-- 排序输入：数字输入框，最大值9999 -->
        <a-form-item label="排序" name="sort" html-for="dept-sort">
          <a-input-number
            id="dept-sort"
            v-model:value="formState.sort"
            name="sort"
            :max="9999"
            placeholder="请输入排序"
            style="width: 100%"
          />
        </a-form-item>
      </div>
    </a-form>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import type { FormInstance } from 'ant-design-vue'
import { message } from 'ant-design-vue'
import { createDept, updateDept } from '@/api/dept'
import type { DeptInfo, DeptForm } from '@/api/dept'

/** 树形选择器数据项结构 */
interface TreeDataItem {
  id: number
  name: string
  children: TreeDataItem[]
}

/** 组件属性定义 */
interface Props {
  visible: boolean       // 弹窗是否可见，支持 v-model:visible 双向绑定
  record: DeptInfo | null  // 编辑时传入的部门记录，null 表示新增模式
  parentId?: number      // 新增子部门时指定的上级部门ID
  treeData?: DeptInfo[]  // 部门树形数据，用于构建上级部门选择器
}

const props = withDefaults(defineProps<Props>(), {
  parentId: undefined,
  treeData: () => []
})

/** 组件事件定义 */
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void  // 更新弹窗可见状态
  (e: 'success'): void                          // 表单提交成功后触发，通知父组件刷新数据
}>()

/** 表单实例引用，用于调用 validate 方法 */
const formRef = ref<FormInstance>()

/** 提交加载状态：防止重复提交 */
const loading = ref(false)

/** 是否为编辑模式：根据 record 是否存在判断 */
const isEdit = computed(() => !!props.record)

/** 表单数据：部门表单字段 */
const formState = reactive<DeptForm>({
  parent_id: 0,       // 上级部门ID，0 表示顶级部门
  name: '',           // 部门名称
  code: '',           // 部门编码
  leader: '',         // 负责人
  sort: undefined     // 排序值，数字类型
})

/** 表单校验规则 */
const rules = {
  name: [{ required: true, message: '请输入部门名称', trigger: 'blur' }],
  parent_id: [{ required: true, message: '请选择上级部门', trigger: 'change' }]
}

/** 上级部门树形选择器数据 */
const deptTreeData = ref<TreeDataItem[]>([])

/**
 * 构建部门树形选择器数据
 * @param data - 原始部门列表数据
 * @returns 树形结构数据，根节点为"顶级部门"（id=0）
 * @description 将扁平部门数据转换为树形结构，并在顶层添加"顶级部门"虚拟根节点，
 *              用于表示无上级部门的情况
 */
const buildTreeData = (data: DeptInfo[]): TreeDataItem[] => {
  const root: TreeDataItem = { id: 0, name: '顶级部门', children: [] }
  const convert = (items: DeptInfo[]): TreeDataItem[] => {
    return items.map((item) => ({
      id: item.id,
      name: item.name,
      children: item.children ? convert(item.children) : []
    }))
  }
  root.children = convert(data)
  return [root]
}

/**
 * 监听部门树形数据变化
 * @param value - 最新的部门树形数据
 * @description 当父组件传入的 treeData 变化时，重新构建上级部门选择器数据
 */
watch(
  () => props.treeData,
  (value) => {
    deptTreeData.value = buildTreeData(value)
  },
  { immediate: true }
)

/**
 * 重置表单数据
 * @description 将所有表单字段恢复为初始默认值
 */
const resetForm = () => {
  formState.parent_id = 0
  formState.name = ''
  formState.code = ''
  formState.leader = ''
  formState.sort = undefined
}

/**
 * 加载表单数据
 * @description 根据组件属性状态填充表单：
 *   - 编辑模式：用 record 数据回填表单
 *   - 新增子部门模式：设置 parentId 为上级部门
 *   - 新增顶级部门模式：重置表单为默认值
 */
const loadFormData = () => {
  if (props.record) {
    formState.parent_id = props.record.parent_id || 0
    formState.name = props.record.name
    formState.code = props.record.code || ''
    formState.leader = props.record.leader || ''
    formState.sort = props.record.sort ?? undefined
  } else if (props.parentId) {
    resetForm()
    formState.parent_id = props.parentId
  } else {
    resetForm()
  }
}

/**
 * 表单提交处理
 * @description 先进行表单校验，校验通过后根据编辑/新增模式调用对应 API，
 *              成功后触发 success 事件并关闭弹窗
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
      await updateDept(props.record.id, formState)
      message.success('更新成功')
    } else {
      await createDept(formState)
      message.success('创建成功')
    }
    emit('success')
    emit('update:visible', false)
    resetForm()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[DeptFormModal] handleSubmit failed:', error)
  } finally {
    loading.value = false
  }
}

/**
 * 弹窗关闭处理
 * @description 关闭弹窗并重置表单数据
 */
const handleCancel = () => {
  emit('update:visible', false)
  resetForm()
}

/**
 * 监听弹窗可见状态变化
 * @param val - 弹窗是否可见
 * @description 弹窗打开时加载表单数据（回填或初始化）
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

<style lang="less" scoped>
.form-body {
  padding: 20px;
}
</style>
