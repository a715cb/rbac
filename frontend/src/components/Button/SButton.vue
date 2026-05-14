<template>
  <a-button v-bind="getProps" :class="getButtonClass">
    <template v-if="props.icon" #icon>
      <s-icon :type="props.icon" :size="props.iconSize" />
    </template>
    <template #default="data">
      <slot v-bind="data || {}"></slot>
    </template>
  </a-button>
</template>

<script lang="ts" setup>
import { computed } from 'vue'

type Color = 'error' | 'warning' | 'success'

const props = defineProps<{
  type?: string
  icon?: string
  iconSize?: string | number
  color?: Color
  disabled?: boolean
  [key: string]: unknown
}>()

const getButtonClass = computed(() => {
  const { color, disabled } = props
  return [
    {
      [`ant-btn-${color}`]: !!color,
      [`is-disabled`]: disabled
    }
  ]
})

const getProps = computed(() => {
  const result = { ...props }
  delete result.icon
  delete result.iconSize
  delete result.color
  return result
})
</script>

<style lang="less" scoped>
.ant-btn-success:not(.ant-btn-link, .is-disabled) {
  color: #fff;
  background-color: var(--spe-success-color);
  border-color: var(--spe-success-color);
}

.ant-btn-warning:not(.ant-btn-link, .is-disabled) {
  color: #fff;
  background-color: var(--spe-warning-color);
  border-color: var(--spe-warning-color);
}

.ant-btn-error:not(.ant-btn-link, .is-disabled) {
  border-color: var(--spe-danger-color);
  background-color: var(--spe-danger-color);
  color: #fff;
}

.ant-btn-background-ghost.ant-btn-success:not(.ant-btn-link) {
  border-width: 1px;
  border-color: var(--spe-success-color);
  background-color: transparent;
  color: var(--spe-success-color);
}
.ant-btn-background-ghost.ant-btn-warning:not(.ant-btn-link) {
  border-width: 1px;
  border-color: var(--spe-warning-color);
  background-color: transparent;
  color: var(--spe-warning-color);
}
.ant-btn-background-ghost.ant-btn-error:not(.ant-btn-link) {
  border-width: 1px;
  border-color: var(--spe-danger-color);
  background-color: transparent;
  color: var(--spe-danger-color);
}
</style>
