<!--
  @文件: UserFormModal.vue
  @用途: 用户表单弹窗组件，用于新增和编辑用户信息
  @描述: 根据传入的 record 是否为空自动切换新增/编辑模式，编辑模式下用户名不可修改、密码字段隐藏。
         依赖组件：DictSelect（性别字段，字典编码 user_gender）、DictRadio（状态字段，字典编码 user_status）。
         后端 createUser/updateUser 接口已内置 role_ids 处理逻辑（事务内原子操作），前端无需单独调用 assignRoles 接口。
  @核心逻辑:
    1. 弹窗打开 → 加载角色列表和部门树 → nextTick 后填充表单数据
    2. 用户填写表单 → 点击确定 → 表单验证 → 调用创建/更新接口
    3. 操作成功 → emit('success') 通知父页面刷新列表 → 关闭弹窗
    4. 弹窗关闭时自动销毁内部 DOM（destroy-on-close），无需手动清理状态
-->
<template>
  <!-- 用户表单弹窗
    - destroy-on-close：关闭时销毁内部 DOM，避免表单验证状态和字典组件实例残留
    - isEdit 控制标题和部分字段的显隐/禁用状态
  -->
  <a-modal
    :title="isEdit ? '编辑用户' : '新增用户'"
    :open="visible"
    :confirm-loading="loading"
    :width="600"
    destroy-on-close
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <a-form ref="formRef" :model="formState" :rules="rules" layout="vertical">
      <!-- 第一行：用户名 + 昵称 -->
      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="用户名" name="username" html-for="user-username">
            <!-- 编辑模式下用户名不可修改；autocomplete="off" 防止浏览器自动填充缓存凭据 -->
            <a-input
              id="user-username"
              v-model:value="formState.username"
              name="username"
              placeholder="请输入用户名"
              :disabled="isEdit"
              autocomplete="off"
            />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="昵称" name="nickname" html-for="user-nickname">
            <a-input
              id="user-nickname"
              v-model:value="formState.nickname"
              name="nickname"
              placeholder="请输入昵称"
            />
          </a-form-item>
        </a-col>
      </a-row>

      <!-- 第二行：邮箱 + 手机号 -->
      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="邮箱" name="email" html-for="user-email">
            <a-input
              id="user-email"
              v-model:value="formState.email"
              name="email"
              placeholder="请输入邮箱"
            />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="手机号" name="mobile" html-for="user-mobile">
            <a-input
              id="user-mobile"
              v-model:value="formState.mobile"
              name="mobile"
              placeholder="请输入手机号"
            />
          </a-form-item>
        </a-col>
      </a-row>

      <!-- 第三行：密码 + 确认密码（仅新增时显示） -->
      <a-row v-if="!isEdit" :gutter="16">
        <a-col :span="12">
          <a-form-item label="密码" name="password" html-for="user-password">
            <a-input-password
              id="user-password"
              v-model:value="formState.password"
              name="password"
              placeholder="请输入密码"
              autocomplete="new-password"
            />
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="确认密码" name="confirmPassword" html-for="user-confirm-password">
            <a-input-password
              id="user-confirm-password"
              v-model:value="formState.confirmPassword"
              name="confirmPassword"
              placeholder="请再次输入密码"
              autocomplete="new-password"
            />
          </a-form-item>
        </a-col>
      </a-row>

      <!-- 第四行：性别 -->
      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="性别" name="gender" html-for="user-gender">
            <DictSelect
              id="user-gender"
              v-model:value="formState.gender"
              name="gender"
              dict-code="user_gender"
              placeholder="请选择性别"
              width="100%"
            />
          </a-form-item>
        </a-col>
      </a-row>

      <!-- 第四行：部门多选 + 状态 -->
      <a-row :gutter="16">
        <a-col :span="12">
          <a-form-item label="部门" name="depts" html-for="user-depts">
            <div class="dept-select-wrapper">
              <a-tree-select
                id="user-depts"
                :tree-data="deptTreeData"
                :field-names="{ label: 'name', value: 'id', children: 'children' }"
                placeholder="选择部门添加"
                allow-clear
                tree-default-expand-all
                style="width: 100%"
                @change="handleAddDept"
              />
              <div v-if="formState.depts && formState.depts.length > 0" class="dept-tags">
                <div
                  v-for="(dept, index) in formState.depts"
                  :key="dept.dept_id"
                  class="dept-tag-item"
                >
                  <a-tag
                    :color="dept.is_primary ? 'blue' : 'default'"
                    closable
                    @close="handleRemoveDept(index)"
                  >
                    {{ dept.dept_name }}
                  </a-tag>
                  <a-button
                    v-if="!dept.is_primary"
                    type="link"
                    size="small"
                    @click="handleSetPrimary(index)"
                  >
                    设为主部门
                  </a-button>
                  <span v-else class="primary-label">主部门</span>
                </div>
              </div>
            </div>
          </a-form-item>
        </a-col>
        <a-col :span="12">
          <!-- 状态：通过 DictRadio 字典组件动态获取选项（user_status） -->
          <a-form-item label="状态" name="status" html-for="user-status">
            <DictRadio
              id="user-status"
              v-model:value="formState.status"
              name="status"
              dict-code="user_status"
            />
          </a-form-item>
        </a-col>
      </a-row>

      <!-- 角色选择：多选模式，选项从后端角色列表接口获取 -->
      <a-form-item label="角色" name="role_ids" html-for="user-role-ids">
        <a-select
          id="user-role-ids"
          v-model:value="formState.role_ids"
          name="role_ids"
          mode="multiple"
          placeholder="请选择角色"
          allow-clear
        >
          <a-select-option v-for="role in roleList" :key="role.id" :value="role.id">
            {{ role.name }}
          </a-select-option>
        </a-select>
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed, nextTick } from 'vue'
import type { FormInstance } from 'ant-design-vue'
import { message } from 'ant-design-vue'
import { createUser, updateUser } from '@/api/user'
import type { UserInfo, UserForm, UserDeptItem } from '@/api/user'
import { getRoleList } from '@/api/role'
import { useDeptTree } from '@/composables/useTreeData'
import type { TreeNode } from '@/composables/useTreeData'
import { DictSelect, DictRadio } from '@/components/Dict'
import { getPasswordRules, getConfirmPasswordRules } from '@/utils/validators'

