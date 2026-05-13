<!--
  @文件: ButtonDetailModal.vue
  @用途: 按钮详情弹窗组件，展示按钮的完整信息
  @描述: 通过 visible 属性控制弹窗显示/隐藏，打开时自动调用后端接口获取按钮详情数据，
         以描述列表形式展示按钮名称、编码、所属菜单、图标、排序、状态、创建时间等字段。
  @核心逻辑:
    1. 监听 visible 变化，弹窗打开时调用 getButtonDetail 获取详情
    2. 以 Descriptions 组件展示按钮完整信息
    3. 状态字段使用 Tag 标签渲染，区分正常/禁用
-->
<template>
  <a-modal
    title="按钮详情"
    :open="visible"
    :footer="null"
    :width="600"
    :confirm-loading="loading"
    @cancel="handleCancel"
  >
    <a-spin :spinning="loading">
      <a-descriptions :column="2" bordered size="small">
        <a-descriptions-item label="按钮名称">
          {{ detailData.name || '--' }}
        </a-descriptions-item>
        <a-descriptions-item label="按钮编码">
          <a-typography-text code>{{ detailData.code || '--' }}</a-typography-text>
        </a-descriptions-item>
        <a-descriptions-item label="所属菜单" :span="2">
          {{ detailData.menu_path || detailData.menu_name || '--' }}
        </a-descriptions-item>
        <a-descriptions-item label="图标">
          <template v-if="detailData.icon">
            <SIcon :type="detailData.icon" :size="16" style="margin-right: 4px" />
            {{ detailData.icon }}
          </template>
          <span v-else>--</span>
        </a-descriptions-item>
        <a-descriptions-item label="排序">
          {{ detailData.sort ?? '--' }}
        </a-descriptions-item>
        <a-descriptions-item label="状态">
          <a-tag :color="detailData.status === 1 ? 'green' : 'red'">
            {{ detailData.status === 1 ? '正常' : '禁用' }}
          </a-tag>
        </a-descriptions-item>
        <a-descriptions-item label="创建时间">
          {{ detailData.create_time || '--' }}
        </a-descriptions-item>
        <a-descriptions-item label="更新时间" :span="2">
          {{ detailData.update_time || '--' }}
        </a-descriptions-item>
      </a-descriptions>
    </a-spin>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { getButtonDetail } from '@/api/button'
import type { ButtonInfo } from '@/api/button'
import SIcon from '@/components/Icon'

interface Props {
  visible: boolean
  record: ButtonInfo | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
}>()

const loading = ref(false)
const detailData = ref<Partial<ButtonInfo>>({})

const fetchDetail = async () => {
  if (!props.record?.id) return
  loading.value = true
  try {
    const res = await getButtonDetail(props.record.id)
    detailData.value = res.data
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[ButtonDetailModal] fetchDetail failed:', error)
  } finally {
    loading.value = false
  }
}

const handleCancel = () => {
  emit('update:visible', false)
}

watch(
  () => props.visible,
  (val) => {
    if (val) {
      fetchDetail()
    } else {
      detailData.value = {}
    }
  }
)
</script>
