<!--
  @文件: Header.vue
  @用途: 应用顶部导航栏组件
  @描述: 负责渲染顶部导航栏，包含折叠触发器、面包屑导航、顶部菜单和用户菜单。
         支持多种布局模式（side/top/mix/left），根据布局模式动态切换显示内容：
         - side/left 布局：显示折叠触发器 + 面包屑
         - top 布局：显示 Logo + 完整横向菜单
         - mix 布局 + splitMenu：显示 Logo + 一级菜单
         - mix 布局 + 非 splitMenu：显示 Logo + 面包屑
  @核心逻辑:
    1. 根据 layout 和 splitMenu 计算顶部菜单显示策略
    2. 通过 useHeaderSetting 获取头部布局参数（宽度、高度、面包屑可见性）
    3. 通过 useSetting 获取全局配置（主题、布局模式、菜单数据）
    4. mix 布局下使用 getMatchedMenuPath 计算当前一级菜单高亮
-->
<template>
  <!-- 占位 header，用于撑开布局高度，避免内容被固定定位的 header 遮挡 -->
  <header :style="getDomStyle"></header>
  <!-- 顶部导航栏：固定定位，支持亮色/暗色主题切换 -->
  <a-layout-header
    :class="['layout-header', 'layout-header-fixed', `header-${headTheme}`]"
    :style="headerStyle"
  >
    <div class="ant-pro-global-header">
      <!-- 左侧区域：折叠触发器 + 面包屑导航 -->
      <!-- top 布局下不显示面包屑（顶部菜单已承载导航功能） -->
      <div class="header-left">
        <layout-trigger v-show="sideOrMobile" @click="toggle" />
        <s-breadcrumb v-show="showBreadcrumb && layout !== 'top'" class="header-breadcrumb" />
      </div>

      <!--
        中间区域：顶部菜单模式或分割菜单模式时显示横向菜单
        - top 布局：使用 base-menu 递归组件渲染完整菜单树（支持多级子菜单）
        - mix 布局 + splitMenu：仅渲染一级菜单作为顶部导航
        - mix 布局 + 非 splitMenu：显示面包屑（由父级条件控制 showBreadcrumb）
      -->
      <!-- 使用 visibleTopMenus 计算属性来控制顶部菜单的显示，避免模板中的 v-if 直接判断 -->
      <div v-show="showTopMenu" class="header-layout-menu">
        <logo class="mr-[30px]" />
        <!-- top 布局：使用递归菜单组件渲染完整菜单树 -->
        <base-menu v-if="layout == 'top'" :theme="navTheme" :menus="menus" mode="horizontal" />
        <!-- mix 布局且开启 splitMenu：仅渲染一级菜单作为顶部导航 -->
        <a-menu
          v-else-if="splitMenu"
          :selected-keys="selectedKeys"
          :theme="headTheme"
          mode="horizontal"
          class="mix-top-menu"
          @click="routerTo"
        >
          <a-menu-item v-for="item in visibleTopMenus" :key="item.path">
            <template #icon>
              <s-icon v-if="hasMenuIcon(item)" :type="getMenuIcon(item)" :size="14" />
            </template>
            {{ getMenuTitle(item) }}
          </a-menu-item>
        </a-menu>
        <!-- mix 布局未开启 splitMenu：显示面包屑 -->
        <s-breadcrumb v-else class="flex items-center ml-[10px]" />
      </div>

      <!-- 右侧区域：用户菜单（头像、下拉操作等） -->
      <div class="layout-header-action">
        <user-menu />
      </div>
    </div>
  </a-layout-header>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import BaseMenu from './Menu/index'
import { useSetting, useHeaderSetting } from '@/layouts/composables'
import { UserMenu, LayoutTrigger, Logo } from './Widget'
import {
  isMenuVisible,
  hasMenuIcon,
  getMenuIcon,
  getMenuTitle,
  getMatchedMenuPath
} from './menuUtils'
import type { RouteMenu } from './menuUtils'

const { menus } = useSetting()

const visibleTopMenus = computed(() => {
  return menus.value.filter((item) => isMenuVisible(item as RouteMenu))
})

// 头部布局相关配置：宽度、高度、是否显示顶部菜单/面包屑等
const { width, height, sideOrMobile, showBreadcrumb, showTopMenu } = useHeaderSetting()

// 全局布局配置：侧边栏状态、主题、布局模式、菜单分割等
const { navTheme, layout, routerTo, splitMenu, headTheme, toggleSidebar } = useSetting()

const route = useRoute()

/**
 * 当前选中的一级菜单路径
 * 用于 mix 布局下顶部横向菜单的高亮显示
 */
const selectedKeys = computed(() => [getMatchedMenuPath(route, menus.value)])

/**
 * 占位 header 的样式
 * 作用：在文档流中占据与固定 header 相同的高度，防止页面内容被遮挡
 */
const getDomStyle = computed(() => {
  return {
    height: height.value,
    background: 'transparent'
  }
})

/**
 * 固定 header 的样式
 * 包含高度、行高和动态宽度（根据侧边栏展开/折叠状态变化）
 */