/**
 * 组件属性定义
 * @property visible - 弹窗是否可见（支持 v-model:visible 双向绑定）
 * @property record - 当前操作的用户记录，null 表示新增模式，非 null 表示编辑模式
 */
interface Props {
  visible: boolean
  record: UserInfo | null
}

const props = defineProps<Props>()

/**
 * 组件事件定义
 * @event update:visible - 更新弹窗可见状态（v-model 双向绑定）
 * @event success - 表单提交成功后触发，传递保存后的完整记录，供父组件局部更新
 */
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'success', record: UserInfo): void
}>()

/** Ant Design Form 实例引用，用于调用 validate() 和 resetFields() */
const formRef = ref<FormInstance>()
/** 提交按钮加载状态，防止重复提交 */
const loading = ref(false)
/** 角色下拉选项列表，从后端角色接口获取 */
const roleList = ref<Array<{ id: number; name: string }>>([])
/** 部门树数据，来自共享缓存组合式函数，供 a-tree-select 使用 */
const { deptTreeData, fetchDeptTree: fetchDepts } = useDeptTree()

/** 是否为编辑模式（record 非空即为编辑模式） */
const isEdit = computed(() => !!props.record)

/**
 * 表单数据状态
 * @property username - 用户名（编辑时不可修改）
 * @property password - 密码（仅新增时必填，编辑时隐藏）
 * @property nickname - 昵称
 * @property email - 邮箱
 * @property mobile - 手机号
 * @property gender - 性别（0-未知，1-男，2-女），由字典组件管理
 * @property dept_id - 所属部门 ID
 * @property status - 状态（1-正常，0-禁用），由字典组件管理
 * @property role_ids - 角色ID列表，后端在创建/更新时事务内原子处理
 */
