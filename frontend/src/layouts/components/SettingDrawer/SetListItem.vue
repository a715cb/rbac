<!--
  @文件: SetListItem.vue
  @用途: 设置列表项组件
  @描述: 在设置抽屉中渲染单个设置项，左侧显示标题（支持 label 关联），
         右侧通过 slot 放置操作控件（Switch、Select、InputNumber 等）。
         当提供 labelFor 时渲染为 label 标签，点击标题可聚焦到对应控件。
  @核心逻辑:
    1. labelFor 属性非空时渲染 label 标签，实现标题与控件的关联
    2. 右侧操作区域通过 slot 传入，保持组件通用性
-->
<template>
  <a-list-item>
    <a-list-item-meta>
      <template #title>
        <!-- 有 labelFor 时渲染 label 标签，支持点击聚焦到对应控件 -->
        <label v-if="labelFor" :for="labelFor">{{ title }}</label>
        <!-- 无 labelFor 时渲染普通文本 -->
        <span v-else>{{ title }}</span>
      </template>
    </a-list-item-meta>
    <!-- 右侧操作区域：由父组件通过 slot 传入具体控件 -->
    <template #actions>
      <slot></slot>
    </template>
  </a-list-item>
</template>

<script setup lang="ts">
/** 组件属性定义 */
defineProps({
  /** 设置项标题文本 */
  title: {
    type: String,
    required: true
  },
  /** 关联控件的 id，用于 label 的 for 属性，实现点击标题聚焦控件 */
  labelFor: {
    type: String,
    default: ''
  }
})
</script>
