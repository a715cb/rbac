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

defineEmits(['click'])

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

  &:active {
    background-color: @layout-trigger-active-bg;
    transform: scale(0.92);
  }
}

.trigger-icon {
  font-size: 18px;
  transition: transform 0.3s ease;
}

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