const formState = reactive<UserForm>({
  username: '',
  password: '',
  confirmPassword: '',
  nickname: '',
  email: '',
  mobile: '',
  gender: 0,
  dept_id: undefined,
  depts: [],
  status: 1,
  role_ids: []
})

/**
 * 表单验证规则（computed 动态计算，编辑模式下密码规则为空）
 * - 用户名：必填 + 长度3-20 + 格式（字母开头，仅含字母数字下划线）
 * - 密码：新增时必填 + 长度6-20，编辑时无校验
 * - 昵称：长度上限20
 * - 邮箱：格式校验（非必填，填写时需符合邮箱格式）
 * - 手机号：格式校验（非必填，填写时需符合11位手机号格式）
 */
const rules = computed(() => ({
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { min: 3, max: 20, message: '用户名长度为 3-20 个字符', trigger: 'blur' },
    {
      pattern: /^[a-zA-Z][a-zA-Z0-9_]*$/,
      message: '用户名需以字母开头，仅含字母数字下划线',
      trigger: 'blur'
    }
  ],
  password: isEdit.value ? [] : getPasswordRules(),
  confirmPassword: isEdit.value ? [] : getConfirmPasswordRules(() => formState.password),
  nickname: [{ max: 20, message: '昵称最长 20 个字符', trigger: 'blur' }],
  email: [{ type: 'email', message: '邮箱格式不正确', trigger: 'blur' }],
  mobile: [{ pattern: /^1[3-9]\d{9}$/, message: '手机号格式不正确', trigger: 'blur' }]
}))

/**
 * 加载表单数据
 * 编辑模式：从 props.record 填充表单字段，使用 ?? 空值合并避免 falsy 值误判
 * 新增模式：先显式重置 formState 所有字段为默认值（因为 destroy-on-close 仅销毁 DOM，
 *   formState 作为 reactive 对象仍保留上次编辑的值，resetFields() 会以当前 formState
 *   值作为"初始值"进行重置，导致新增模式下残留编辑数据），再调用 resetFields() 清除验证状态
 */
const loadFormData = () => {
  if (props.record) {
    formState.username = props.record.username
    formState.nickname = props.record.nickname ?? ''
    formState.email = props.record.email ?? ''
    formState.mobile = props.record.mobile ?? ''
    formState.gender = props.record.gender ?? 0
    formState.dept_id = props.record.dept_id ? Number(props.record.dept_id) : undefined
    formState.depts = props.record.depts?.map((d) => ({ ...d, dept_id: Number(d.dept_id) })) ?? []
    formState.status = props.record.status
    formState.role_ids = props.record.roles?.map((r) => Number(r.id)) ?? []
  } else {
    formState.username = ''
    formState.password = ''
    formState.confirmPassword = ''
    formState.nickname = ''
    formState.email = ''
    formState.mobile = ''
    formState.gender = 0
    formState.dept_id = undefined
    formState.depts = []
    formState.status = 1
    formState.role_ids = []
    formRef.value?.clearValidate()
  }
}

/**
 * 获取角色列表
 * 请求后端角色接口获取全部角色（limit=100），用于角色多选下拉选项。
 * 每次弹窗打开时调用，确保选项数据最新。
 */
const fetchRoles = async () => {
  try {
    const res = await getRoleList({ page: 1, limit: 100 })
    roleList.value = res.data.list.map((r) => ({ ...r, id: Number(r.id) }))
  } catch (error) {
    if (import.meta.env.DEV) console.error('[UserFormModal] Fetch roles failed:', error)
  }
}

