<template>
  <div :class="prefixCls">
    <a-input-search
      v-model:value="searchValue"
      placeholder="搜索图标名称"
      allow-clear
      style="margin-bottom: 12px"
    />
    <a-tabs v-model:activeKey="currentTab" @change="handleTabChange">
      <a-tab-pane v-for="group in filteredIcons" :key="group.key" :tab="group.title">
        <ul v-if="group.icons.length" class="icon-list">
          <li
            v-for="icon in group.icons"
            :key="`${group.key}-${icon}`"
            class="icon-item"
            :class="{ active: selectedIcon === icon }"
            :title="icon"
            @click="handleSelectedIcon(icon)"
          >
            <s-icon :type="icon" :style="{ fontSize: '22px' }" />
          </li>
        </ul>
        <a-empty v-else :image="simpleImage" description="未找到匹配图标" />
      </a-tab-pane>
    </a-tabs>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { Empty } from 'ant-design-vue'
import SIcon from '../SIcon.vue'
import icons from './icons'

interface IconGroup {
  key: string
  title: string
  icons: string[]
}

interface Props {
  prefixCls?: string
  value?: string
}

const props = withDefaults(defineProps<Props>(), {
  prefixCls: 's-icon-selector',
  value: ''
})

const emit = defineEmits<{
  (e: 'change', icon: string): void
  (e: 'update:value', icon: string): void
}>()

const simpleImage = Empty.PRESENTED_IMAGE_SIMPLE

const selectedIcon = ref(props.value || '')
const currentTab = ref('directional')
const searchValue = ref('')

const filteredIcons = computed<IconGroup[]>(() => {
  const keyword = searchValue.value.trim().toLowerCase()
  if (!keyword) return icons
  return icons.map((group: IconGroup) => ({
    ...group,
    icons: group.icons.filter((icon: string) => icon.toLowerCase().includes(keyword))
  }))
})

const handleSelectedIcon = (icon: string): void => {
  selectedIcon.value = icon
  emit('change', icon)
  emit('update:value', icon)
}

const handleTabChange = (activeKey: string | number): void => {
  currentTab.value = activeKey.toString()
}

const autoSwitchTab = (): void => {
  icons.some(
    (item: IconGroup) =>
      item.icons.some((icon: string) => icon === props.value) && (currentTab.value = item.key)
  )
}

watch(
  () => props.value,
  (val: string) => {
    selectedIcon.value = val || ''
    autoSwitchTab()
  }
)

onMounted(() => {
  if (props.value) {
    autoSwitchTab()
  }
})
</script>

<style lang="less" scoped>
.icon-list {
  list-style: none;
  padding: 0;
  overflow-y: auto;
  height: 320px;

  .icon-item {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    margin: 3px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
      color: var(--ant-color-primary, #1890ff);
      background-color: var(--ant-color-primary-bg, #e6f7ff);
    }

    &.active {
      color: #fff;
      background-color: var(--ant-color-primary, #1890ff);
    }
  }
}

[data-theme='dark'] {
  .icon-item {
    &:hover {
      background-color: #111b26;
    }

    &.active {
      background-color: var(--ant-color-primary, #1890ff);
    }
  }
}
</style>
