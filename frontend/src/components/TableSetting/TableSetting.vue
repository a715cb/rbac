<template>
  <div class="table-settings">
    <RefreshSetting />
    <FullScreenSetting />
    <SizeSetting />
    <ColumnSetting />
  </div>
</template>

<script setup lang="ts">
import RefreshSetting from './RefreshSetting.vue'
import FullScreenSetting from './FullScreenSetting.vue'
import SizeSetting from './SizeSetting.vue'
import ColumnSetting from './ColumnSetting.vue'
</script>

<style lang="less" scoped>
.table-settings {
  display: flex;
  align-items: center;
  gap: 16px;

  :deep(.anticon) {
    color: var(--table-settings-icon-color);
    transition: color 0.3s;

    &:hover {
      color: var(--table-settings-icon-hover);
    }
  }

  svg {
    width: 1.3em;
    height: 1.3em;
  }
}
</style>
