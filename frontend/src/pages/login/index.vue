<!--
  @文件: login/index.vue
  @用途: 系统登录页面
  @描述: 提供用户身份认证入口，支持用户名/密码登录、图形验证码校验、记住密码功能。
         页面采用左右分栏布局：左侧为系统介绍与特性展示，右侧为登录表单。
         移动端自动隐藏左侧区域，表单占满全屏。
  @核心逻辑:
    - 表单校验通过后调用 login API 进行身份认证
    - 登录成功后将 token 和用户信息存入 store，支持 redirect 参数跳转
    - 记住密码功能通过 localStorage 持久化用户名
    - 验证码组件支持刷新和客户端校验
    - 错误处理区分不同类型（密码错误、账户禁用、网络异常等）
-->
<template>
  <div class="login-container">
    <!-- 左侧：系统介绍与特性展示，移动端隐藏 -->
    <div class="login-left">
      <div class="login-banner">
        <h1>RBAC 权限管理系统</h1>
        <p>高效、灵活、安全的企业级权限管理解决方案</p>
        <!-- 系统特性列表 -->
        <div class="feature-list">
          <div class="feature-item">
            <CheckCircleOutlined class="feature-icon" />
            <span>基于 RBAC 模型的精细化权限控制</span>
          </div>
          <div class="feature-item">
            <CheckCircleOutlined class="feature-icon" />
            <span>支持菜单、按钮、接口三级权限</span>
          </div>
          <div class="feature-item">
            <CheckCircleOutlined class="feature-icon" />
            <span>JWT Token 无状态认证</span>
          </div>
          <div class="feature-item">
            <CheckCircleOutlined class="feature-icon" />
            <span>前后端分离架构</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 右侧：登录表单区域 -->
    <div class="login-right">
      <div class="login-box">
        <!-- 登录头部：Logo + 标题 -->
        <div class="login-header">
          <div class="logo">
            <SettingOutlined class="logo-icon" />
          </div>
          <h1>欢迎登录</h1>
          <p>请输入您的账号信息</p>
        </div>

        <!-- 登录表单：垂直布局，校验规则绑定 -->
        <a-form
          :model="formState"
          :rules="rules"
          layout="vertical"
          @finish="handleLogin"
          @finish-failed="handleLoginFailed"
        >
          <!-- 用户名输入：带用户图标前缀 -->
          <a-form-item
            label="用户名"
            name="username"
            html-for="login-username"
            class="login-form-item"
          >
            <a-input
              id="login-username"
              v-model:value="formState.username"
              name="username"
              placeholder="用户名 / 手机号 / 邮箱"
              size="large"
            >
              <template #prefix>
                <UserOutlined />
              </template>
            </a-input>
          </a-form-item>

          <!-- 密码输入：带锁图标前缀，密码模式隐藏明文 -->
          <a-form-item
            label="密码"
            name="password"
            html-for="login-password"
            class="login-form-item"
          >
            <a-input-password
              id="login-password"
              v-model:value="formState.password"
              name="password"
              placeholder="请输入密码"
              size="large"
            >
              <template #prefix>
                <LockOutlined />
              </template>
            </a-input-password>
          </a-form-item>

          <!-- 验证码输入：集成图形验证码组件 -->
          <a-form-item
            label="验证码"
            name="captcha"
            html-for="login-captcha"
            class="login-form-item"
          >
            <Captcha ref="captchaRef" v-model:captcha="formState.captcha" />
          </a-form-item>

          <!-- 记住密码 & 忘记密码 -->
          <a-form-item class="login-form-item-no-label">
            <div class="form-options">
              <a-checkbox id="login-remember" v-model:checked="formState.remember" name="remember">
                记住密码
              </a-checkbox>
            </div>
          </a-form-item>

          <!-- 登录按钮：防重复提交 -->
          <a-form-item>
            <a-button
              type="primary"
              html-type="submit"
              size="large"
              :loading="loading"
              :disabled="isSubmitting"
              block
              class="login-button"
            >
              <span v-if="!loading">登 录</span>
            </a-button>
          </a-form-item>
        </a-form>

        <!-- 底部：注册引导
        <div class="login-footer">
          <span>还没有账号？</span>
          <a class="register-link" @click.prevent="handleRegister">立即注册</a>
        </div>
        -->
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  UserOutlined,
  LockOutlined,
  SettingOutlined,
  CheckCircleOutlined
} from '@ant-design/icons-vue'
import { message } from 'ant-design-vue'
import { useUserStore } from '@/stores/user'
import { login } from '@/api/auth'
import { Captcha } from '@/components'
import { AppConfig } from '@/config/app'
import { StorageManager } from '@/utils/storage'

