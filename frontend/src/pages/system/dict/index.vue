<!--
  @文件: index.vue
  @用途: 系统字典管理页面
  @描述: 提供字典类型和字典数据的增删改查功能，左侧展示字典类型列表，右侧展示对应字典数据表格
  @核心逻辑:
    1. 左侧面板：字典类型列表，支持搜索、新增、编辑、删除、启用/禁用
    2. 右侧面板：选中字典类型后展示其下字典数据，支持新增、编辑、删除、状态切换
    3. 选中字典类型变化时自动刷新字典数据列表
    4. 集成 TableSetting 组件，支持表格列配置、刷新、全屏、尺寸调整
-->
<template>
  <div ref="wrapRef" class="page-container">
    <div class="dict-container">
      <!-- 左侧：字典类型列表面板 -->
      <div class="dict-container-left">
        <!-- 搜索栏 -->
        <header class="dict-header">
          <a-input-search
            id="dict-search"
            v-model:value="searchKeyword"
            name="keyword"
            placeholder="请输入字典名称搜索"
            @change="onSearch"
          />
        </header>

        <!-- 字典类型列表 -->
        <main class="dict-main dict-list">
          <a-list size="small" :loading="typeLoading" :data-source="filteredTypeList">
            <template #renderItem="{ item }">
              <a-list-item
                class="dict-list-item"
                :class="{ active: currentType?.id === item.id }"
                @click="handleSelectType(item)"
              >
                <span class="custom-dict-node">
                  <!-- 字典名称，已禁用时显示标识 -->
                  <span class="name">
                    {{ item.name }}
                    <span v-if="item.status === 0" class="desc">(已禁用)</span>
                  </span>
                  <!-- 字典编码，hover 时隐藏并显示操作按钮 -->
                  <span class="type">{{ item.code }}</span>
                  <!-- 操作按钮组：编辑、启用/禁用、删除 -->
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

        <!-- 底部添加按钮 -->
        <footer class="dict-footer">
          <a-button @click="handleAddType">
            <PlusOutlined />
            添加字典
          </a-button>
        </footer>
      </div>

      <!-- 右侧：字典数据面板 -->
      <section class="dict-container-right">
        <!-- 选中字典类型后显示头部信息和操作 -->
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

        <!-- 字典数据表格 -->
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

        <!-- 未选中字典类型时的空状态提示 -->
        <div v-if="!currentType" class="dict-empty">
          <a-empty description="请选择左侧字典类型" />
        </div>
      </section>

      <!-- 字典类型编辑弹窗 -->
      <DictTypeModal
        :visible="typeModalVisible"
        :record="currentEditType"
        @update:visible="typeModalVisible = $event"
        @success="handleTypeModalSuccess"
      />

      <!-- 字典数据编辑弹窗 -->
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

/** 字典类型列表加载状态 */
const typeLoading = ref(false)
/** 字典数据列表加载状态 */
const dataLoading = ref(false)
/** 字典类型列表数据 */
const typeList = ref<DictTypeInfo[]>([])
/** 字典数据列表数据 */
const dataList = ref<DictDataInfo[]>([])
/** 当前选中的字典类型 */
const currentType = ref<DictTypeInfo | null>(null)
/** 字典类型搜索关键词 */
const searchKeyword = ref('')
/** 页面容器引用，用于 TableSetting 定位弹窗 */
const wrapRef = ref<HTMLElement | null>(null)

/** 字典类型编辑弹窗可见性 */
const typeModalVisible = ref(false)
/** 字典数据编辑弹窗可见性 */
const dataModalVisible = ref(false)
/** 当前编辑的字典类型记录（null 表示新增） */
const currentEditType = ref<DictTypeInfo | null>(null)
/** 当前编辑的字典数据记录（null 表示新增） */
const currentEditData = ref<DictDataInfo | null>(null)

