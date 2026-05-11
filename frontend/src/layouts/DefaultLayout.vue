<!--
  @description 应用主布局组件
  负责组装侧边栏、顶部导航、多标签页、内容区域、底部栏及设置抽屉，
  并根据全局配置动态切换主题（亮色/暗色/暗黑）、布局模式（左侧/混合）和路由切换动画。
-->
<template>
  <!-- 根布局容器，通过 layoutClass 动态绑定主题与布局样式 -->
  <a-layout :class="layoutClass">
    <!-- 侧边栏：根据配置决定是否显示 -->
    <layout-sidebar v-show="showSidebar" />
    <!-- 右侧主区域：包含头部、标签页、内容区、底部栏 -->
    <a-layout class="w-full">
      <layout-header />
      <layout-tabs />
      <!-- 内容区域：承载路由视图，支持切换动画 -->
      <a-layout-content class="layout-content">
        <router-view v-slot="{ Component, route }">
          <!-- 开启动画时使用 transition 包裹，mode="out-in" 确保旧组件先退出再进入新组件 -->
          <transition v-if="openAnimation" :name="animation" mode="out-in" appear>
            <component :is="Component" :key="route.fullPath" />
          </transition>
          <!-- 未开启动画时直接渲染组件 -->
          <component :is="Component" v-else :key="route.fullPath" />
        </router-view>
      </a-layout-content>
      <!-- 底部栏：根据配置决定是否显示 -->
      <layout-footer v-show="showFooter" />
    </a-layout>
    <!-- 设置抽屉：通过 ref 暴露 toggle 方法，支持远程控制展开/收起 -->
    <setting-drawer ref="settingDrawerRef" />
  </a-layout>
</template>

<script setup lang="ts">
import { computed, provide, ref, nextTick } from 'vue'
import { LayoutSidebar, LayoutHeader, LayoutFooter, LayoutTabs, SettingDrawer } from './components'
import { useSetting } from './composables'

/**
 * 从全局设置 Hook 中解构布局相关配置
 * - showSidebar   是否显示侧边栏
 * - theme         当前主题模式（light / dark / realDark）
 * - layout        当前布局模式（left / mix）
 * - animation     路由切换动画名称
 * - openAnimation 是否开启动画
 * - showMultiTabs 是否显示多标签页
 * - showFooter    是否显示底部栏
 */
const { showSidebar, theme, layout, animation, openAnimation, showMultiTabs, showFooter } =
  useSetting()

/** 设置抽屉组件引用，用于调用其 toggle 方法 */
const settingDrawerRef = ref()

/**
 * 通过 provide 向所有后代组件注入 openSetting 方法，
 * 子组件可通过 inject('openSetting') 调用来打开设置面板。
 * 使用 nextTick 确保在 DOM 更新后再操作抽屉，避免时序问题。
 */
provide('openSetting', async () => {
  await nextTick()
  settingDrawerRef.value?.toggle()
})

/**
 * 动态计算布局容器的 CSS 类名
 * - basic-layout          基础布局类
 * - ant-theme-{theme}     主题类，控制颜色方案
 * - ant-layout-{layout}   布局类，控制侧边栏与头部的排列方式
 * - multi-tabs            多标签页标识类，影响内容区高度计算
 */
const layoutClass = computed(() => {
  return [
    'basic-layout',
    `ant-theme-${theme.value}`,
    `ant-layout-${layout.value}`,
    { 'multi-tabs': showMultiTabs.value }
  ]
})
</script>

<style lang="less" scoped>
.basic-layout {
  display: flex;
  flex-direction: row;
  width: 100%;
  min-height: 100vh;
  overflow-x: hidden;
  background: var(--spe-layout-bg-color, @background-color-base);

  :deep(.layout-content) {
    position: relative;
    padding: 16px;
  }
}

.w-full {
  flex: 1;
  min-width: 0;
  background: var(--spe-layout-bg-color, @background-color-base);
}

:deep(.ant-layout) {
  background: var(--spe-layout-bg-color, @background-color-base);
}

.ant-layout-left,
.ant-layout-mix {
  :deep(.layout-header) {
    z-index: 11;
  }
}

.ant-theme-realDark {
  :deep(.ant-pro-sider) {
    background-color: var(--ant-color-bg-container);
  }

  :deep(.logo h1) {
    color: var(--spe-layout-logo-text-color);
  }

  :deep(.layout-header) {
    background: var(--ant-color-bg-container);
  }

  :deep(.ant-layout-content) {
    background: var(--ant-color-bg-layout);
  }
}
</style>