const router = useRouter()
const userStore = useUserStore()

/** 验证码组件实例引用，用于调用 validate() 和 refresh() 方法 */
const captchaRef = ref<InstanceType<typeof Captcha> | null>(null)

/** 登录请求加载状态，控制按钮 loading 效果 */
const loading = ref(false)

/** 防重复提交标志，loading 期间禁用按钮 */
const isSubmitting = ref(false)

/** 登录表单数据 */
const formState = reactive({
  username: '', // 用户名
  password: '', // 密码
  captcha: '', // 图形验证码
  remember: false // 是否记住密码
})

/** 表单校验规则 */
const rules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { min: 3, max: 20, message: '用户名长度为 3-20 个字符', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码长度至少为 6 位', trigger: 'blur' }
  ],
  captcha: [
    { required: true, message: '请输入验证码', trigger: 'blur' },
    { len: 4, message: '验证码长度为 4 位', trigger: 'blur' }
  ]
}

/**
 * 登录处理
 * @description 表单校验通过后的登录流程：
 *   1. 客户端验证码校验
 *   2. 调用 login API 进行身份认证
 *   3. 存储 token 和用户信息到 store
 *   4. 处理"记住密码"逻辑（localStorage 持久化用户名）
 *   5. 跳转至 redirect 参数指定页面或默认首页
 *   6. 错误处理：区分密码错误、账户禁用、网络异常等不同场景
 */
const handleLogin = async () => {
  // 客户端验证码校验
  if (captchaRef.value && !captchaRef.value.validate()) {
    message.error('验证码不正确，请重新输入')
    captchaRef.value.refresh()
    return
  }

  // 二次校验验证码长度
  if (formState.captcha.length !== 4) {
    message.error('请输入 4 位验证码')
    return
  }

  loading.value = true
  isSubmitting.value = true

  try {
    const response = await login({
      username: formState.username,
      password: formState.password
    })

    const { access_token, refresh_token, expires_in, user_info } = response.data

    // 存储 token（含过期时间管理）
    userStore.setTokenWithExpiry(access_token, refresh_token, expires_in)
    // 存储用户基本信息（角色和权限在路由守卫中动态获取）
    userStore.setUserInfo({
      ...user_info,
      roles: [],
      permissions: []
    })

    message.success('登录成功')

    // 处理"记住密码"：勾选时持久化用户名，取消时清除
    if (formState.remember) {
      StorageManager.setItem('local', AppConfig.rememberUsernameKey, formState.username)
    } else {
      StorageManager.removeItem('local', AppConfig.rememberUsernameKey)
    }

    // 跳转：优先使用 redirect 查询参数，否则默认跳转仪表盘
    const redirect = (router.currentRoute.value.query.redirect as string) || '/dashboard'
    router.push(redirect)
  } catch (error: unknown) {
    const err = error as { message?: string }
    const errorMessage = err.message || '登录失败，请检查账号信息'

    // 根据错误信息类型展示不同的提示
    if (errorMessage.includes('用户名或密码错误')) {
      message.error('用户名或密码错误，请重新输入')
    } else if (errorMessage.includes('账户已被禁用')) {
      message.error('账户已被禁用，请联系管理员')
    } else if (errorMessage.includes('账户已锁定') || errorMessage.includes('锁定')) {
      message.error(errorMessage)
    } else if (
      errorMessage.includes('网络') ||
      errorMessage.includes('超时') ||
      errorMessage.includes('连接')
    ) {
      message.error('网络连接异常，请检查网络后重试')
    } else if (errorMessage.includes('服务器') || errorMessage.includes('500')) {
      message.error('服务器繁忙，请稍后重试')
    } else {
      message.error(errorMessage)
    }

    // 登录失败后刷新验证码
    captchaRef.value?.refresh()
  } finally {
    loading.value = false
    isSubmitting.value = false
  }
}

