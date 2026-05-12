<!--
  @文件: SettingDrawer/index.vue
  @用途: 设置抽屉组件
  @描述: 提供可视化配置界面，允许用户自定义主题风格、主题色、导航模式和其他系统设置。
         所有设置通过 useSetting Hook 统一管理，并持久化到 localStorage。
         支持两种入口模式：通过头部 UserMenu 打开，或固定在页面右侧作为把手。
  @核心逻辑:
    1. 从 useSetting 获取所有设置状态和变更方法
    2. 监听 layout 变化自动调整 splitMenu 和 showBreadcrumb 的联动
    3. 通过 defineExpose 暴露 toggle 方法供父组件远程控制
    4. 主题风格和主题色变更分别调用 changeTheme 和 changeSetting
-->
<template>
  <div class="setting-container">
    <!--
      a-drawer：Ant Design 抽屉组件
      - width: 抽屉宽度 310px
      - placement: 从右侧滑入
      - closable: false（隐藏默认关闭按钮，使用自定义关闭按钮）
      - rootClassName: 动态类名，控制抽屉显示/隐藏动画
      - open: 抽屉可见性，由 visible 控制
    -->
    <a-drawer
      width="310"
      placement="right"
      :closable="false"
      :root-class-name="rootClassName"
      :body-style="{ padding: '20px' }"
      :open="visible"
      @close="onClose"
    >
      <!--
        自定义关闭按钮：
        仅在 setPosition == 'header'（设置入口在头部）时显示，
        位于抽屉右上角，点击关闭抽屉。
      -->
      <div v-show="setPosition == 'header'" class="custom-close-btn flex-center" @click="onClose">
        <s-icon type="close-outlined" />
      </div>

      <div class="setting-drawer-index-content">
        <!-- ==================== 整体风格设置 ==================== -->
        <div :style="{ marginBottom: '24px' }">
          <h3 class="setting-drawer-index-title">整体风格设置</h3>
          <!--
            主题风格选择器：
            - 使用 flex 横向排列所有主题选项
            - 遍历 options.themeStyle 渲染每个主题的 SVG 预览图标
            - 选中项右上角显示 check 图标
          -->
          <div class="setting-drawer-block-checbox">
            <a-tooltip v-for="(item, index) in options.themeStyle" :key="index">
              <template #title>{{ item.label }}</template>
              <div class="setting-drawer-index-item" @click="setTheme(item.value)">
                <s-icon :type="item.icon" size="48px"></s-icon>
                <!-- 选中状态：右上角显示蓝色对勾图标 -->
                <div v-show="theme === item.value" class="setting-drawer-index-selectIcon">
                  <s-icon type="check-outlined" />
                </div>
              </div>
            </a-tooltip>
          </div>
        </div>

        <!-- ==================== 主题色设置 ==================== -->
        <div :style="{ marginBottom: '24px' }">
          <h3 class="setting-drawer-index-title">主题色</h3>
          <div style="height: 20px">
            <!--
              主题色选择器：
              - 遍历预设的 8 种主题色
              - 颜色标签内显示选中色的 check 图标
              - 点击切换 primaryColor
            -->
            <a-tooltip
              v-for="(item, index) in options.themeColors"
              :key="index"
              class="setting-drawer-theme-color-colorBlock"
            >
              <template #title>
                {{ item.key }}
              </template>
              <a-tag :color="item.color" @click="changeColor(item.color)">
                <s-icon v-show="item.color === primaryColor" type="check-outlined"></s-icon>
              </a-tag>
            </a-tooltip>
          </div>
        </div>
        <a-divider />

        <!-- ==================== 导航模式设置 ==================== -->
        <div :style="{ marginBottom: '24px' }">
          <h3 class="setting-drawer-index-title">导航模式</h3>
          <!--
            导航模式选择器：
            - 遍历 4 种布局选项（side/top/mix/left）
            - 选中项右上角显示 check 图标
          -->
          <div class="setting-drawer-block-checbox">
            <a-tooltip v-for="item in options.layouts" :key="item.value">
              <template #title>{{ item.label }}</template>
              <div class="setting-drawer-index-item" @click="changeSetting('layout', item.value)">
                <s-icon :type="item.icon" size="48px"></s-icon>
                <div v-show="layout === item.value" class="setting-drawer-index-selectIcon">
                  <s-icon type="check-outlined" />
                </div>
              </div>
            </a-tooltip>
          </div>
        </div>

        <a-divider />

        <!-- ==================== 其他设置 ==================== -->
        <div class="mt-[24px]">
          <h3 class="setting-drawer-index-title">其他设置</h3>
          <div class="mt-[20px]">
            <a-list :split="false">
              <!--
                路由动画：控制页面切换时是否启用路由动画
                通过 openAnimation 开关控制，动画类型受 openAnimation 影响
              -->
              <set-list-item title="路由动画" label-for="setting-open-animation">
                <a-switch
                  id="setting-open-animation"
                  size="small"
                  :checked="openAnimation"
                  @change="changeSetting('openAnimation', $event)"
                />
              </set-list-item>

              <set-list-item title="动画类型" label-for="setting-animation-type">
                <a-select
                  id="setting-animation-type"
                  size="small"
                  :disabled="!openAnimation"
                  style="width: 105px"
                  :options="options.animation"
                  :value="animation"
                  @change="changeSetting('animation', $event)"
                ></a-select>
              </set-list-item>

              <set-list-item title="分割菜单" label-for="setting-split-menu">
                <a-switch
                  id="setting-split-menu"
                  size="small"
                  :disabled="['top', 'mix', 'left'].includes(layout)"
                  :checked="splitMenu"
                  @change="changeSetting('splitMenu', $event)"
                />
              </set-list-item>

              <set-list-item title="面包屑" label-for="setting-breadcrumb">
                <a-switch
                  id="setting-breadcrumb"
                  size="small"
                  :disabled="['top', 'mix', 'left'].includes(layout)"
                  :checked="showBreadcrumb"
                  @change="changeSetting('showBreadcrumb', $event)"
                />
              </set-list-item>

              <set-list-item title="标签页" label-for="setting-multi-tabs">
                <a-switch
                  id="setting-multi-tabs"
                  size="small"
                  :checked="showMultiTabs"
                  @change="changeSetting('showMultiTabs', $event)"
                />
              </set-list-item>

              <set-list-item title="固定标签页" label-for="setting-fixed-multi-tabs">
                <a-switch
                  id="setting-fixed-multi-tabs"
                  size="small"
                  :checked="fixedMultiTabs"
                  @change="changeSetting('fixedMultiTabs', $event)"
                />
              </set-list-item>

              <set-list-item title="标签页类型" label-for="setting-tabs-type">
                <a-select
                  id="setting-tabs-type"
                  size="small"
                  style="width: 105px"
                  :options="options.tabsType"
                  :value="tabsType"
                  @change="changeSetting('tabsType', $event)"
                ></a-select>
              </set-list-item>

              <set-list-item title="页面加载条" label-for="setting-nprogress">
                <a-switch
                  id="setting-nprogress"
                  size="small"
                  :checked="openNProgress"
                  @change="changeSetting('openNProgress', $event)"
                />
              </set-list-item>

              <set-list-item title="圆角" label-for="setting-border-radius">
                <a-input-number
                  id="setting-border-radius"
                  :formatter="(value: number | string) => `${value}px`"
                  :parser="(value: string) => value.replace('px', '')"
                  style="width: 105px"
                  :value="borderRadius"
                  :min="1"
                  :max="16"
                  @change="changeSetting('borderRadius', $event)"
                />
              </set-list-item>

              <set-list-item title="底部栏" label-for="setting-show-footer">
                <a-switch
                  id="setting-show-footer"
                  size="small"
                  :checked="showFooter"
                  @change="changeSetting('showFooter', $event)"
                />
              </set-list-item>

              <set-list-item title="主题设置位置">
                <a-segmented
                  v-model:value="setPosition"
                  aria-label="主题设置位置"
                  style="width: 105px"
                  :options="options.setPosiList"
                  @change="changeSetting('setPosition', $event)"
                />
              </set-list-item>
            </a-list>
          </div>
        </div>
      </div>

      <!--
        固定把手：
        仅在 setPosition == 'fixed' 时显示，
        位于抽屉左侧（抽屉右边），点击切换抽屉开闭状态。
        图标根据抽屉可见性显示设置图标或关闭图标。
      -->
      <template #handle>
        <div v-show="setPosition == 'fixed'" class="setting-drawer-index-handle" @click="toggle">
          <s-icon :type="visible ? 'close-outlined' : 'setting-outlined'" style="color: #fff" />
        </div>
      </template>
    </a-drawer>
  </div>
