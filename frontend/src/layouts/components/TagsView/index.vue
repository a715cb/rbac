<!--
  TagsView - 多标签页视图组件
  功能：展示已访问页面的标签页，支持点击切换、关闭当前、关闭其他、关闭全部等操作。
  与 useTabs hook 配合使用，管理标签页的列表、激活状态和操作逻辑。
-->
<template>
  <!--
    占位元素：当标签页固定在顶部时，
    在页面内容区顶部预留与标签页等高的空间，避免内容被标签页遮挡。
    仅在 fixedMultiTabs（固定标签页）且 showMultiTabs（显示标签页）为 true 时渲染。
  -->
  <div
    v-show="fixedMultiTabs && showMultiTabs"
    class="multi-tab-stuff"
    :style="`height: ${height}`"
  ></div>

  <!--
    标签页容器：
    - fixedMultiTabs 为 true 时使用固定定位（fixed），此时 top 设为标签页高度以避免遮挡内容
    - tabsWidth 控制容器宽度（由 useSetting 计算得出）
    - tabsType 控制标签页样式类型（如 smooth-tab / smart-tab）
  -->
  <div
    v-show="showMultiTabs"
    :style="{ width: tabsWidth, top: fixedMultiTabs ? height : '' }"
    :class="['tabs', tabsType, { 'multi-tab-fixed': fixedMultiTabs }]"
  >
    <a-tabs
      v-model:active-key="active"
      hide-add
      class="tab"
      type="editable-card"
      @change="onChange"
      @edit="onEdit"
    >
      <!--
        遍历渲染所有标签页：
        - key 使用 path 确保唯一性，path 也是路由跳转的依据
        - closable 控制是否显示关闭按钮：仅当标签页数量 > 1 时允许关闭，
          避免关闭最后一个标签页导致页面空白
      -->
      <a-tab-pane v-for="item in list" :key="item.path" :closable="!item.affix">
        <template #tab>
          <!-- 标签页标题前显示菜单图标，无图标时使用默认图标 -->
          <s-icon :type="item.icon || DEFAULT_MENU_ICON" />
          {{ item.title }}
        </template>
      </a-tab-pane>

      <!-- 标签页右侧下拉工具菜单：关闭其他、关闭当前、关闭全部 -->
      <template #rightExtra>
        <a-dropdown class="tab-tool">
          <a-button style="border: none">
            <template #icon>
              <s-icon type="DownOutlined" />
            </template>
          </a-button>
          <template #overlay>
            <a-menu>
              <a-menu-item @click="closeOther">关 闭 其 他</a-menu-item>
              <a-menu-item @click="closeCurrent">关 闭 当 前</a-menu-item>
              <a-menu-item @click="closeAll">关 闭 全 部</a-menu-item>
            </a-menu>
          </template>
        </a-dropdown>
      </template>
    </a-tabs>
  </div>
</template>

<script setup lang="ts">
/**
 * 组件依赖说明：
 * - useTabs: 管理标签页状态（列表、激活状态）和操作方法（关闭、跳转等）
 * - useSetting: 获取全局设置（是否显示标签页、是否固定、宽度、样式类型）
 * - useHeaderSetting: 获取头部高度，用于计算固定标签页的占位空间
 * - TabsProps: Ant Design Vue Tabs 组件的事件类型定义
 */
import { useTabs } from './useTabs'
import { useSetting, useHeaderSetting } from '@/layouts/composables'
import { DEFAULT_MENU_ICON } from '../menuUtils'
import type { TabsProps } from 'ant-design-vue'

/**
 * 从 useSetting 获取标签页相关的全局配置：
 * - showMultiTabs: 是否显示多标签页
 * - fixedMultiTabs: 是否固定标签页（固定时使用 fixed 定位）
 * - tabsWidth: 标签页容器宽度（桌面端全宽，移动端自适应）
 * - tabsType: 标签页样式类型（smooth-tab / smart-tab）
 */
const { showMultiTabs, fixedMultiTabs, tabsWidth, tabsType } = useSetting()

/**
 * 从 useTabs 获取标签页的状态和操作方法：
 * - navigation: 路由跳转函数，跳转时携带原始 query 参数
 * - list: 已访问页面的标签页列表
 * - active: 当前激活的标签页 path
 * - close: 关闭指定标签页
 * - closeOther: 关闭除当前外的其他标签页
 * - closeCurrent: 关闭当前激活的标签页
 * - closeAll: 关闭所有标签页
 */
const { navigation, list, active, close, closeOther, closeCurrent, closeAll } = useTabs()

/**
 * 从 useHeaderSetting 获取头部高度：
 * - 用于计算固定标签页的占位空间，以及 fixed 定位时的 top 值
 */
const { height } = useHeaderSetting()

/**
 * onEdit - 标签页编辑事件处理
 * 当用户点击标签页的关闭按钮时触发，由 Ant Design Tabs 组件的 @edit 事件调用。
 * action 为 'remove' 时执行关闭操作，其他 action 忽略。
 *
 * @param path - 被编辑的标签页路径
 * @param action - 编辑类型（如 'remove' 表示关闭）
 */
const onEdit: TabsProps['onEdit'] = (path, action) => {
  if (action == 'remove') close(path as string)
}

/**
 * onChange - 标签页切换事件处理
 * 当用户点击切换标签页时触发，内部查找目标标签页的完整信息，
 * 跳转时保留原始 query 参数以确保页面数据正确加载。
 *
 * @param path - 被切换到的标签页路径
 */
const onChange: TabsProps['onChange'] = (path) => {
  const routes = list.value.find((item) => item.path == path)
  navigation({ path: path as string, query: routes?.query })
}
</script>

<style lang="less">
@import '@/styles/layout/tags-view.less';
</style>