const headerStyle = computed(() => {
  return {
    height: height.value,
    lineHeight: height.value,
    width: width.value
  }
})

const toggle = () => {
  toggleSidebar()
}
</script>

<style lang="less" scoped>
@import '@/styles/layout/header.less';

.layout-header-fixed {
  .header-fixed();
}

.layout-header {
  padding: 0px;
  right: 0px;
  z-index: 9;

  .header-layout-menu {
    position: relative;
    width: 100%;
    height: 100%;
    transition: none;
    display: flex;
    align-items: center;
    transition:
      background 0.3s,
      width 0.2s;
    flex: 1;

    :deep(.ant-menu) {
      background: transparent;
      line-height: inherit;
    }

    :deep(.ant-menu-horizontal) {
      display: flex;
      align-items: center;
      flex-wrap: nowrap;
      white-space: nowrap;
      overflow: visible;

      > .ant-menu-item,
      > .ant-menu-submenu {
        flex-shrink: 0;
      }

      .ant-menu-item-icon,
      .anticon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        vertical-align: middle;
      }

      .ant-menu-title-content {
        margin-inline-start: 8px;
      }

      .svg-icon {
        width: 1em;
        height: 1em;
        vertical-align: middle;
      }
    }

    :deep(.ant-menu:not(.ant-menu-horizontal)) {
      .s-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        vertical-align: middle;
        margin-right: 8px;
      }
    }
  }

  .ant-pro-global-header {
    .header-content();
    border-bottom: 1px solid var(--spe-layout-border-color);

    .header-left {
      display: flex;
      align-items: center;
      flex-shrink: 0;
      min-width: 0;
      gap: 4px;
    }

    .header-breadcrumb {
      margin-left: 4px;
      min-width: 0;
      overflow: hidden;

      :deep(.s-breadcrumb) {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
    }

    .layout-header-action {
      .layout-header-action();
    }
  }
}

.header-light {
  background: @layout-header-light-bg;

  :deep(.account-name) {
    color: @layout-header-light-account-text;
  }
}

.header-dark {
  background: @layout-header-dark-bg;

  :deep(.s-breadcrumb__inner),
  :deep(.s-breadcrumb__inner .no-redirect) {
    color: @layout-header-dark-breadcrumb-text;
  }

  :deep(.s-breadcrumb__separator) {
    color: @layout-header-dark-breadcrumb-separator;
  }
}

.mix-top-menu {
  flex: 1;
  min-width: 0;
  border-bottom: none !important;

  :deep(.ant-menu-item),
  :deep(.ant-menu-submenu) {
    display: inline-flex;
    align-items: center;
    height: 46px;
    line-height: 46px;
    padding: 0 16px;
    margin: 0 4px;
    border-radius: 4px;
    transition: all 0.3s ease;

    &:hover {
      background-color: rgba(255, 255, 255, 0.1);
    }

    .ant-menu-item-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-right: 6px;
      font-size: 14px;
    }

    .ant-menu-title-content {
      display: inline-flex;
      align-items: center;
    }
  }

  :deep(.ant-menu-item-selected) {
    background-color: var(--ant-color-primary-bg);
    color: var(--ant-color-primary);
    font-weight: 500;

    &::after {
      display: none;
    }
  }
}

.header-light .mix-top-menu {
  :deep(.ant-menu-item),
  :deep(.ant-menu-submenu) {
    color: @layout-mix-menu-light-text;

    &:hover {
      background-color: @layout-mix-menu-light-hover-bg;
      color: var(--ant-color-primary);
    }
  }

  :deep(.ant-menu-item-selected) {
    background-color: var(--ant-color-primary-bg);
    color: var(--ant-color-primary);
  }
}

.header-dark .mix-top-menu {
  :deep(.ant-menu-item),
  :deep(.ant-menu-submenu) {
    color: @layout-mix-menu-dark-text;

    &:hover {
      background-color: @layout-mix-menu-dark-hover-bg;
      color: @layout-mix-menu-dark-hover-text;
    }
  }

  :deep(.ant-menu-item-selected) {
    background-color: @layout-mix-menu-dark-selected-bg;
    color: @layout-mix-menu-dark-selected-text;
  }
}

@media screen and (max-width: 1200px) {
  .mix-top-menu {
    :deep(.ant-menu-item),
    :deep(.ant-menu-submenu) {
      padding: 0 12px;
      margin: 0 2px;
    }
  }
}

@media screen and (max-width: 992px) {
  .mix-top-menu {
    :deep(.ant-menu-item),
    :deep(.ant-menu-submenu) {
      padding: 0 8px;
      margin: 0;

      .ant-menu-item-icon {
        margin-right: 4px;
      }
    }
  }

  .layout-header .ant-pro-global-header {
    padding: 0 12px;

    .header-left {
      gap: 2px;
    }

    .header-breadcrumb {
      margin-left: 2px;
    }
  }
}

@media screen and (max-width: 576px) {
  .layout-header .ant-pro-global-header {
    padding: 0 8px;

    .header-breadcrumb {
      :deep(.s-breadcrumb__separator) {
        margin: 0 4px;
      }
    }
  }
}
</style>