</template>

<script setup lang="ts">
/**
 * 组件依赖说明：
 * - options: ./options 模块，包含所有设置项的配置数据（主题风格、主题色、导航模式、动画类型等）
 * - SetListItem: 设置列表项子组件，用于统一样式
 * - useSetting: 全局设置状态管理 hook，统一管理所有设置的状态和操作方法
 */
import { ref, computed, watch } from 'vue'
import * as options from './options'
import SetListItem from './SetListItem.vue'
import { useSetting } from '../../composables'

/**
 * 从 useSetting 获取所有设置相关的状态和操作方法：
 * - layout: 导航模式（side/top/mix/left）
 * - theme: 主题风格（light/dark/realDark）
 * - primaryColor: 主题色
 * - animation / openAnimation: 路由动画开关和类型
 * - splitMenu / showBreadcrumb: 分割菜单和面包屑开关
 * - showMultiTabs / fixedMultiTabs / tabsType: 标签页相关设置
 * - openNProgress: 页面加载条开关
 * - borderRadius: 圆角大小
 * - setPosition: 设置入口位置
 * - changeSetting: 通用设置变更方法
 * - changeTheme: 主题变更方法（同时更新 data-theme 属性）
 */
const {
  layout,
  theme,
  primaryColor,
  animation,
  showBreadcrumb,
  fixedMultiTabs,
  showMultiTabs,
  showFooter,
  splitMenu,
  tabsType,
  openAnimation,
  openNProgress,
  borderRadius,
  setPosition,
  changeSetting,
  changeTheme
} = useSetting()

