<template>
  <div class="error-page">
    <div class="error-content">
      <div class="error-icon">
        <StopOutlined />
      </div>
      <h1>403</h1>
      <p>抱歉，您没有权限访问该页面</p>
      <a-button type="primary" @click="goBack">返回上一页</a-button>
      <a-button style="margin-left: 12px" @click="goHome">返回首页</a-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { StopOutlined } from '@ant-design/icons-vue'

const router = useRouter()

const goBack = () => {
  router.go(-1)
}

const goHome = () => {
  router.push('/dashboard')
}
</script>

<style lang="less" scoped>
.error-page {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0f2f5;
}

.error-content {
  text-align: center;

  .error-icon {
    font-size: 80px;
    color: #ff4d4f;
    margin-bottom: 24px;
  }

  h1 {
    font-size: 72px;
    font-weight: 700;
    color: #ff4d4f;
    margin: 0 0 16px;
  }

  p {
    font-size: 18px;
    color: #666;
    margin-bottom: 32px;
  }
}
</style>
