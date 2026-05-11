<template>
  <div ref="wrapRef" class="page-container">
    <div class="dict-container">
      <div class="dict-container-left">
        <header class="dict-header">
          <a-input-search
            id="dict-search"
            v-model:value="searchKeyword"
            name="keyword"
            placeholder="请输入字典名称搜索"
            @change="onSearch"
          />
        </header>
        <main class="dict-main dict-list">
          <a-list size="small" :loading="typeLoading" :data-source="filteredTypeList">
            <template #renderItem="{ item }">
              <a-list-item
                class="dict-list-item"
                :class="{ active: currentType?.id === item.id }"
                @click="handleSelectType(item)"
              >
                <span class="custom-dict-node">
                  <span class="name">
                    {{ item.name }}
                    <span v-if="item.status === 0" class="desc">(已禁用)</span>
                  </span>
                  <span class="type">{{ item.code }}</span>
                  <span class="action">
                    <a-tooltip title="编辑">
                      <EditOutlined @click.stop="handleEditType(item)" />
                    </a-tooltip>
                    <a-tooltip :title="item.status === 0 ? '启用' : '禁用'">
                      <component
                        :is="item.status === 0 ? CheckCircleOutlined : StopOutlined"
                        @click.stop="handleToggleTypeStatus(item)"
                      />
                    </a-tooltip>
                    <a-tooltip title="删除">
                      <DeleteOutlined @click.stop="handleDeleteType(item)" />
                    </a-tooltip>
                  </span>
                </span>
              </a-list-item>
            </template>
          </a-list>
        </main>
        <footer class="dict-footer">
          <a-button @click="handleAddType">
            <PlusOutlined />
            添加字典
          </a-button>
        </footer>
      </div>

      <section class="dict-container-right">
        <div v-if="currentType" class="dict-right-header">
          <div class="dict-header-info">
            <span>
              {{ currentType.name }}
              <a-tag class="type-tag" color="purple">{{ currentType.code }}</a-tag>
            </span>
            <div class="dict-header-actions">
              <a-button type="primary" @click="handleAddData">
                <PlusOutlined />
                添加
              </a-button>
              <TableSetting />
            </div>
          </div>
          <a-divider style="margin: 12px 0" />
        </div>

        <a-table
          v-if="currentType"
          :columns="visibleColumns"
          :data-source="dataList"
          :loading="dataLoading"
          :pagination="false"
          :size="tableSettingState.size"
          row-key="id"
          table-layout="fixed"
        />

        <div v-if="!currentType" class="dict-empty">
          <a-empty description="请选择左侧字典类型" />
        </div>
      </section>

      <DictTypeModal
        :visible="typeModalVisible"
        :record="currentEditType"
        @update:visible="typeModalVisible = $event"
        @success="handleTypeModalSuccess"
      />

      <DictDataModal
        :visible="dataModalVisible"
        :record="currentEditData"
        :dict-type-id="Number(currentType?.id) || 0"
        @update:visible="dataModalVisible = $event"
        @success="handleDataModalSuccess"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, h } from 'vue'
import { message, Modal, Switch, Space, Button, Popconfirm } from 'ant-design-vue'
import {
  EditOutlined,
  DeleteOutlined,
  PlusOutlined,
  CheckCircleOutlined,
  StopOutlined
} from '@ant-design/icons-vue'
import {
  getDictTypeList,
  getDictDataList,
  deleteDictType,
  deleteDictData,
  changeDictTypeStatus,
  changeDictDataStatus
} from '@/api/dict'
import type { DictTypeInfo, DictDataInfo } from '@/api/dict'
import DictTypeModal from './components/DictTypeModal.vue'
import DictDataModal from './components/DictDataModal.vue'
import TableSetting from '@/components/TableSetting/TableSetting.vue'
import {
  useTableSetting,
  createTableSettingContext
} from '@/components/TableSetting/useTableSetting'
import type { ColumnItem } from '@/components/TableSetting/types'

const typeLoading = ref(false)
const dataLoading = ref(false)
const typeList = ref<DictTypeInfo[]>([])
const dataList = ref<DictDataInfo[]>([])
const currentType = ref<DictTypeInfo | null>(null)
const searchKeyword = ref('')
const wrapRef = ref<HTMLElement | null>(null)

const typeModalVisible = ref(false)
const dataModalVisible = ref(false)
const currentEditType = ref<DictTypeInfo | null>(null)
const currentEditData = ref<DictDataInfo | null>(null)

const filteredTypeList = computed(() => {
  if (!searchKeyword.value) return typeList.value
  return typeList.value.filter((item) => item.name.includes(searchKeyword.value))
})