/** visible: 抽屉可见性状态，默认关闭 */
const visible = ref<boolean>(false)

watch(
  () => layout.value,
  (newLayout) => {
    if (newLayout === 'side') {
      if (splitMenu.value) {
        changeSetting('splitMenu', false)
      }
      if (!showBreadcrumb.value) {
        changeSetting('showBreadcrumb', true)
      }
    } else if (['top', 'mix', 'left'].includes(newLayout)) {
      if (!splitMenu.value) {
        changeSetting('splitMenu', true)
      }
      if ((newLayout === 'mix' || newLayout === 'top') && showBreadcrumb.value) {
        changeSetting('showBreadcrumb', false)
      }
      if (newLayout === 'left' && !showBreadcrumb.value) {
        changeSetting('showBreadcrumb', true)
      }
    }
  },
  { immediate: true }
)

/**
 * rootClassName: 动态根类名
 * - 抽屉关闭时：仅有 'setting-drawer' 类
 * - 抽屉打开时：'setting-drawer setting-drawer-show'
 * 用于通过 CSS 控制抽屉滑入/滑出动画
 */
const rootClassName = computed(() => {
  const className = visible.value ? 'setting-drawer-show' : ''
  return `setting-drawer ${className}`
})

/** onClose: 关闭抽屉，将 visible 设为 false */
const onClose = () => {
  visible.value = false
}

/** toggle: 切换抽屉可见性 */
const toggle = () => {
  visible.value = !visible.value
}

/**
 * setTheme: 切换主题风格
 * 调用 useSetting 的 changeTheme 方法，传入新的主题值。
 * changeTheme 内部会更新 theme 状态、data-theme 属性、Ant Design ConfigProvider 主题配置。
 *
 * @param newTheme - 新的主题值（light/dark/realDark）
 */
const setTheme = (newTheme: any) => {
  changeTheme(newTheme)
}

/**
 * changeColor: 切换主题色
 * 调用通用 changeSetting 方法，将 primaryColor 更新为新颜色。
 * 主题色变更会同时更新 Ant Design 主题令牌的 colorPrimary 和 CSS 变量。
 *
 * @param color - 新的主题色值（如 '#4073fa'）
 */
