<!--
  @文件: Sider.vue
  @用途: 侧边栏主体组件
  @描述: 渲染侧边栏的完整结构，包含 Logo、菜单列表和折叠触发器。
         支持左侧混合布局（left）下的双栏模式：LeftNav 显示一级菜单图标，
         Sider 显示当前一级菜单的子菜单。通过 useSetting 获取侧边栏宽度、
         主题、折叠状态等配置，并计算占位宽度确保内容区域不被遮挡。
  @核心逻辑:
    1. 根据 layout 模式决定是否显示 LeftNav（仅 left 布局 + 非移动端）
    2. sideMenus 计算当前应显示的菜单数据（mix/left 布局下为子菜单）
    3. stuffWidth 计算侧边栏占位宽度（left 布局 = leftNavWidth + sideWidth）
    4. siderLeft 计算 Sider 的 left 偏移（left 布局下需偏移 leftNavWidth）
-->
<template>
  <div class="sider-container">
    <!-- 占位元素：在文档流中占据与固定侧边栏相同的宽度，防止内容被遮挡 -->
    <div
      :style="{ width: `${stuffWidth}px`, flex: `0 0 ${stuffWidth}px` }"
      class="ant-fixed-stuff"
    ></div>
    <!-- 左侧一级导航：仅在 left 布局且非移动端时显示 -->
    <left-nav v-if="isLeftNotMobile" />
    <!-- 侧边栏菜单区域：有菜单数据时才渲染 -->
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
      <!-- 折叠触发器：仅 mix 布局未开启 splitMenu 时显示 -->
      <template #trigger>
        <div class="ant-pro-sider-links">
          <layout-trigger style="padding-left: 15px" />
        </div>
      </template>
      <!-- Logo 区域：折叠时仅显示图标，展开时显示图标+标题 -->
      <Logo :show-title="sidebarOpened" />
      <!-- 菜单列表：可滚动区域，使用 base-menu 递归渲染 -->
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

/** 从全局设置中获取侧边栏相关的配置和状态 */
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

/** 当前侧边栏菜单数据列表 */
const menuList = computed(() => sideMenus.value)

/** 切换侧边栏折叠/展开状态 */
const toggle = () => {
  toggleSidebar()
}
</script>

<style lang="less" scoped>
@import '@/styles/layout/sidebar.less';

.sider-container {
  display: contents;
}

/* 侧边栏面板：固定定位 + 滚动条样式 + 右侧边框 */
.ant-pro-sider-fixed {
  .sidebar-panel();
  .sidebar-scroll();
  border-right: 1px solid var(--spe-layout-border-color);
}

/* 折叠触发器区域：左对齐，顶部边框分隔 */
.ant-pro-sider-links {
  text-align: left;
  border-top: 1px solid @layout-sidebar-light-border;
}

/* 覆盖 Ant Design Sider 触发器默认样式 */
:deep(.ant-layout-sider-trigger) {
  background: inherit;
  border-right: 1px solid var(--spe-layout-border-color);
}

/* 占位元素：禁止收缩，宽度过渡动画 */
.ant-pro-fixed-stuff {
  flex-shrink: 0;
  transition: width 0.2s;
}

/* 移除菜单右侧边框（侧边栏已有边框） */
.ant-pro-sider {
  :deep(.ant-menu-root) {
    border-right: none;
  }
}
</style>