/** 根据搜索关键词过滤字典类型列表 */
const filteredTypeList = computed(() => {
  if (!searchKeyword.value) return typeList.value
  return typeList.value.filter((item) => item.name.includes(searchKeyword.value))
})

/** 字典数据表格列配置 */
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
    /** 状态列：渲染为 Switch 开关，切换时调用状态变更接口 */
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
    /** 操作列：渲染编辑按钮和删除确认按钮 */
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

/** 初始化表格设置（列配置、刷新回调、容器引用） */
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

/** 创建表格设置上下文，供 TableSetting 子组件访问状态和操作方法 */
createTableSettingContext({
  state: tableSettingState,
  actions: {
    /** 刷新字典数据列表 */
    refresh: () => fetchDataList(),
    /** 切换全屏模式 */
    toggleFullscreen: () => {
      tableSettingState.isFullscreen = !tableSettingState.isFullscreen
    },
    /** 切换表格尺寸 */
    changeSize: (size) => {
      tableSettingState.size = size
    },
    /** 设置表格列配置 */
    setColumns: (columns) => {
      tableSettingState.columns = columns
    },
    /** 重置表格列为默认配置 */
    resetColumns: () => {
      tableSettingState.columns = columnItems.map((col) => ({ ...col }))
    }
  },
  wrapRef: settingWrapRef,
  getVisibleColumns,
  getPopupContainer
})

/** 根据列可见性设置过滤后的表格列，用于 a-table 渲染 */
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

/**
 * 获取字典类型列表
 * @param isInit - 是否为初始化加载，为 true 时自动选中第一个字典类型
 * @description 获取字典类型列表后，根据当前选中状态更新 currentType：
 *   - 初始化时自动选中第一项
 *   - 非初始化时尝试保持当前选中项，若已被删除则回退到第一项
 */
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

/**
 * 获取字典数据列表
 * @description 根据当前选中的字典类型 ID 获取对应的字典数据，未选中类型时清空列表
 */
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

/** 搜索输入变更回调（搜索逻辑由 computed filteredTypeList 驱动，此处为空实现） */
const onSearch = () => {}

/**
 * 选中字典类型
 * @param item - 选中的字典类型信息
 */
const handleSelectType = (item: DictTypeInfo) => {
  currentType.value = item
}

/** 监听当前选中字典类型变化，自动刷新字典数据列表 */
watch(currentType, () => {
  fetchDataList()
})

/** 打开新增字典类型弹窗 */
const handleAddType = () => {
  currentEditType.value = null
  typeModalVisible.value = true
}

/**
 * 打开编辑字典类型弹窗
 * @param item - 待编辑的字典类型信息
 */
const handleEditType = (item: DictTypeInfo) => {
  currentEditType.value = item
  typeModalVisible.value = true
}

/**
 * 删除字典类型（含确认弹窗）
 * @param item - 待删除的字典类型信息
 * @description 删除成功后，若删除的是当前选中项则清空选中状态，并刷新类型列表
 */
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

/**
 * 切换字典类型启用/禁用状态（含确认弹窗）
 * @param item - 待切换状态的字典类型信息
 * @description 根据当前状态取反，确认后调用状态变更接口并刷新列表
 */
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

/** 打开新增字典数据弹窗 */
const handleAddData = () => {
  currentEditData.value = null
  dataModalVisible.value = true
}

/**
 * 打开编辑字典数据弹窗
 * @param item - 待编辑的字典数据信息
 */
const handleEditData = (item: DictDataInfo) => {
  currentEditData.value = item
  dataModalVisible.value = true
}

/**
 * 删除字典数据
 * @param item - 待删除的字典数据信息
 * @description 删除成功后刷新字典数据列表
 */
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

/**
 * 切换字典数据启用/禁用状态
 * @param item - 待切换状态的字典数据信息
 * @param checked - Switch 开关的新状态，true 为启用，false 为禁用
 * @description 变更成功或失败后均刷新字典数据列表以保持数据一致性
 */
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

