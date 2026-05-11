<template>
  <div class="error-page">
    <div class="error-content">
      <div class="error-icon">
        <QuestionCircleOutlined />
      </div>
      <h1>404</h1>
      <p>抱歉，您访问的页面不存在</p>
      <a-button type="primary" @click="goHome">返回首页</a-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { QuestionCircleOutlined } from '@ant-design/icons-vue'

const router = useRouter()

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
    color: #4073fa;
    margin-bottom: 24px;
  }

  h1 {
    font-size: 72px;
    font-weight: 700;
    color: #4073fa;
    margin: 0 0 16px;
  }

  p {
    font-size: 18px;
    color: #666;
    margin-bottom: 32px;
  }
}
</style>
