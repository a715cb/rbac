<!--
  @文件: 403.vue
  @用途: 403 禁止访问错误页
  @描述: 当用户无权限访问目标页面时展示此页面，提供返回上一页和返回首页两个导航按钮。
         页面以禁止图标和 403 状态码为核心视觉元素，使用红色强调权限被拒绝的警示感。
-->
<template>
  <div class="error-page">
    <div class="error-content">
      <!-- 禁止图标：表示访问被拒绝 -->
      <div class="error-icon">
        <StopOutlined />
      </div>
      <!-- 403 状态码 -->
      <h1>403</h1>
      <!-- 错误描述文案 -->
      <p>抱歉，您没有权限访问该页面</p>
      <!-- 导航按钮：返回上一页 + 返回首页 -->
      <a-button type="primary" @click="goBack">返回上一页</a-button>
      <a-button style="margin-left: 12px" @click="goHome">返回首页</a-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { StopOutlined } from '@ant-design/icons-vue'

const router = useRouter()

/**
 * 返回上一页
 * @description 调用路由回退，返回浏览器历史中的上一页
 */
const goBack = () => {
  router.go(-1)
}

/**
 * 返回首页
 * @description 导航至仪表盘页面（/dashboard）
 */
const goHome = () => {
  router.push('/dashboard')
}
</script>

<style lang="less" scoped>
/* 错误页面容器：全屏居中布局，浅灰背景 */
.error-page {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0f2f5;
}

.error-content {
  text-align: center;

  /* 禁止图标：大尺寸红色，强调访问被拒绝 */
  .error-icon {
    font-size: 80px;
    color: #ff4d4f;
    margin-bottom: 24px;
  }

  /* 403 状态码：超大字号加粗红色 */
  h1 {
    font-size: 72px;
    font-weight: 700;
    color: #ff4d4f;
    margin: 0 0 16px;
  }

  /* 错误描述文案 */
  p {
    font-size: 18px;
    color: #666;
    margin-bottom: 32px;
  }
}
</style>
