<template>
  <a-select
    v-model:value="innerValue"
    :placeholder="placeholder"
    :allow-clear="allowClear"
    :disabled="disabled"
    :loading="dictLoading"
    :mode="mode"
    :style="{ width }"
    v-bind="filteredAttrs"
  >
    <a-select-option v-for="item in dictOptions" :key="item.value" :value="toValueType(item.value)">
      {{ item.label }}
    </a-select-option>
  </a-select>
</template>

<script setup lang="ts">
import { computed, watch, useAttrs } from 'vue'
import { useDict } from '@/composables/useDict'
import type { DictOption } from '@/composables/useDict'

type SelectMode = 'multiple' | 'tags' | undefined

interface Props {
  dictCode: string
  modelValue?: string | number | (string | number)[] | undefined
  valueType?: 'string' | 'number'
  placeholder?: string
  allowClear?: boolean
  disabled?: boolean
  width?: string
  mode?: SelectMode
  limit?: number
  immediate?: boolean
  options?: DictOption[]
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: undefined,
  valueType: 'number',
  placeholder: '请选择',
  allowClear: true,
  disabled: false,
  width: '120px',
  mode: undefined,
  limit: undefined,
  immediate: true,
  options: undefined
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number | (string | number)[] | undefined): void
  (e: 'change', value: string | number | (string | number)[] | undefined): void
}>()

const useDictResult = useDict({
  code: props.dictCode,
  limit: props.limit,
  immediate: props.immediate && !props.options
})

const dictOptions = computed(() => props.options ?? useDictResult.options.value)
const dictLoading = computed(() => (props.options ? false : useDictResult.loading.value))

const toValueType = (val: string): string | number => {
  if (props.valueType === 'number') {
    const num = Number(val)
    return isNaN(num) ? val : num
  }
  return val
}

const toStoreValue = (val: string | number | (string | number)[] | undefined) => {
  if (val === undefined || val === null) return undefined
  if (Array.isArray(val)) {
    return val.map((v) => (props.valueType === 'number' ? Number(v) : String(v)))
  }
  return props.valueType === 'number' ? Number(val) : String(val)
}

const innerValue = computed({
  get: () => props.modelValue,
  set: (val) => {
    const converted = toStoreValue(val)
    emit('update:modelValue', converted)
    emit('change', converted)
  }
})

const attrs = useAttrs()
const filteredAttrs = computed(() => {
  const filtered = { ...attrs }
  delete filtered.value
  delete filtered['onUpdate:value']
  return filtered
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
  name: 'DictSelect',
  inheritAttrs: false
})
</script>
