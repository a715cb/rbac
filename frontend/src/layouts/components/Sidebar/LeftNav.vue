<template>
  <div :class="['left-nav-wrapper', `left-nav-${theme}`]">
    <logo style="display: block" :show-title="false" />
    <div class="left-nav-container">
      <template v-for="item in visibleMenuList" :key="item.path">
        <li
          :class="[{ active: currentPath == item.path }, 'left-nav-item']"
          @click="routerTo(item)"
        >
          <div class="left-nav-link">
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

const { menus, theme, routerTo } = useSetting()
const route = useRoute()
const currentPath = computed(() => getMatchedMenuPath(route, menus.value))

const menuList = computed(() => menus.value)

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

.left-nav-realDark {
  .left-nav-container,
  :deep(.logo) {
    background: @layout-left-nav-dark-bg;
  }
}

.left-nav-light {
  border-right: 1px solid @layout-left-nav-item-light-border;

  :deep(.logo),
  .left-nav-container {
    background: @layout-left-nav-light-bg;
  }
  .left-nav-item {
    position: relative;
    color: @layout-left-nav-item-light-text;
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
