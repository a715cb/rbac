<template>
  <div class="logo" :style="{ padding: padding }">
    <router-link :to="{ path: '/' }">
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
      <h1 v-show="showTitle">{{ title }}</h1>
    </router-link>
  </div>
</template>

<script setup lang="ts">
import { computed, useId } from 'vue'
import { useSetting } from '@/layouts/composables'
import config from '@/config'

const id = useId()
const gradientId = computed(() => 'logo-gradient-' + id)

const props = defineProps({
  showTitle: {
    type: Boolean,
    default: true
  }
})

const { isMixLayout } = useSetting()

const title = computed(() => {
  return config.appName || 'RBAC 权限管理系统'
})

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
