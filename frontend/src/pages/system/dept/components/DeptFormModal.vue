<template>
  <a-modal
    :title="isEdit ? '修改部门' : '添加部门'"
    :open="visible"
    :confirm-loading="loading"
    :width="560"
    :destroy-on-close="true"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <a-form
      ref="formRef"
      :model="formState"
      :rules="rules"
      layout="horizontal"
      :label-col="{ flex: '90px' }"
      :wrapper-col="{ flex: 1 }"
    >
      <div class="form-body">
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

        <a-form-item label="部门名称" name="name" html-for="dept-name">
          <a-input
            id="dept-name"
            v-model:value="formState.name"
            name="name"
            placeholder="请输入部门名称"
          />
        </a-form-item>

        <a-form-item label="负责人" name="leader" html-for="dept-leader">
          <a-input
            id="dept-leader"
            v-model:value="formState.leader"
            name="leader"
            placeholder="请选择部门负责人"
          />
        </a-form-item>

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

interface TreeDataItem {
  id: number
  name: string
  children: TreeDataItem[]
}

interface Props {
  visible: boolean
  record: DeptInfo | null
  parentId?: number
  treeData?: DeptInfo[]
}

const props = withDefaults(defineProps<Props>(), {
  parentId: undefined,
  treeData: () => []
})

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'success'): void
}>()

const formRef = ref<FormInstance>()
const loading = ref(false)
const isEdit = computed(() => !!props.record)

const formState = reactive<DeptForm>({
  parent_id: 0,
  name: '',
  code: '',
  leader: '',
  sort: undefined
})

const rules = {
  name: [{ required: true, message: '请输入部门名称', trigger: 'blur' }],
  parent_id: [{ required: true, message: '请选择上级部门', trigger: 'change' }]
}

const deptTreeData = ref<TreeDataItem[]>([])

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

watch(
  () => props.treeData,
  (value) => {
    deptTreeData.value = buildTreeData(value)
  },
  { immediate: true }
)

const resetForm = () => {
  formState.parent_id = 0
  formState.name = ''
  formState.code = ''
  formState.leader = ''
  formState.sort = undefined
}

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

<style lang="less" scoped>
.form-body {
  padding: 20px;
}
</style>