const columnItems: ColumnItem[] = [
  { key: 'id', title: 'ID', dataIndex: 'id', width: 80 },
  { key: 'label', title: '字典标签', dataIndex: 'label' },
  { key: 'value', title: '字典键值', dataIndex: 'value' },
  { key: 'sort', title: '排序', dataIndex: 'sort', width: 80 },
  {
    key: 'status',
    title: '状态',
    dataIndex: 'status',
    width: 100,
    align: 'center',
    customRender: ({ record }: { record: DictDataInfo }) =>
      h(Switch, {
        checked: record.status === 1,
        onChange: (checked: string | number | boolean) => {
          handleDataStatusChange(record, Boolean(checked))
        }
      })
  },
  { key: 'remark', title: '备注', dataIndex: 'remark' },
  {
    key: 'action',
    title: '操作',
    dataIndex: 'action',
    width: 200,
    customRender: ({ record }: { record: DictDataInfo }) =>
      h(Space, null, () => [
        h(Button, { type: 'link', size: 'small', onClick: () => handleEditData(record) }, () => [
          h(EditOutlined),
          ' 编辑'
        ]),
        h(
          Popconfirm,
          {
            title: '确定要删除该字典数据吗？',
            onConfirm: () => handleDeleteData(record)
          },
          () => [
            h(Button, { type: 'link', danger: true, size: 'small' }, () => [
              h(DeleteOutlined),
              ' 删除'
            ])
          ]
        )
      ])
  }
]

const {
  state: tableSettingState,
  getVisibleColumns,
  getPopupContainer,
  wrapRef: settingWrapRef
} = useTableSetting({
  columns: columnItems,
  onRefresh: () => {
    fetchDataList()
  },
  wrapRef
})

createTableSettingContext({
  state: tableSettingState,
  actions: {
    refresh: () => fetchDataList(),
    toggleFullscreen: () => {
      tableSettingState.isFullscreen = !tableSettingState.isFullscreen
    },
    changeSize: (size) => {
      tableSettingState.size = size
    },
    setColumns: (columns) => {
      tableSettingState.columns = columns
    },
    resetColumns: () => {
      tableSettingState.columns = columnItems.map((col) => ({ ...col }))
    }
  },
  wrapRef: settingWrapRef,
  getVisibleColumns,
  getPopupContainer
})

const visibleColumns = computed(() => {
  return getVisibleColumns.value.map((col) => ({
    title: col.title,
    dataIndex: col.dataIndex,
    key: col.key,
    width: col.width,
    align: col.align as 'left' | 'center' | 'right' | undefined,
    ellipsis: col.ellipsis,
    customRender: col.customRender
  }))
})

const fetchTypeList = async (isInit = false) => {
  typeLoading.value = true
  try {
    const res = await getDictTypeList()
    typeList.value = res.data
    if (isInit && typeList.value.length > 0) {
      currentType.value = typeList.value[0]
    } else if (currentType.value) {
      const updated = typeList.value.find((item) => item.id === currentType.value!.id)
      if (updated) {
        currentType.value = updated
      } else if (typeList.value.length > 0) {
        currentType.value = typeList.value[0]
      } else {
        currentType.value = null
      }
    }
  } catch (error) {
    if (import.meta.env.DEV) console.error('[Dict] Fetch type list failed:', error)
    // error handled by request interceptor
  } finally {
    typeLoading.value = false
  }
}

const fetchDataList = async () => {
  if (!currentType.value) {
    dataList.value = []
    return
  }
  dataLoading.value = true
  try {
    const res = await getDictDataList({ dict_type_id: currentType.value.id })
    dataList.value = res.data
  } catch (error) {
    if (import.meta.env.DEV) console.error('[Dict] Fetch data list failed:', error)
    // error handled by request interceptor
  } finally {
    dataLoading.value = false
  }
}

const onSearch = () => {}

const handleSelectType = (item: DictTypeInfo) => {
  currentType.value = item
}

watch(currentType, () => {
  fetchDataList()
})

const handleAddType = () => {
  currentEditType.value = null
  typeModalVisible.value = true
}

const handleEditType = (item: DictTypeInfo) => {
  currentEditType.value = item
  typeModalVisible.value = true
}

const handleDeleteType = (item: DictTypeInfo) => {
  Modal.confirm({
    title: '提示',
    content: `确定要删除「${item.name}」字典类型吗？删除后该类型下的所有字典数据也将被删除。`,
    centered: true,
    onOk: async () => {
      try {
        await deleteDictType(item.id)
        message.success('删除成功')
        if (currentType.value?.id === item.id) {
          currentType.value = null
        }
        fetchTypeList()
      } catch (error) {
        if (import.meta.env.DEV) console.error('[Dict] Delete dict type failed:', error)
        // error handled by request interceptor
      }
    }
  })
}

