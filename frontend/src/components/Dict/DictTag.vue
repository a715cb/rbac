<template>
  <a-tag v-if="label" :color="color">
    {{ label }}
  </a-tag>
  <span v-else>{{ fallback }}</span>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useDict, getDictLabel } from '@/composables/useDict'

interface Props {
  dictCode: string
  value?: string | number | undefined
  color?: string
  fallback?: string
  limit?: number
}

const props = withDefaults(defineProps<Props>(), {
  value: undefined,
  color: undefined,
  fallback: '--',
  limit: undefined
})

const { options } = useDict({
  code: props.dictCode,
  limit: props.limit
})

const label = computed(() => getDictLabel(options.value, props.value))

defineOptions({
  name: 'DictTag'
})
</script>
