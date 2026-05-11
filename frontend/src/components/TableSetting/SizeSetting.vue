<template>
  <a-tooltip placement="top" :get-popup-container="getPopupContainer">
    <template #title>
      <span>密度</span>
    </template>
    <a-dropdown placement="bottom" :trigger="['click']" :get-popup-container="getPopupContainer">
      <ColumnHeightOutlined :style="{ fontSize: '13px', cursor: 'pointer' }" />
      <template #overlay>
        <a-menu v-model:selected-keys="selectedKeys" @click="handleClick">
          <a-menu-item key="default">
            <span>默认</span>
          </a-menu-item>
          <a-menu-item key="middle">
            <span>中等</span>
          </a-menu-item>
          <a-menu-item key="small">
            <span>紧凑</span>
          </a-menu-item>
        </a-menu>
      </template>
    </a-dropdown>
  </a-tooltip>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { ColumnHeightOutlined } from '@ant-design/icons-vue'
import { useTableSettingContext } from '@/components/TableSetting/useTableSetting'
import type { SizeType } from '@/components/TableSetting/types'

const { state, actions, getPopupContainer } = useTableSettingContext()
const selectedKeys = ref<string[]>([state.size])

watch(
  () => state.size,
  (val) => {
    selectedKeys.value = [val]
  }
)

const handleClick = ({ key }: { key: string }) => {
  actions.changeSize(key as SizeType)
}
</script>
