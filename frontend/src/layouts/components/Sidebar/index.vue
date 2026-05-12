<!--
  @文件: Sidebar/index.vue
  @用途: 侧边栏入口组件
  @描述: 根据设备类型（移动端/桌面端）切换侧边栏的展示形态。
         - 移动端：使用抽屉式侧边栏，点击遮罩或路由变化时自动关闭
         - 桌面端：直接渲染固定侧边栏组件
  @核心逻辑:
    1. 移动端使用 a-drawer 抽屉组件，桌面端使用 Sider 固定组件
    2. 监听路由变化，移动端下自动关闭侧边栏抽屉
    3. 注意：Drawer 使用 Portal 机制，v-show 不兼容，必须使用 v-if/v-else
-->
<template>
  <!-- 移动端：抽屉式侧边栏，从左侧滑出 -->
  <!--
    注意：Ant Design Vue Drawer 使用 Portal/Teleport 机制将内容渲染到 body。
    v-show 不兼容 Drawer，因为：
    1. Drawer 通过 getContainer 控制渲染容器，默认挂到 body
    2. v-show 通过 display:none 控制显隐，但 Portal 机制下 CSS 样式无法正确传递到目标容器
    3. Drawer 内部使用 v-if + v-show 两阶段渲染（DOM插入 + 动画触发），外部包裹无效
    因此保留 v-if/v-else 条件渲染模式，移动端销毁 Drawer，桌面端销毁 Sider
  -->
  <a-drawer
    v-if="isMobile"
    placement="left"
    :get-container="undefined"
    :width="208"
    :closable="false"
    :class="`drawer-sider ant-theme-${navTheme}`"
    :open="sidebarOpened"
    @close="handleClose"
  >
    <Sider />
  </a-drawer>
  <!-- 桌面端：固定侧边栏 -->
  <Sider v-else />
</template>

<script setup lang="ts">
import Sider from './Sider.vue'
import { useSetting } from '@/layouts/composables'
import { useRoute } from 'vue-router'
import { watch } from 'vue'

// 从全局设置中获取侧边栏相关状态
const { sidebarOpened, isMobile, navTheme, changeSetting } = useSetting()
const route = useRoute()

// 监听路由变化，移动端下自动关闭侧边栏，避免切换页面后抽屉仍停留
watch(route, () => {
  isMobile.value && handleClose()
})

/** 关闭侧边栏抽屉，将 sidebarOpened 设为 false */
const handleClose = () => {
  changeSetting('sidebarOpened', false)
}
</script>
