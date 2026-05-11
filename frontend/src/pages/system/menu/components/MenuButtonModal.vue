<template>
  <a-modal
    title="按钮管理"
    :open="visible"
    :width="700"
    :footer="null"
    :destroy-on-close="true"
    @cancel="handleCancel"
  >
    <div class="button-toolbar">
      <a-button type="primary" size="small" @click="handleAdd">
        <PlusOutlined />
        新增按钮
      </a-button>
    </div>

    <a-table
      :columns="columns"
      :data-source="buttonList"
      :loading="loading"
      row-key="id"
      size="small"
      :pagination="false"
    >
      <template #bodyCell="{ text, column, record }">
        <template v-if="column.dataIndex === 'status'">
          <a-tag :color="record.status === 1 ? 'green' : 'red'">
            {{ record.status === 1 ? '正常' : '禁用' }}
          </a-tag>
        </template>
        <template v-else-if="column.dataIndex === 'action'">
          <a-space>
            <a-button type="link" size="small" @click="handleEdit(record)">
              <EditOutlined />
              编辑
            </a-button>
            <a-popconfirm title="确定要删除该按钮吗？" @confirm="handleDelete(record)">
              <a-button type="link" danger size="small">
                <DeleteOutlined />
                删除
              </a-button>
            </a-popconfirm>
          </a-space>
        </template>
        <template v-else>
          {{ text }}
        </template>
      </template>
    </a-table>

    <a-modal
      :title="isEdit ? '编辑按钮' : '新增按钮'"
      :open="formVisible"
      :confirm-loading="formLoading"
      :width="500"
      :destroy-on-close="true"
      @ok="handleFormSubmit"
      @cancel="handleFormCancel"
    >
      <a-form ref="formRef" :model="formState" :rules="formRules" layout="vertical">
        <a-form-item label="按钮名称" name="name" html-for="menu-btn-name">
          <a-input
            id="menu-btn-name"
            v-model:value="formState.name"
            name="name"
            placeholder="请输入按钮名称"
          />
        </a-form-item>
        <a-form-item label="按钮编码" name="code" html-for="menu-btn-code">
          <a-input
            id="menu-btn-code"
            v-model:value="formState.code"
            name="code"
            placeholder="请输入按钮编码"
          />
        </a-form-item>
        <a-form-item label="图标" name="icon" html-for="menu-btn-icon">
          <a-input
            id="menu-btn-icon"
            v-model:value="formState.icon"
            name="icon"
            placeholder="请输入图标名称"
          />
        </a-form-item>
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="排序" name="sort" html-for="menu-btn-sort">
              <a-input-number
                id="menu-btn-sort"
                v-model:value="formState.sort"
                name="sort"
                :min="0"
                style="width: 100%"
              />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="状态" name="status" html-for="menu-btn-status">
              <a-radio-group id="menu-btn-status" v-model:value="formState.status" name="status">
                <a-radio :value="1">正常</a-radio>
                <a-radio :value="0">禁用</a-radio>
              </a-radio-group>
            </a-form-item>
          </a-col>
        </a-row>
      </a-form>
    </a-modal>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import type { FormInstance } from 'ant-design-vue'
import { message } from 'ant-design-vue'
import { PlusOutlined, EditOutlined, DeleteOutlined } from '@ant-design/icons-vue'
import { getMenuButtons, createMenuButton, updateMenuButton, deleteMenuButton } from '@/api/menu'
import type { MenuButton } from '@/api/menu'

interface Props {
  visible: boolean
  menuId?: number
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'success'): void
}>()

const loading = ref(false)
const buttonList = ref<MenuButton[]>([])
const formVisible = ref(false)
const formLoading = ref(false)
const formRef = ref<FormInstance>()
const currentButton = ref<MenuButton | null>(null)

const isEdit = ref(false)

const formState = reactive({
  name: '',
  code: '',
  icon: '',
  sort: 0,
  status: 1
})

const formRules = {
  name: [{ required: true, message: '请输入按钮名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入按钮编码', trigger: 'blur' }]
}

const columns = [
  { title: '按钮名称', dataIndex: 'name', key: 'name' },
  { title: '按钮编码', dataIndex: 'code', key: 'code' },
  { title: '图标', dataIndex: 'icon', key: 'icon' },
  { title: '排序', dataIndex: 'sort', key: 'sort', width: 80, align: 'center' },
  { title: '状态', key: 'status', width: 80, align: 'center' },
  { title: '操作', key: 'action', width: 150 }
]

const fetchButtons = async () => {
  if (!props.menuId) return
  loading.value = true
  try {
    const res = await getMenuButtons(props.menuId)
    buttonList.value = res.data
  } catch (error) {
    if (import.meta.env.DEV) console.error('[MenuButtonModal] Fetch buttons failed:', error)
    // error handled by request interceptor
  } finally {
    loading.value = false
  }
}

const handleAdd = () => {
  isEdit.value = false
  currentButton.value = null
  resetForm()
  formVisible.value = true
}

const handleEdit = (record: MenuButton) => {
  isEdit.value = true
  currentButton.value = record
  formState.name = record.name
  formState.code = record.code
  formState.icon = record.icon || ''
  formState.sort = record.sort || 0
  formState.status = record.status
  formVisible.value = true
}

const handleDelete = async (record: MenuButton) => {
  if (!props.menuId) return
  try {
    await deleteMenuButton(props.menuId, record.id)
    message.success('删除成功')
    fetchButtons()
    emit('success')
  } catch (error) {
    if (import.meta.env.DEV) console.error('[MenuButtonModal] Delete button failed:', error)
    // error handled by request interceptor
  }
}

const resetForm = () => {
  formState.name = ''
  formState.code = ''
  formState.icon = ''
  formState.sort = 0
  formState.status = 1
}

const handleFormSubmit = async () => {
  try {
    await formRef.value?.validate()
  } catch {
    return
  }

  if (!props.menuId) {
    message.error('菜单 ID 无效')
    return
  }

  formLoading.value = true
  try {
    if (isEdit.value && currentButton.value) {
      await updateMenuButton(props.menuId, currentButton.value.id, formState)
      message.success('更新成功')
    } else {
      await createMenuButton(props.menuId, formState)
      message.success('创建成功')
    }
    formVisible.value = false
    resetForm()
    fetchButtons()
    emit('success')
  } catch (error) {
    if (import.meta.env.DEV) console.error('[MenuButtonModal] Submit form failed:', error)
    // error handled by request interceptor
  } finally {
    formLoading.value = false
  }
}

const handleFormCancel = () => {
  formVisible.value = false
  resetForm()
}

const handleCancel = () => {
  emit('update:visible', false)
}

watch(
  () => props.visible,
  (val) => {
    if (val && props.menuId) {
      fetchButtons()
    }
  },
  { immediate: true }
)
</script>

<style lang="less" scoped>
.button-toolbar {
  margin-bottom: 16px;
}
</style>
