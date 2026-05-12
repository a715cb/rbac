<!--
  @文件: ResetPasswordModal.vue
  @用途: 管理员通过弹窗为指定用户重置登录密码
  @描述: 在用户管理页面中，管理员通过此弹窗为指定用户重置登录密码。
    弹窗包含"新密码"和"确认密码"两个输入框，提交时校验密码长度和一致性。
    父组件通过 visible prop 控制弹窗显示/隐藏，弹窗打开时自动清空表单。
  @核心逻辑:
    1. 父组件通过 visible prop 控制弹窗显示/隐藏
    2. 弹窗打开时自动清空表单（watch 监听 visible 变化）
    3. 填写密码 → 表单校验（必填 + 最少6位 + 两次一致）→ 调用重置密码接口
    4. 重置成功后通知父组件刷新数据并关闭弹窗
-->
<template>
  <!--
    a-modal 配置说明：
    - open：绑定弹窗显示状态
    - confirm-loading：确认按钮加载状态（防止重复提交）
    - width：弹窗宽度 400px
  -->
  <a-modal
    title="重置密码"
    :open="visible"
    :confirm-loading="loading"
    :width="400"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <!-- 表单布局：垂直排列（label 在输入框上方） -->
    <a-form ref="formRef" :model="formState" :rules="rules" layout="vertical">
      <!-- 新密码输入框：使用密码模式隐藏输入内容 -->
      <a-form-item label="新密码" name="password" html-for="reset-password">
        <a-input-password
          id="reset-password"
          v-model:value="formState.password"
          name="password"
          placeholder="请输入新密码"
        />
      </a-form-item>

      <!-- 确认密码输入框：需与新密码一致 -->
      <a-form-item label="确认密码" name="confirmPassword" html-for="reset-confirm-password">
        <a-input-password
          id="reset-confirm-password"
          v-model:value="formState.confirmPassword"
          name="confirmPassword"
          placeholder="请再次输入新密码"
        />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import type { FormInstance } from 'ant-design-vue'
import { message } from 'ant-design-vue'
import { resetPassword } from '@/api/user'
import { getPasswordRules, getConfirmPasswordRules } from '@/utils/validators'

/**
 * 组件 Props 定义
 * @prop visible - 控制弹窗显示/隐藏，支持 v-model:visible 双向绑定
 * @prop userId - 待重置密码的用户ID，由父组件传入
 */
interface Props {
  visible: boolean
  userId?: number
}

const props = defineProps<Props>()

/**
 * 组件对外事件定义
 * @event update:visible - 更新弹窗显示状态（v-model:visible 支持）
 * @event success - 密码重置成功后触发
 */
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'success'): void
}>()

/** 表单实例引用，用于调用 validate() 方法触发表单校验 */
const formRef = ref<FormInstance>()

/** 确认按钮加载状态，提交时置为 true 防止重复点击 */
const loading = ref(false)

/** 表单数据：新密码 + 确认密码 */
const formState = reactive({
  password: '',
  confirmPassword: ''
})

const rules = {
  password: getPasswordRules(),
  confirmPassword: getConfirmPasswordRules(() => formState.password)
}

/** 重置表单数据，清空密码输入框 */
const resetForm = () => {
  formState.password = ''
  formState.confirmPassword = ''
}

/**
 * 处理确认提交
 *
 * 执行流程：
 * 1. 触发表单校验，校验不通过则中止提交
 * 2. 校验 userId 是否有效（防止异常情况下提交无效请求）
 * 3. 调用重置密码接口
 * 4. 成功后：提示成功 → 通知父组件刷新 → 关闭弹窗 → 清空表单
 */
const handleSubmit = async () => {
  // 触发表单校验
  try {
    await formRef.value?.validate()
  } catch {
    return
  }

  // 校验用户ID有效性
  if (!props.userId) {
    message.error('用户 ID 无效')
    return
  }

  // 调用重置密码接口
  loading.value = true
  try {
    await resetPassword(props.userId, formState.password)
    // 成功后处理
    message.success('密码重置成功')
    emit('success')
    emit('update:visible', false)
    resetForm()
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[ResetPasswordModal] handleSubmit failed:', error)
  } finally {
    loading.value = false
  }
}

/**
 * 处理取消操作
 *
 * 关闭弹窗并清空表单数据，避免下次打开时残留上次输入。
 */
const handleCancel = () => {
  emit('update:visible', false)
  resetForm()
}

/**
 * 监听弹窗显示状态变化
 *
 * 当弹窗打开时（visible 变为 true），自动清空表单数据，
 * 确保每次打开弹窗都是干净的初始状态。
 */
watch(
  () => props.visible,
  (val) => {
    if (val) {
      resetForm()
    }
  }
)
</script>
