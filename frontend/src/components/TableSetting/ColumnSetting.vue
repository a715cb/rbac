<template>
  <a-tooltip placement="top" :get-popup-container="getPopupContainer">
    <template #title>
      <span>列设置</span>
    </template>
    <a-popover
      placement="bottomLeft"
      trigger="click"
      overlay-class-name="column-setting-popover"
      :get-popup-container="getPopupContainer"
      @open-change="onVisibleChange"
    >
      <template #title>
        <div class="popover-title">
          <a-checkbox
            v-model:checked="checkAll"
            :indeterminate="indeterminate"
            @change="onCheckAllChange"
          >
            列展示
          </a-checkbox>
          <a-button style="float: right" type="link" size="small" @click="handleReset">
            重置
          </a-button>
        </div>
      </template>
      <template #content>
        <div ref="sortRef" class="column-sort-list">
          <div v-for="item in columnList" :key="item.key" class="column-sort-item">
            <HolderOutlined class="move-icon" />
            <a-checkbox
              v-model:checked="item.visible"
              style="margin-right: 10px"
              @change="onCheckChange"
            />
            <span>{{ item.title }}</span>
          </div>
        </div>
      </template>
      <SettingOutlined :style="{ fontSize: '13px', cursor: 'pointer' }" />
    </a-popover>
  </a-tooltip>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import { HolderOutlined, SettingOutlined } from '@ant-design/icons-vue'
import { useTableSettingContext } from '@/components/TableSetting/useTableSetting'
import { useSortable } from '@/composables/useSortable'
import type { ColumnItem } from '@/components/TableSetting/types'
import type { SortableEvent } from 'sortablejs'

const { state, actions, getPopupContainer } = useTableSettingContext()

const checkAll = ref(true)
const sortRef = ref<HTMLElement | null>(null)
let isFirstLoad = false

const columnList = ref<ColumnItem[]>([])

const checkedList = computed(() => {
  return columnList.value.filter((item: ColumnItem) => item.visible !== false)
})

const indeterminate = computed(() => {
  const len = columnList.value.length
  const checkedLen = checkedList.value.length
  return checkedLen > 0 && checkedLen < len
})

const onVisibleChange = async () => {
  if (isFirstLoad) return
  await nextTick()
  if (sortRef.value) {
    useSortable(sortRef.value, {
      handle: '.move-icon',
      onEnd({ newIndex, oldIndex }: SortableEvent) {
        if (newIndex === undefined || oldIndex === undefined) return
        const list = [...columnList.value]
        const currRow = list.splice(oldIndex, 1)[0]
        list.splice(newIndex, 0, currRow)
        columnList.value = list
        actions.setColumns(list)
      }
    })
  }
  isFirstLoad = true
}

const onCheckAllChange = (e: { target: { checked: boolean } }) => {
  const checked = e.target.checked
  columnList.value.forEach((item: ColumnItem) => {
    item.visible = checked
  })
  if (checked) {
    actions.setColumns(columnList.value)
  } else {
    actions.setColumns([])
  }
}

const onCheckChange = () => {
  const len = columnList.value.length
  checkAll.value = checkedList.value.length === len
  actions.setColumns(columnList.value)
}

const handleReset = () => {
  checkAll.value = true
  actions.resetColumns()
  columnList.value = state.columns.map((col: ColumnItem) => ({ ...col }))
}

onMounted(() => {
  columnList.value = state.columns.map((col: ColumnItem) => ({ ...col }))
})
</script>

<style lang="less">
.column-setting-popover {
  .ant-popover-inner-content {
    max-height: 325px;
    overflow-y: auto;
    padding: 0;
  }
}

.column-sort-list {
  .column-sort-item {
    display: flex;
    align-items: center;
    padding: 5px 16px;
    cursor: default;

    .move-icon {
      margin-right: 15px;
      cursor: move;
      color: #999;
      font-size: 12px;

      &:hover {
        color: #333;
      }
    }
  }
}
</style>