const changeColor = (color: string) => {
  changeSetting('primaryColor', color)
}

/**
 * defineExpose: 暴露组件方法供父组件调用
 * - toggle: 切换抽屉可见性
 * 用于父组件通过 ref 调用打开/关闭抽屉。
 */
defineExpose({
  toggle
})
</script>

<style lang="less" scoped>
/*
 * 抽屉滑入/滑出动画：
 * - .ant-drawer-content-wrapper 默认 transform: translate(100%) 隐藏在右侧
 * - .setting-drawer-show 时 transform: translate(0) 滑入视图
 * - box-shadow: none 由全局样式设置，此处覆盖避免重复
 */
:global(.setting-drawer .ant-drawer-content-wrapper) {
  display: block !important;
  transform: translate(100%);
  box-shadow: none;
}

:global(.setting-drawer-show .ant-drawer-content-wrapper) {
  transform: translate(0);
}

/* 主体内容区 */
.setting-drawer-index-content {
  /* 主题/布局选择器：flex 横向排列 */
  .setting-drawer-block-checbox {
    display: flex;

    .setting-drawer-index-item {
      margin-right: 16px;
      position: relative;
      border-radius: 4px;
      cursor: pointer;

      img {
        width: 48px;
      }

      /* 选中图标：绝对定位在右上角 */
      .setting-drawer-index-selectIcon {
        position: absolute;
        top: 0;
        right: 0;
        width: 100%;
        padding-top: 15px;
        padding-left: 24px;
        height: 100%;
        color: @layout-setting-select-color;
        font-size: 14px;
        font-weight: 700;
      }
    }
  }

  .setting-drawer-index-title {
    margin-bottom: 16px;
  }

  /* 覆盖 Ant Design List 组件的内边距 */
  :deep(.ant-list .ant-list-item) {
    padding: 8px;
  }

  /* 主题色色块：固定宽高，浮动排列 */
  .setting-drawer-theme-color-colorBlock {
    width: 20px;
    height: 20px;
    border-radius: 2px;
    float: left;
    cursor: pointer;
    margin-right: 8px;
    padding-left: 0px;
    padding-right: 0px;
    text-align: center;
    color: #fff;
    font-weight: 700;

    i {
      font-size: 14px;
    }
  }
}

/*
 * 固定把手（设置入口在右侧时）：
 * 位于抽屉右侧外侧，垂直居中（top: 240px），
 * 初始隐藏抽屉（right: 310px），点击展开抽屉。
 */
.setting-drawer-index-handle {
  width: 35px;
  height: 35px;
  background: @layout-setting-handle-bg;
  position: absolute;
  top: 240px;
  right: 310px;
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  pointer-events: auto;
  z-index: 1001;
  text-align: center;
  font-size: 16px;
  border-radius: 4px 0 0 4px;

  i {
    color: rgb(255, 255, 255);
    font-size: 20px;
  }
}

/*
 * 暗黑模式适配：
 * - 把手背景加亮（避免黑色把手在深色背景下不可见）
 * - 自定义关闭按钮图标设为白色，hover 时背景加亮
 */
html[data-theme='dark'] {
  .setting-drawer-index-handle {
    background: @layout-setting-handle-dark-bg;
  }
  .custom-close-btn {
    .anticon {
      color: #fff;
    }
    &:hover {
      opacity: 0.8;
      background: @layout-setting-close-dark-hover-bg;
    }
  }
}

/*
 * 自定义关闭按钮（头部入口模式）：
 * 圆形按钮，右上角定位，点击关闭抽屉。
 * X符号使用flexbox精确居中
 */
.custom-close-btn {
  position: absolute;
  width: 32px;
  height: 32px;
  right: 14px;
  top: 8px;
  font-size: 14px;
  cursor: pointer;
  z-index: 10;
  padding: 0;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;

  & .anticon {
    color: rgba(0, 0, 0, 0.45);
    display: flex;
    justify-content: center;
    align-items: center;
    line-height: 1;
    font-size: 14px;
  }

  &:hover {
    opacity: 0.8;
    background: @layout-setting-close-hover-bg;
  }
}
</style>
