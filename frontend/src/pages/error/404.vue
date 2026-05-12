<!--
  @文件: 404.vue
  @用途: 404 页面未找到错误页
  @描述: 当用户访问不存在的路由时展示此页面，提供返回首页的导航按钮。
         页面以问号图标和 404 状态码为核心视觉元素，提示用户页面不存在。
-->
<template>
  <div class="error-page">
    <div class="error-content">
      <!-- 问号图标：表示页面未找到的疑问状态 -->
      <div class="error-icon">
        <QuestionCircleOutlined />
      </div>
      <!-- 404 状态码 -->
      <h1>404</h1>
      <!-- 错误描述文案 -->
      <p>抱歉，您访问的页面不存在</p>
      <!-- 返回首页按钮 -->
      <a-button type="primary" @click="goHome">返回首页</a-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { QuestionCircleOutlined } from '@ant-design/icons-vue'

const router = useRouter()

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

  /* 问号图标：大尺寸蓝色，营造疑问视觉 */
  .error-icon {
    font-size: 80px;
    color: #4073fa;
    margin-bottom: 24px;
  }

  /* 404 状态码：超大字号加粗蓝色 */
  h1 {
    font-size: 72px;
    font-weight: 700;
    color: #4073fa;
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
