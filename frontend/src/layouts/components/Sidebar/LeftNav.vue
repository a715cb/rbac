<!--
  @文件: LeftNav.vue
  @用途: 左侧一级导航组件
  @描述: 在 left 布局模式下渲染左侧一级菜单导航栏，仅显示菜单图标和简短标题。
         每个一级菜单项垂直排列，点击后跳转到对应路由，当前激活项高亮显示。
         标题超过 4 个字符时使用 Tooltip 显示完整标题。
  @核心逻辑:
    1. 从 menus 中过滤隐藏菜单得到可见菜单列表
    2. 使用 getMatchedMenuPath 匹配当前路由对应的一级菜单路径
    3. 点击菜单项调用 routerTo 进行路由跳转
-->
<template>
  <div :class="['left-nav-wrapper', `left-nav-${theme}`]">
    <!-- Logo 区域：仅显示图标，不显示标题 -->
    <logo style="display: block" :show-title="false" />
    <div class="left-nav-container">
      <template v-for="item in visibleMenuList" :key="item.path">
        <li
          :class="[{ active: currentPath == item.path }, 'left-nav-item']"
          @click="routerTo(item)"
        >
          <div class="left-nav-link">
            <!-- 标题超过 4 字符时显示 Tooltip -->
            <a-tooltip
              :title="getMenuTitle(item).length > 4 ? getMenuTitle(item) : ''"
              placement="right"
            >
              <s-icon :size="16" :type="getMenuIcon(item)"></s-icon>
              <span class="line-feed-1 mt-[8px]">{{ getMenuTitle(item) }}</span>
            </a-tooltip>
          </div>
        </li>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { Logo } from '../Widget'
import { useSetting } from '@/layouts/composables'
import { getMatchedMenuPath, getMenuIcon, getMenuTitle } from '../menuUtils'

/** 从全局设置获取菜单数据、主题和路由跳转方法 */
const { menus, theme, routerTo } = useSetting()
const route = useRoute()

/** 当前激活的一级菜单路径，用于高亮显示 */
const currentPath = computed(() => getMatchedMenuPath(route, menus.value))

/** 完整菜单列表 */
const menuList = computed(() => menus.value)

/** 过滤隐藏菜单后的可见菜单列表 */
const visibleMenuList = computed(() => menuList.value.filter((item) => !item.hidden))
</script>

<style lang="less" scoped>
@import '@/styles/layout/sidebar.less';

.left-nav-wrapper {
  :deep(.logo) {
    background: @layout-left-nav-dark-bg;
  }
  .sidebar-panel();
  width: 80px;
  z-index: 99;
  overflow: hidden;
}

.left-nav-container {
  overflow: auto;
  overflow-x: hidden;
  width: 100%;
  height: 100vh;
  padding: 0 8px;
  flex-shrink: 0;
  background: @layout-left-nav-dark-bg;
  .left-nav-item {
    padding: 8px 3px;
    margin-bottom: 10px;
    cursor: pointer;
    width: 100%;
    color: @layout-left-nav-item-text;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    /* 激活状态：主色背景 */
    &.active {
      background: var(--ant-color-primary);
    }

    &:not(.active):hover {
      background: @layout-left-nav-item-hover;
    }
  }

  .left-nav-link {
    display: flex;
    width: 100%;
    height: 100%;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }
}

/* 暗黑模式适配 */
.left-nav-realDark {
  .left-nav-container,
  :deep(.logo) {
    background: @layout-left-nav-dark-bg;
  }
}

/* 亮色主题适配 */
.left-nav-light {
  border-right: 1px solid @layout-left-nav-item-light-border;

  :deep(.logo),
  .left-nav-container {
    background: @layout-left-nav-light-bg;
  }
  .left-nav-item {
    position: relative;
    color: @layout-left-nav-item-light-text;
    /* 亮色激活状态：主色文字 + 浅色背景 */
    &.active {
      color: var(--ant-color-primary);
      background: var(--ant-color-primary-bg);
    }

    &:hover {
      color: var(--ant-color-primary);
    }
  }
}
</style>
