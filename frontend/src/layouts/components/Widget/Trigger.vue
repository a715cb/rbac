<!--
  @文件: Trigger.vue
  @用途: 侧边栏折叠触发器组件
  @描述: 渲染侧边栏折叠/展开的切换按钮，根据侧边栏状态显示不同图标
         （折叠时显示展开图标，展开时显示折叠图标）。
         点击时触发 click 事件，由父组件处理折叠逻辑。
  @核心逻辑:
    1. 从 useSetting 获取 sidebarOpened 状态判断当前图标
    2. 点击时 emit click 事件，由父组件调用 toggleSidebar
-->
<template>
  <div class="trigger-container" @click="$emit('click')">
    <s-icon
      class="trigger-icon"
      :type="sidebarOpened ? 'menu-fold-outlined' : 'menu-unfold-outlined'"
    />
  </div>
</template>

<script lang="ts" setup>
import { useSetting } from '@/layouts/composables'

/** 点击事件，由父组件监听处理折叠逻辑 */
defineEmits(['click'])

/** 侧边栏展开状态，用于切换图标方向 */
const { sidebarOpened } = useSetting()
</script>

<style lang="less" scoped>
.trigger-container {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 6px;
  cursor: pointer;
  transition:
    background-color 0.25s ease,
    transform 0.15s ease;

  &:hover {
    background-color: @layout-trigger-hover-bg;
  }

  /* 点击缩放反馈 */
  &:active {
    background-color: @layout-trigger-active-bg;
    transform: scale(0.92);
  }
}

.trigger-icon {
  font-size: 18px;
  transition: transform 0.3s ease;
}

/* 暗色头部主题适配 */
.header-dark .trigger-container {
  &:hover {
    background-color: @layout-trigger-dark-hover-bg;
  }

  &:active {
    background-color: @layout-trigger-dark-active-bg;
  }

  .trigger-icon {
    color: @layout-trigger-dark-icon-color;
  }
}
</style>
