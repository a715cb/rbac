<template>
  <a-list-item>
    <a-list-item-meta>
      <template #title>
        <label v-if="labelFor" :for="labelFor">{{ title }}</label>
        <span v-else>{{ title }}</span>
      </template>
    </a-list-item-meta>
    <template #actions>
      <slot></slot>
    </template>
  </a-list-item>
</template>

<script setup lang="ts">
defineProps({
  title: {
    type: String,
    required: true
  },
  labelFor: {
    type: String,
    default: ''
  }
})
</script>
