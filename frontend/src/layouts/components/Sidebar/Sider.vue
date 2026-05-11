<template>
  <div class="sider-container">
    <div
      :style="{ width: `${stuffWidth}px`, flex: `0 0 ${stuffWidth}px` }"
      class="ant-fixed-stuff"
    ></div>
    <left-nav v-if="isLeftNotMobile" />
    <a-layout-sider
      v-if="sideMenus.length"
      :style="siderLeft"
      :width="sideWidth"
      :theme="navTheme"
      :collapsed-width="sideWidth"
      :collapsed="!sidebarOpened"
      :collapsible="showSideTrigger"
      class="ant-pro-sider-fixed ant-pro-sider"
      @collapse="toggle"
    >
      <template #trigger>
        <div class="ant-pro-sider-links">
          <layout-trigger style="padding-left: 15px" />
        </div>
      </template>
      <Logo :show-title="sidebarOpened" />
      <div style="flex: 1 1 0%; overflow: hidden auto">
        <base-menu :menus="menuList" :theme="navTheme" :collapsed="!sidebarOpened" />
      </div>
    </a-layout-sider>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseMenu from '../Menu/index'
import LeftNav from './LeftNav.vue'
import { Logo, LayoutTrigger } from '../Widget'
import { useSetting } from '@/layouts/composables'

const {
  sideWidth,
  sidebarOpened,
  stuffWidth,
  navTheme,
  siderLeft,
  isLeftNotMobile,
  sideMenus,
  toggleSidebar,
  showSideTrigger
} = useSetting()

const menuList = computed(() => sideMenus.value)

const toggle = () => {
  toggleSidebar()
}
</script>

<style lang="less" scoped>
@import '@/styles/layout/sidebar.less';

.sider-container {
  display: contents;
}

.ant-pro-sider-fixed {
  .sidebar-panel();
  .sidebar-scroll();
  border-right: 1px solid var(--spe-layout-border-color);
}
.ant-pro-sider-links {
  text-align: left;
  border-top: 1px solid @layout-sidebar-light-border;
}
:deep(.ant-layout-sider-trigger) {
  background: inherit;
  border-right: 1px solid var(--spe-layout-border-color);
}
.ant-pro-fixed-stuff {
  flex-shrink: 0;
  transition: width 0.2s;
}
.ant-pro-sider {
  :deep(.ant-menu-root) {
    border-right: none;
  }
}
</style>