/**
 * 表单校验失败回调
 * @description 提示用户检查表单填写
 */
const handleLoginFailed = () => {
  message.warning('请检查表单填写是否正确')
}

/**
 * 加载记住的用户名
 * @description 从 localStorage 读取上次记住的用户名，
 *              如果存在则自动填充并勾选"记住密码"
 */
const loadRememberedUsername = () => {
  const remembered = StorageManager.getItem('local', AppConfig.rememberUsernameKey)
  if (remembered) {
    formState.username = remembered
    formState.remember = true
  }
}

/** 页面挂载时加载记住的用户名 */
onMounted(() => {
  loadRememberedUsername()
})
</script>

<style lang="less" scoped>
/* 登录容器：全屏左右分栏布局 */
.login-container {
  height: 100vh;
  display: flex;
  background: #fff;
}

/* 左侧系统介绍区域：渐变蓝色背景，移动端隐藏 */
.login-left {
  flex: 1;
  background: linear-gradient(135deg, #4073fa 0%, #3360d8 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px;

  @media (max-width: 768px) {
    display: none;
  }
}

/* 系统介绍内容 */
.login-banner {
  max-width: 480px;
  color: #fff;

  h1 {
    font-size: 42px;
    font-weight: 700;
    margin-bottom: 16px;
    line-height: 1.3;
    letter-spacing: 1px;
  }

  > p {
    font-size: 18px;
    opacity: 0.9;
    margin-bottom: 48px;
    line-height: 1.6;
  }
}

/* 特性列表：纵向排列，每项带勾选图标 */
.feature-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 16px;

  .feature-icon {
    font-size: 20px;
    color: #52c41a;
  }
}

/* 右侧登录表单区域：固定宽度，移动端全屏 */
.login-right {
  width: 480px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 40px;
  background: #fff;

  @media (max-width: 768px) {
    width: 100%;
    padding: 40px 24px;
  }
}

/* 登录框：限制最大宽度，居中 */
.login-box {
  width: 100%;
  max-width: 360px;
}

/* 登录头部：Logo + 标题 + 副标题 */
.login-header {
  text-align: center;
  margin-bottom: 40px;

  /* Logo 容器：渐变蓝色圆角方块 */
  .logo {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #4073fa 0%, #3360d8 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    box-shadow: 0 4px 12px rgba(64, 115, 250, 0.3);

    .logo-icon {
      font-size: 32px;
      color: #fff;
    }
  }

  h1 {
    font-size: 28px;
    font-weight: 600;
    color: @text-color;
    margin-bottom: 8px;
  }

  p {
    color: @text-color-secondary;
    font-size: 14px;
  }
}

/* 表单选项行：记住密码 + 忘记密码，两端对齐 */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
}

/* 登录表单项：视觉隐藏标签（保留无障碍访问） */
.login-form-item {
  :deep(.ant-form-item-label) {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }

  :deep(.ant-form-item-control) {
    flex: 1;
    max-width: 100%;
  }
}

/* 无标签表单项：完全隐藏标签 */
.login-form-item-no-label {
  :deep(.ant-form-item-label) {
    display: none;
  }
}

/* 底部注册引导 */
.login-footer {
  margin-top: 24px;
  text-align: center;
  color: @text-color-secondary;
  font-size: 14px;

  .register-link {
    color: @primary-color;
    margin-left: 4px;
    transition: color 0.3s;

    &:hover {
      color: @primary-color-hover;
    }
  }
}

