<template>
  <a-radio-group v-model:value="innerValue" :disabled="disabled" v-bind="filteredAttrs">
    <a-radio v-for="item in dictOptions" :key="item.value" :value="toValueType(item.value)">
      {{ item.label }}
    </a-radio>
  </a-radio-group>
</template>

<script setup lang="ts">
import { computed, watch, useAttrs } from 'vue'
import { useDict } from '@/composables/useDict'
import type { DictOption } from '@/composables/useDict'

interface Props {
  dictCode: string
  modelValue?: string | number | undefined
  valueType?: 'string' | 'number'
  disabled?: boolean
  limit?: number
  immediate?: boolean
  options?: DictOption[]
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: undefined,
  valueType: 'number',
  disabled: false,
  limit: undefined,
  immediate: true,
  options: undefined
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | undefined): void
  (e: 'change', value: string | number | undefined): void
}>()

const useDictResult = useDict({
  code: props.dictCode,
  limit: props.limit,
  immediate: props.immediate && !props.options
})

const dictOptions = computed(() => props.options ?? useDictResult.options.value)

const toValueType = (val: string): string | number => {
  if (props.valueType === 'number') {
    const num = Number(val)
    return isNaN(num) ? val : num
  }
  return val
}

const innerValue = computed({
  get: () => props.modelValue,
  set: (val) => {
    const converted =
      val === undefined || val === null
        ? undefined
        : props.valueType === 'number'
          ? Number(val)
          : String(val)
    emit('update:modelValue', converted)
    emit('change', converted)
  }
})

const attrs = useAttrs()
const filteredAttrs = computed(() => {
  const { value, 'onUpdate:value': onUpdateValue, ...rest } = attrs as Record<string, unknown>
  return rest
})

watch(
  () => props.options,
  (newOptions) => {
    if (newOptions) {
      useDictResult.options.value = newOptions
    }
  }
)

defineOptions({
  name: 'DictRadio',
  inheritAttrs: false
})
</script>