const handleToggleTypeStatus = (item: DictTypeInfo) => {
  const newStatus = item.status === 1 ? 0 : 1
  const msg = newStatus === 1 ? '启用' : '禁用'
  Modal.confirm({
    title: '提示',
    content: `确定要${msg}「${item.name}」字典类型吗？`,
    centered: true,
    onOk: async () => {
      try {
        await changeDictTypeStatus(item.id, newStatus)
        message.success(`${msg}成功`)
        fetchTypeList()
      } catch (error) {
        if (import.meta.env.DEV) console.error('[Dict] Toggle dict type status failed:', error)
        // error handled by request interceptor
      }
    }
  })
}

const handleAddData = () => {
  currentEditData.value = null
  dataModalVisible.value = true
}

const handleEditData = (item: DictDataInfo) => {
  currentEditData.value = item
  dataModalVisible.value = true
}

const handleDeleteData = async (item: DictDataInfo) => {
  try {
    await deleteDictData(item.id)
    message.success('删除成功')
    fetchDataList()
  } catch (error) {
    if (import.meta.env.DEV) console.error('[Dict] Delete dict data failed:', error)
    // error handled by request interceptor
  }
}

const handleDataStatusChange = async (item: DictDataInfo, checked: boolean) => {
  const newStatus = checked ? 1 : 0
  const msg = newStatus === 1 ? '启用' : '禁用'
  try {
    await changeDictDataStatus(item.id, newStatus)
    message.success(`${msg}成功`)
    fetchDataList()
  } catch (error) {
    if (import.meta.env.DEV) console.error('[Dict] Toggle dict data status failed:', error)
    fetchDataList()
  }
}

const handleTypeModalSuccess = () => {
  fetchTypeList()
}

const handleDataModalSuccess = () => {
  fetchDataList()
}

onMounted(() => {
  fetchTypeList(true)
})
</script>

<style lang="less" scoped>
.page-container {
  height: 100%;

  &.fullscreen-table {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    background: var(--ant-color-bg-container, #fff);
    padding: 16px;
    display: flex;
    flex-direction: column;
    overflow: visible;
  }
}

.dict-container {
  display: flex;
  height: 100%;
  background: var(--ant-color-bg-container, #fff);
  border-radius: var(--ant-border-radius, 8px);
  overflow: hidden;
}

.dict-container-left {
  flex: 0 0 300px;
  display: flex;
  flex-direction: column;
  height: 100%;
  border-right: 1px solid var(--ant-color-border-secondary, #f0f0f0);
  overflow: hidden;
}

.dict-header {
  flex-shrink: 0;
  padding: 13px 15px;
}

.dict-main {
  flex: 1;
  overflow: auto;
  padding: 0;
}

.dict-footer {
  display: flex;
  justify-content: center;
  flex-shrink: 0;
  padding: 12px 20px;
  border-top: 1px solid var(--ant-color-border-secondary, #f0f0f0);
}

.dict-container-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 16px;
  overflow: auto;
}

.dict-header-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: var(--ant-color-text, rgba(0, 0, 0, 0.88));
  font-size: 18px;
  font-weight: 500;

  .type-tag {
    position: relative;
    bottom: 3px;
    margin-left: 10px;
  }

  .dict-header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
  }
}

.dict-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 1;
}

.dict-list {
  .dict-list-item {
    cursor: pointer;
    padding: 8px 20px;
    border: none;

    &:not(.active):hover {
      background: var(--ant-color-primary-bg, #e6f4ff);
    }
  }

  .custom-dict-node {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;

    .name {
      color: var(--ant-color-text, rgba(0, 0, 0, 0.88));
    }

    .type {
      font-size: 12px;
      color: #999;
    }

    .desc {
      color: #f62d51;
      margin-left: 5px;
      font-size: 12px;
    }

    .action {
      display: none;
      min-width: 72px;
      color: var(--ant-color-text-secondary, rgba(0, 0, 0, 0.45));

      :deep(.anticon) {
        margin: 0 5px;
        cursor: pointer;

        &:hover {
          color: var(--ant-color-primary, #1677ff);
        }
      }
    }

    &:hover {
      .action {
        display: block;
      }

      .type {
        display: none;
      }
    }
  }
}

.active {
  background-color: var(--ant-color-primary-bg, #e6f4ff);

  .custom-dict-node .name {
    color: var(--ant-color-primary, #1677ff);
  }
}

[data-theme='dark'] {
  .custom-dict-node {
    .action :deep(.anticon):hover {
      color: #fff;
    }
  }
}
</style>
