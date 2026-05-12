<template>
  <a-input
    readonly
    :style="{ width }"
    placeholder="请选择图标"
    class="s-icon-select"
    :value="currentSelect"
    allow-clear
    @clear="handleClear"
  >
    <template #prefix>
      <s-icon v-if="currentSelect" :type="currentSelect" class="icon-preview" />
    </template>
    <template #addonAfter>
      <span class="select-btn" @click="setVisible(true)">选择</span>
    </template>
  </a-input>

  <a-modal
    title="选择图标"
    :width="800"
    :footer="null"
    :open="visible"
    centered
    @cancel="setVisible(false)"
  >
    <icon-selector v-model:value="currentSelect" />
  </a-modal>
</template>

<script setup lang="ts">
import { ref, watchEffect, watch } from 'vue'
import IconSelector from './components/IconSelector.vue'
import SIcon from './SIcon.vue'

interface Props {
  modelValue?: string
  width?: string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  width: '100%'
})

const emit = defineEmits<{
  (e: 'change', value: string): void
  (e: 'update:modelValue', value: string): void
}>()

const currentSelect = ref('')
const visible = ref(false)

const setVisible = (val: boolean) => {
  visible.value = val
}

const handleClear = () => {
  currentSelect.value = ''
}

watchEffect(() => {
  currentSelect.value = props.modelValue
})

watch(
  () => currentSelect.value,
  (v) => {
    visible.value = false
    emit('update:modelValue', v)
    emit('change', v)
  }
)
</script>

<style lang="less" scoped>
.s-icon-select {
  .icon-preview {
    font-size: 16px;
    margin-right: 4px;
  }

  :deep(.ant-input-group-addon) {
    padding: 0;
  }

  .select-btn {
    display: inline-block;
    padding: 0 12px;
    cursor: pointer;
    font-size: 13px;
    color: var(--ant-color-primary, #1890ff);

    &:hover {
      opacity: 0.85;
    }
  }
}
</style>
