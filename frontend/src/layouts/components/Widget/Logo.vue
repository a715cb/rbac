<!--
  @文件: Logo.vue
  @用途: 应用 Logo 组件
  @描述: 渲染应用 Logo 图标和标题，点击跳转到首页。
         Logo 使用 SVG 绘制带渐变填充的圆形图标，内嵌字母 "R"。
         支持通过 showTitle 控制标题显示/隐藏（侧边栏折叠时隐藏标题），
         mix 布局下使用更紧凑的内边距。
  @核心逻辑:
    1. 使用 useId 生成唯一渐变 ID，避免多实例冲突
    2. showTitle prop 控制标题文本的显示
    3. isMixLayout 时使用紧凑内边距
-->
<template>
  <div class="logo" :style="{ padding: padding }">
    <router-link :to="{ path: '/' }">
      <!-- Logo SVG：渐变填充圆形 + 字母 R -->
      <svg
        class="logo-svg"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 100 100"
        width="30"
        height="30"
      >
        <defs>
          <linearGradient :id="gradientId" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color: var(--spe-logo-gradient-start); stop-opacity: 1" />
            <stop offset="100%" style="stop-color: var(--spe-logo-gradient-end); stop-opacity: 1" />
          </linearGradient>
        </defs>
        <circle cx="50" cy="50" r="45" :fill="'url(#' + gradientId + ')'" />
        <text
          x="50"
          y="65"
          font-family="Arial, sans-serif"
          font-size="40"
          font-weight="bold"
          fill="var(--spe-logo-text-fill)"
          text-anchor="middle"
        >
          R
        </text>
      </svg>
      <!-- 标题文本：折叠时隐藏 -->
      <h1 v-show="showTitle">{{ title }}</h1>
    </router-link>
  </div>
</template>

<script setup lang="ts">
import { computed, useId } from 'vue'
import { useSetting } from '@/layouts/composables'
import config from '@/config'

/** 生成唯一渐变 ID，避免页面中多个 Logo 实例的 SVG 渐变冲突 */
const id = useId()
const gradientId = computed(() => 'logo-gradient-' + id)

/** 组件属性定义 */
const props = defineProps({
  /** 是否显示标题文本，侧边栏折叠时设为 false */
  showTitle: {
    type: Boolean,
    default: true
  }
})

const { isMixLayout } = useSetting()

/** 应用标题，从全局配置获取 */
const title = computed(() => {
  return config.appName || 'RBAC 权限管理系统'
})

/** Logo 区域内边距，mix 布局下更紧凑 */
const padding = computed(() => {
  if (isMixLayout.value) {
    return '8px 16px'
  }
  return props.showTitle ? '16px' : '16px 8px'
})
</script>

<style lang="less" scoped>
.sider.light .logo {
  box-shadow: none;
}

.logo {
  position: relative;
  display: flex;
  align-items: center;
  line-height: 32px;
  cursor: pointer;

  a {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 32px;
  }

  .logo-svg {
    display: inline-block;
    height: 30px;
    width: 30px;
    vertical-align: middle;
    transition:
      height 0.2s,
      width 0.2s;
  }

  h1 {
    display: inline-block;
    height: 32px;
    margin: 0 0 0 12px;
    overflow: hidden;
    color: var(--spe-logo-title-color);
    font-weight: 600;
    font-size: 18px;
    line-height: 32px;
    vertical-align: middle;
    animation-duration: 0.2s;
    transition: color 0.3s ease;
  }
}
</style>