/** 字典类型弹窗操作成功回调，刷新字典类型列表 */
const handleTypeModalSuccess = () => {
  fetchTypeList()
}

/** 字典数据弹窗操作成功回调，刷新字典数据列表 */
const handleDataModalSuccess = () => {
  fetchDataList()
}

/** 页面挂载时初始化加载字典类型列表 */
onMounted(() => {
  fetchTypeList(true)
})
</script>

<style lang="less" scoped>
/* 页面容器：撑满父级高度 */
.page-container {
  height: 100%;

  /* 全屏模式：固定定位覆盖整个视口 */
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

/* 字典容器：左右分栏布局 */
.dict-container {
  display: flex;
  height: 100%;
  background: var(--ant-color-bg-container, #fff);
  border-radius: var(--ant-border-radius, 8px);
  overflow: hidden;
}

/* 左侧面板：固定宽度 300px，纵向排列（搜索-列表-底部按钮） */
.dict-container-left {
  flex: 0 0 300px;
  display: flex;
  flex-direction: column;
  height: 100%;
  border-right: 1px solid var(--ant-color-border-secondary, #f0f0f0);
  overflow: hidden;
}

/* 搜索栏：不收缩，固定内边距 */
.dict-header {
  flex-shrink: 0;
  padding: 13px 15px;
}

/* 列表主区域：自适应剩余空间，内容溢出滚动 */
.dict-main {
  flex: 1;
  overflow: auto;
  padding: 0;
}

/* 底部按钮区：居中排列，不收缩，上边框分隔 */
.dict-footer {
  display: flex;
  justify-content: center;
  flex-shrink: 0;
  padding: 12px 20px;
  border-top: 1px solid var(--ant-color-border-secondary, #f0f0f0);
}

/* 右侧面板：自适应剩余宽度，纵向排列 */
.dict-container-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  padding: 16px;
  overflow: auto;
}

/* 右侧头部信息区：标题与操作按钮两端对齐 */
.dict-header-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: var(--ant-color-text, rgba(0, 0, 0, 0.88));
  font-size: 18px;
  font-weight: 500;

  /* 字典编码标签：微调垂直位置和左间距 */
  .type-tag {
    position: relative;
    bottom: 3px;
    margin-left: 10px;
  }

  /* 头部操作按钮组：水平排列，间距 12px */
  .dict-header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
  }
}

/* 空状态区域：垂直水平居中，撑满剩余空间 */
.dict-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 1;
}

/* 字典类型列表项样式 */
.dict-list {
  /* 列表项：指针手型，紧凑内边距，去除默认边框 */
  .dict-list-item {
    cursor: pointer;
    padding: 8px 20px;
    border: none;

    /* 非选中项 hover 效果：浅蓝背景 */
    &:not(.active):hover {
      background: var(--ant-color-primary-bg, #e6f4ff);
    }
  }

  /* 自定义节点：名称、编码、操作按钮三端对齐 */
  .custom-dict-node {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;

    /* 字典名称 */
    .name {
      color: var(--ant-color-text, rgba(0, 0, 0, 0.88));
    }

    /* 字典编码：小字号灰色显示 */
    .type {
      font-size: 12px;
      color: #999;
    }

    /* 已禁用标识：红色小字号 */
    .desc {
      color: #f62d51;
      margin-left: 5px;
      font-size: 12px;
    }

    /* 操作按钮组：默认隐藏，hover 时显示替换编码位置 */
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

    /* hover 时：显示操作按钮，隐藏字典编码 */
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

/* 选中状态：浅蓝背景 + 名称高亮为主题色 */
.active {
  background-color: var(--ant-color-primary-bg, #e6f4ff);

  .custom-dict-node .name {
    color: var(--ant-color-primary, #1677ff);
  }
}

/* 暗色主题适配：操作图标 hover 变白色 */
[data-theme='dark'] {
  .custom-dict-node {
    .action :deep(.anticon):hover {
      color: #fff;
    }
  }
}
</style>
