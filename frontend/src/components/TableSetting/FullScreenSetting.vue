<template>
  <a-tooltip v-model:open="open" placement="top" :get-popup-container="getPopupContainer">
    <template #title>
      <span>{{ state.isFullscreen ? '退出全屏' : '全屏' }}</span>
    </template>
    <FullscreenExitOutlined
      v-if="state.isFullscreen"
      :style="{ fontSize: '13px', cursor: 'pointer' }"
      @click="handleToggle"
    />
    <FullscreenOutlined
      v-else
      :style="{ fontSize: '13px', cursor: 'pointer' }"
      @click="handleToggle"
    />
  </a-tooltip>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { FullscreenOutlined, FullscreenExitOutlined } from '@ant-design/icons-vue'
import { useTableSettingContext } from '@/components/TableSetting/useTableSetting'

const { state, actions, getPopupContainer } = useTableSettingContext()
const open = ref(false)

const handleToggle = () => {
  actions.toggleFullscreen()
  open.value = false
}
</script>
