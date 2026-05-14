<template>
  <span class="s-breadcrumb__item">
    <span :class="['s-breadcrumb__inner', to ? 'is-link' : '']" role="link" @click="onClick">
      <slot></slot>
    </span>
    <span class="s-breadcrumb__separator" role="presentation">
      <svg viewBox="0 0 16 16" width="1em" height="1em" fill="currentColor">
        <path
          d="M6 3.5L10.5 8L6 12.5"
          stroke="currentColor"
          stroke-width="1.5"
          fill="none"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
    </span>
  </span>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()
const props = defineProps({
  separator: {
    type: String,
    default: '/'
  },
  to: {
    type: String,
    default: ''
  },
  replace: Boolean
})
const onClick = () => {
  if (!props.to || !router) return
  props.replace ? router.replace(props.to) : router.push(props.to)
}
</script>

<style scoped lang="less">
.s-breadcrumb__item {
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
}

.s-breadcrumb__inner {
  color: rgba(0, 0, 0, 0.45);
}

.s-breadcrumb__inner.is-link,
.s-breadcrumb__inner a {
  font-weight: 500;
  text-decoration: none;
  transition: color 0.2s cubic-bezier(0.645, 0.045, 0.355, 1);
  color: rgba(0, 0, 0, 0.65);
}

.s-breadcrumb__separator {
  display: inline-flex;
  align-items: center;
  margin: 0 8px;
  color: rgba(0, 0, 0, 0.25);
  font-size: 12px;
}

.s-breadcrumb__item:last-child .s-breadcrumb__separator {
  display: none;
}
</style>