/**
 * 表单提交处理
 * 实现思路：
 * 1. 先调用 formRef.validate() 进行表单验证，验证失败直接返回
 * 2. 根据 isEdit 模式调用不同的 API：
 *    - 编辑模式：调用 updateUser，传入用户 ID 和表单数据（含 role_ids）
 *    - 新增模式：调用 createUser，传入表单数据（含 role_ids）
 * 3. 后端在事务内原子处理用户信息和角色分配，前端无需单独调用 assignRoles
 * 4. 成功后 emit('success') 通知父页面刷新列表，emit('update:visible', false) 关闭弹窗
 * 5. 失败时不关闭弹窗，用户可修正后重试
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
      await updateUser(props.record.id, {
        username: props.record.username,
        nickname: formState.nickname,
        email: formState.email,
        mobile: formState.mobile,
        gender: formState.gender,
        dept_id: formState.dept_id,
        depts: formState.depts,
        status: formState.status,
        role_ids: formState.role_ids
      })
      message.success('更新成功')
      emit('success', {
        ...props.record,
        ...formState,
        id: props.record.id
      } as UserInfo)
    } else {
      const res = await createUser(formState)
      message.success('创建成功')
      emit('success', {
        ...formState,
        id: res.data.id
      } as UserInfo)
    }
    emit('update:visible', false)
  } catch (error) {
    if (import.meta.env.DEV) console.error('[UserFormModal] Submit form failed:', error)
  } finally {
    loading.value = false
  }
}

const handleAddDept = (value: number) => {
  if (!value) return
  if (formState.depts?.some((d) => d.dept_id === value)) {
    message.warning('该部门已添加')
    return
  }
  const deptName = findDeptName(deptTreeData.value, value)
  const newDept: UserDeptItem = {
    dept_id: value,
    dept_name: deptName,
    is_primary: formState.depts && formState.depts.length === 0 ? 1 : 0,
    sort: formState.depts?.length ?? 0
  }
  if (!formState.depts) formState.depts = []
  formState.depts.push(newDept)
  if (newDept.is_primary) {
    formState.dept_id = value
  }
}

const handleRemoveDept = (index: number) => {
  if (!formState.depts) return
  const removed = formState.depts[index]
  formState.depts.splice(index, 1)
  if (removed.is_primary && formState.depts.length > 0) {
    formState.depts[0].is_primary = 1
    formState.dept_id = formState.depts[0].dept_id
  } else if (formState.depts.length === 0) {
    formState.dept_id = undefined
  }
}

const handleSetPrimary = (index: number) => {
  if (!formState.depts) return
  formState.depts.forEach((d, i) => {
    d.is_primary = i === index ? 1 : 0
  })
  formState.dept_id = formState.depts[index].dept_id
}

/** 递归在部门树中查找节点名称，用于添加部门时显示名称 */
const findDeptName = (
  tree: TreeNode[],
  id: number
): string => {
  for (const node of tree) {
    if (node.id === id) return node.name
    if (node.children) {
      const name = findDeptName(node.children, id)
      if (name) return name
    }
  }
  return ''
}

/** 取消按钮处理：关闭弹窗（destroy-on-close 会自动清理内部状态） */
const handleCancel = () => {
  emit('update:visible', false)
}

/**
 * 监听弹窗可见性变化
 * 弹窗打开时执行以下操作：
 * 1. fetchRoles()：加载最新角色列表（确保新增角色可选）
 * 2. fetchDepts()：加载最新部门树（确保新增部门可选）
 * 3. nextTick(() => loadFormData())：等待 DOM 渲染完成后填充表单数据，
 *    确保 formRef 已就绪可调用 resetFields()
 */
watch(
  () => props.visible,
  async (val) => {
    if (val) {
      await Promise.all([fetchRoles(), fetchDepts()])
      nextTick(() => loadFormData())
    }
  }
)
</script>

<style lang="less" scoped>
.dept-select-wrapper {
  .dept-tags {
    margin-top: 8px;
  }

  .dept-tag-item {
    display: inline-flex;
    align-items: center;
    margin-right: 4px;
    margin-bottom: 4px;
  }

  .primary-label {
    color: #1677ff;
    font-size: 12px;
    margin-left: 4px;
  }
}
</style>
