<template>
  <a-tooltip placement="top" :get-popup-container="getPopupContainer">
    <template #title>
      <span>刷新</span>
    </template>
    <RedoOutlined :style="{ fontSize: '13px', cursor: 'pointer' }" @click="handleRefresh" />
  </a-tooltip>
</template>

<script setup lang="ts">
import { RedoOutlined } from '@ant-design/icons-vue'
import { useTableSettingContext } from '@/components/TableSetting/useTableSetting'

const { actions, getPopupContainer } = useTableSettingContext()

const handleRefresh = () => {
  actions.refresh()
}
</script>