/* 输入框外层容器样式 - 保持边框和布局，去除背景 */
:deep(.ant-input-affix-wrapper) {
  border-radius: @border-radius-base;
  padding: 8px 12px;
  background: none; /* 完全去除背景 */
  border: 1px solid #d9d9d9;

  &:hover {
    border-color: @primary-color-hover;
  }

  &:focus,
  &.ant-input-affix-wrapper-focused {
    border-color: @primary-color;
    box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
  }

  .ant-input-prefix {
    margin-right: 8px;
    color: @text-color-secondary;
  }

  /* 输入框本身 - 完全透明背景 */
  .ant-input {
    background: transparent; /* 透明背景 */
    background-color: transparent; /* 兼容不同浏览器 */
    background-image: none; /* 去除可能的背景图片 */
  }
}

/* 覆盖浏览器自动填充时的默认背景色 - 兼容Chrome、Edge、Safari */
:deep(.ant-input-affix-wrapper) .ant-input:-webkit-autofill,
:deep(.ant-input-affix-wrapper) .ant-input:-webkit-autofill:hover,
:deep(.ant-input-affix-wrapper) .ant-input:-webkit-autofill:focus,
:deep(.ant-input-affix-wrapper) .ant-input:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0 1000px transparent inset !important;
  box-shadow: 0 0 0 1000px transparent inset !important;
  -webkit-text-fill-color: @text-color !important;
  caret-color: @text-color !important; /* 确保光标颜色一致 */
  transition: background-color 5000s ease-in-out 0s;
}

/* Edge浏览器特殊处理 - 使用appearance属性覆盖自动填充样式 */
:deep(.ant-input-affix-wrapper) .ant-input:autofill,
:deep(.ant-input-affix-wrapper) .ant-input:autofill:hover,
:deep(.ant-input-affix-wrapper) .ant-input:autofill:focus,
:deep(.ant-input-affix-wrapper) .ant-input:autofill:active {
  appearance: none !important;
  -webkit-appearance: none !important;
  background: transparent !important;
  background-color: transparent !important;
  background-image: none !important;
  -webkit-text-fill-color: @text-color !important;
  caret-color: @text-color !important;
}

/* 密码输入框特殊处理 - 确保内部输入框背景透明 */
:deep(.ant-input-password) {
  background: none; /* 外层容器无背景 */

  .ant-input {
    background: transparent;
    background-color: transparent;
    background-image: none;
  }
}

/* 密码输入框自动填充背景覆盖 - WebKit内核浏览器 */
:deep(.ant-input-password) .ant-input:-webkit-autofill,
:deep(.ant-input-password) .ant-input:-webkit-autofill:hover,
:deep(.ant-input-password) .ant-input:-webkit-autofill:focus,
:deep(.ant-input-password) .ant-input:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0 1000px transparent inset !important;
  box-shadow: 0 0 0 1000px transparent inset !important;
  -webkit-text-fill-color: @text-color !important;
  caret-color: @text-color !important;
  transition: background-color 5000s ease-in-out 0s;
}

/* Edge浏览器密码输入框自动填充特殊处理 */
:deep(.ant-input-password) .ant-input:autofill,
:deep(.ant-input-password) .ant-input:autofill:hover,
:deep(.ant-input-password) .ant-input:autofill:focus,
:deep(.ant-input-password) .ant-input:autofill:active {
  appearance: none !important;
  -webkit-appearance: none !important;
  background: transparent !important;
  background-color: transparent !important;
  background-image: none !important;
  -webkit-text-fill-color: @text-color !important;
  caret-color: @text-color !important;
}

/* 登录按钮：加大高度和字号，带阴影效果 */
.login-button {
  height: 44px;
  font-size: 16px;
  border-radius: @border-radius-base;
  box-shadow: 0 2px 8px rgba(24, 144, 255, 0.3);

  &:hover {
    box-shadow: 0 4px 12px rgba(24, 144, 255, 0.4);
  }
}

/* 忘记密码链接 */
.forgot-link {
  color: @text-color-secondary;
  transition: color 0.3s;
  cursor: pointer;

  &:hover {
    color: @primary-color;
  }
}
</style>
