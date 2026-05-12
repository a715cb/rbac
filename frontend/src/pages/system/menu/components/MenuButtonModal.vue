<!-- 菜单按钮管理弹窗组件：管理某个菜单下的按钮（权限按钮），支持新增、编辑、删除按钮 -->
<template>
  <!-- 外层弹窗：按钮列表 -->
  <a-modal
    title="按钮管理"
    :open="visible"
    :width="700"
    :footer="null"
    :destroy-on-close="true"
    @cancel="handleCancel"
  >
    <!-- 工具栏：新增按钮 -->
    <div class="button-toolbar">
      <a-button type="primary" size="small" @click="handleAdd">
        <PlusOutlined />
        新增按钮
      </a-button>
    </div>

    <!-- 按钮列表表格 -->
    <a-table
      :columns="columns"
      :data-source="buttonList"
      :loading="loading"
      row-key="id"
      size="small"
      :pagination="false"
    >
      <template #bodyCell="{ text, column, record }">
        <!-- 状态列：使用标签展示正常/禁用 -->
        <template v-if="column.dataIndex === 'status'">
          <a-tag :color="record.status === 1 ? 'green' : 'red'">
            {{ record.status === 1 ? '正常' : '禁用' }}
          </a-tag>
        </template>
        <!-- 操作列：编辑 + 删除（带确认弹窗） -->
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
        <!-- 其他列：原样输出文本 -->
        <template v-else>
          {{ text }}
        </template>
      </template>
    </a-table>

    <!-- 内层弹窗：按钮表单（新增/编辑） -->
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
        <!-- 排序 + 状态 -->
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

// ==================== 组件属性与事件 ====================

interface Props {
  visible: boolean
  menuId?: number
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'success'): void
}>()

// ==================== 列表相关状态 ====================

const loading = ref(false) // 列表加载状态
const buttonList = ref<MenuButton[]>([]) // 按钮列表数据

// ==================== 表单相关状态 ====================

const formVisible = ref(false) // 表单弹窗可见性
const formLoading = ref(false) // 表单提交加载状态
const formRef = ref<FormInstance>() // 表单实例引用
const currentButton = ref<MenuButton | null>(null) // 当前编辑的按钮记录

/** 是否为编辑模式 */
const isEdit = ref(false)

/** 按钮表单数据 */
const formState = reactive({
  name: '',
  code: '',
  icon: '',
  sort: 0,
  status: 1
})

/** 表单校验规则：按钮名称和编码为必填项 */
const formRules = {
  name: [{ required: true, message: '请输入按钮名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入按钮编码', trigger: 'blur' }]
}

// ==================== 表格列配置 ====================

const columns = [
  { title: '按钮名称', dataIndex: 'name', key: 'name' },
  { title: '按钮编码', dataIndex: 'code', key: 'code' },
  { title: '图标', dataIndex: 'icon', key: 'icon' },
  { title: '排序', dataIndex: 'sort', key: 'sort', width: 80, align: 'center' },
  { title: '状态', key: 'status', width: 80, align: 'center' },
  { title: '操作', key: 'action', width: 150 }
]

// ==================== 数据请求 ====================

/** 获取当前菜单下的按钮列表 */
const fetchButtons = async () => {
  if (!props.menuId) return
  loading.value = true
  try {
    const res = await getMenuButtons(props.menuId)
    buttonList.value = res.data
  } catch (error) {
    if (import.meta.env.DEV) console.error('[MenuButtonModal] Fetch buttons failed:', error)
  } finally {
    loading.value = false
  }
}

// ==================== 列表操作 ====================

/** 新增按钮：打开表单弹窗（新增模式） */
const handleAdd = () => {
  isEdit.value = false
  currentButton.value = null
  resetForm()
  formVisible.value = true
}

/** 编辑按钮：回填数据并打开表单弹窗（编辑模式） */
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

/** 删除按钮：调用删除接口后刷新列表并通知父组件 */
const handleDelete = async (record: MenuButton) => {
  if (!props.menuId) return
  try {
    await deleteMenuButton(props.menuId, record.id)
    message.success('删除成功')
    fetchButtons()
    emit('success')
  } catch (error) {
    if (import.meta.env.DEV) console.error('[MenuButtonModal] Delete button failed:', error)
  }
}

// ==================== 表单操作 ====================

/** 重置表单数据为默认值 */
const resetForm = () => {
  formState.name = ''
  formState.code = ''
  formState.icon = ''
  formState.sort = 0
  formState.status = 1
}

/** 提交表单：校验通过后调用新增/更新接口 */
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
  } finally {
    formLoading.value = false
  }
}

/** 取消表单：关闭表单弹窗并重置 */
const handleFormCancel = () => {
  formVisible.value = false
  resetForm()
}

/** 取消外层弹窗 */
const handleCancel = () => {
  emit('update:visible', false)
}

// ==================== 侦听器 ====================

/** 监听弹窗可见性变化，打开时加载按钮列表 */
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
/* 工具栏底部间距 */
.button-toolbar {
  margin-bottom: 16px;
}
</style>
