<!--
  @文件: DeptTree.vue
  @用途: 在用户管理页面左侧展示部门树形结构，支持搜索过滤和展开/折叠
  @描述: 在用户管理页面左侧展示部门树形结构，支持搜索过滤、展开/折叠全部节点，
    选中部门后向父组件发送选中事件，用于按部门筛选用户列表。页面加载时自动获取部门树数据并默认展开全部节点，
    搜索采用"保留祖先链"策略过滤树节点，选中模式为单选，点击已选中节点可取消选中。
  @核心逻辑:
    1. 页面加载时自动获取部门树数据并默认展开全部节点
    2. 点击部门节点 → emit('select', deptId) → 触发用户列表按部门筛选
    3. 搜索部门 → 递归过滤树节点（保留祖先链），自动展开匹配节点
    4. 展开全部 / 折叠全部 → 快捷操作树节点展开状态
-->
<template>
  <a-card class="dept-tree-card" :bordered="false">
    <!-- 卡片标题栏：左侧"部门"文字 + 右侧展开/折叠按钮 -->
    <template #title>
      <div class="dept-tree-header">
        <span>部门</span>
        <a-space :size="4">
          <!-- 展开全部节点 -->
          <a-tooltip title="展开全部">
            <a-button type="text" size="small" @click="handleExpandAll">
              <PlusSquareOutlined />
            </a-button>
          </a-tooltip>
          <!-- 折叠全部节点 -->
          <a-tooltip title="折叠全部">
            <a-button type="text" size="small" @click="handleCollapseAll">
              <MinusSquareOutlined />
            </a-button>
          </a-tooltip>
        </a-space>
      </div>
    </template>

    <!--
      部门搜索框
      - v-model:value 绑定 searchValue，输入时实时更新过滤结果（通过 computed）
      - @search 在按回车或点击搜索图标时触发，额外展开所有匹配节点
      - allow-clear 允许一键清空搜索词，清空后自动恢复完整树
    -->
    <a-input-search
      v-model:value="searchValue"
      placeholder="搜索部门"
      allow-clear
      class="dept-tree-search"
      @search="handleSearch"
    />

    <!-- 部门树主体区域，加载中时显示旋转动画 -->
    <a-spin :spinning="loading">
      <!--
        a-tree 配置说明：
        - tree-data：绑定 filteredTreeData（经搜索过滤后的数据），非原始 treeData
        - field-names：后端返回 { name, id, children }，需映射为 a-tree 期望的 { title, key, children }
        - expanded-keys：受控展开状态，由 expandedKeys 驱动（覆盖 default-expand-all）
        - selected-keys：受控选中状态，由 selectedKeys 驱动
        - show-icon：启用自定义节点图标（配合 #icon 插槽）
        - block-node：节点占满整行宽度，扩大可点击区域
        - default-expand-all：仅首次渲染生效，后续展开状态由 expandedKeys 完全接管
      -->
      <a-tree
        v-if="treeData.length"
        :tree-data="filteredTreeData"
        :field-names="{ title: 'name', key: 'id', children: 'children' }"
        :expanded-keys="expandedKeys"
        :selected-keys="selectedKeys"
        show-icon
        block-node
        default-expand-all
        @select="handleSelect"
        @expand="handleExpand"
      >
        <!-- 节点图标插槽：展开时显示打开文件夹，折叠时显示关闭文件夹 -->
        <template #icon="{ expanded }">
          <FolderOpenOutlined v-if="expanded" />
          <FolderOutlined v-else />
        </template>
      </a-tree>
      <!-- 接口无数据时显示空状态占位 -->
      <a-empty v-else :image="simpleImage" description="暂无部门数据" />
    </a-spin>
  </a-card>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
  PlusSquareOutlined,
  MinusSquareOutlined,
  FolderOutlined,
  FolderOpenOutlined
} from '@ant-design/icons-vue'
import { Empty } from 'ant-design-vue'
import { getDeptTree } from '@/api/dept'
import type { DeptInfo } from '@/api/dept'

/** Empty 组件的简约图片引用，用于 a-empty 的 image 属性 */
const simpleImage = Empty.PRESENTED_IMAGE_SIMPLE

/** 部门树数据加载状态，控制 a-spin 旋转动画 */
const loading = ref(false)

/**
 * 部门树原始数据（从接口获取，未经搜索过滤）
 * 作为 filteredTreeData 的数据源，搜索仅影响展示不修改原始数据
 */
const treeData = ref<DeptInfo[]>([])

/**
 * 搜索关键词
 * - 输入时通过 filteredTreeData computed 实时过滤树节点
 * - 清空时自动恢复完整树结构
 */
const searchValue = ref('')

/**
 * 当前展开的节点 key 列表
 * - 受控模式：完全由程序控制哪些节点展开
 * - 初始值在 fetchDeptTree 成功后由 collectAllKeys 填充
 * - 用户手动展开/折叠时通过 handleExpand 同步更新
 */
const expandedKeys = ref<number[]>([])

/**
 * 当前选中的节点 key 列表
 * - 单选模式：数组最多包含一个元素
 * - 点击已选中节点时 a-tree 传入空数组，实现取消选中
 */
const selectedKeys = ref<number[]>([])

/**
 * 组件对外事件定义
 * @event select - 选中部门节点时触发，取消选中时传 undefined
 */
const emit = defineEmits<{
  (e: 'select', deptId: number | undefined): void
}>()

/**
 * 递归过滤部门树节点（保留祖先链策略）
 *
 * 过滤规则：
 * 1. 若节点名称包含关键词 → 保留该节点（含其全部原始子节点）
 * 2. 若节点名称不匹配但其子树中存在匹配项 → 保留该节点（仅含过滤后的子节点）
 * 3. 若节点名称不匹配且子树也无匹配项 → 移除该节点
 *
 * 此策略确保匹配节点的祖先链完整，用户能看清匹配项在组织架构中的层级位置。
 *
 * @param nodes - 待过滤的部门树节点数组
 * @param keyword - 搜索关键词，为空时直接返回原始节点
 * @returns 过滤后的部门树节点数组
 */
const filterTree = (nodes: DeptInfo[], keyword: string): DeptInfo[] => {
  if (!keyword) return nodes
  const result: DeptInfo[] = []
  for (const node of nodes) {
    const children = node.children ? filterTree(node.children, keyword) : []
    const selfMatch = node.name.includes(keyword)
    const childMatch = children.length > 0
    if (selfMatch || childMatch) {
      result.push({
        ...node,
        children: childMatch ? children : node.children
      })
    }
  }
  return result
}

/**
 * 计算属性：基于搜索关键词过滤后的部门树数据
 *
 * searchValue 变化时自动重新计算，驱动 a-tree 的 tree-data 更新。
 * 搜索词为空时返回完整原始数据，无额外性能开销。
 */
const filteredTreeData = computed(() => filterTree(treeData.value, searchValue.value))

/**
 * 递归收集树中所有节点的 key
 *
 * 遍历整棵树（含所有层级），收集每个节点的 id。
 * 用于"展开全部"功能和搜索后自动展开匹配结果。
 *
 * @param nodes - 部门树节点数组
 * @returns 所有节点的 ID 数组（深度优先顺序）
 */
const collectAllKeys = (nodes: DeptInfo[]): number[] => {
  const keys: number[] = []
  for (const node of nodes) {
    keys.push(node.id)
    if (node.children?.length) {
      keys.push(...collectAllKeys(node.children))
    }
  }
  return keys
}

/**
 * 获取部门树数据
 *
 * 调用后端接口获取部门树形结构，获取成功后：
 * 1. 更新 treeData 原始数据
 * 2. 默认展开全部节点（收集所有 key 赋值给 expandedKeys）
 *
 * 失败时仅在开发环境打印错误，不向用户弹窗（部门树为辅助功能，不影响主流程）
 */
const fetchDeptTree = async () => {
  loading.value = true
  try {
    const res = await getDeptTree()
    treeData.value = res.data.tree.map((d: DeptInfo) => ({ ...d, id: Number(d.id) }))
    expandedKeys.value = collectAllKeys(treeData.value)
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[DeptTree] fetchDeptTree failed:', error)
  } finally {
    loading.value = false
  }
}

/**
 * 处理树节点选中事件
 *
 * a-tree 的 select 事件参数为选中 key 数组（底层支持多选配置），
 * 本组件仅取第一个元素实现单选效果。
 *
 * 交互行为：
 * - 点击未选中节点 → keys 包含该节点 ID → emit 该 ID
 * - 点击已选中节点 → keys 为空数组 → emit undefined（取消选中）
 *
 * @param keys - 选中的节点 key 数组
 */
const handleSelect = (keys: number[]) => {
  selectedKeys.value = keys
  emit('select', keys.length ? keys[0] : undefined)
}

/**
 * 处理树节点展开/折叠事件
 *
 * 用户手动点击展开/折叠箭头时触发，同步更新 expandedKeys。
 * 由于 a-tree 的 expanded-keys 为受控模式，必须在此回调中更新，
 * 否则展开/折叠操作不会生效。
 *
 * @param keys - 当前展开的节点 key 数组
 */
const handleExpand = (keys: number[]) => {
  expandedKeys.value = keys
}

/** 展开全部节点：收集原始数据中所有节点 key 并赋值给 expandedKeys */
const handleExpandAll = () => {
  expandedKeys.value = collectAllKeys(treeData.value)
}

/** 折叠全部节点：清空 expandedKeys，所有节点收起 */
const handleCollapseAll = () => {
  expandedKeys.value = []
}

/**
 * 处理搜索事件
 *
 * 在按回车或点击搜索图标时触发（非实时输入触发）。
 * 搜索时自动展开过滤结果中的所有节点，方便用户查看匹配项的完整层级关系。
 * 清空搜索词时不触发此方法，由 filteredTreeData computed 自动恢复完整树。
 */
const handleSearch = () => {
  if (searchValue.value) {
    expandedKeys.value = collectAllKeys(filteredTreeData.value)
  }
}

/**
 * 重置选中状态
 *
 * 清空选中节点并通知父组件当前无选中部门。
 * 父组件可通过 ref 调用此方法（已通过 defineExpose 暴露），
 * 典型场景：用户点击"重置搜索"按钮时同步清除部门筛选。
 */
const resetSelection = () => {
  selectedKeys.value = []
  emit('select', undefined)
}

/** 暴露方法供父组件通过 ref 调用 */
defineExpose({ resetSelection })

/** 组件挂载时自动加载部门树数据 */
onMounted(() => {
  fetchDeptTree()
})
</script>

<style lang="less" scoped>
/* 部门树卡片：撑满父容器高度，内容溢出隐藏 */
.dept-tree-card {
  height: 100%;
  overflow: hidden;

  /* 卡片内容区：减少内边距，纵向溢出时滚动 */
  :deep(.ant-card-body) {
    padding: 0 12px 12px;
    overflow-y: auto;
  }

  /* 标题栏：部门文字与操作按钮左右分布 */
  .dept-tree-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  /* 搜索框与树节点之间留出间距 */
  .dept-tree-search {
    margin-bottom: 8px;
  }

  /* 树组件样式：透明背景 + 选中/悬停高亮 */
  :deep(.ant-tree) {
    background: transparent;

    .ant-tree-node-content-wrapper {
      /* 鼠标悬停时使用主题色浅底 */
      &:hover {
        background-color: var(--ant-color-primary-bg);
        transition:
          background-color 0.25s ease,
          transform 0.2s ease;
        transform: translateX(2px);
      }

      /* 选中节点使用主题色背景 + 主题色文字 */
      &.ant-tree-node-selected {
        background-color: var(--ant-color-primary-bg);
        color: var(--ant-color-primary);
        transition:
          background-color 0.2s ease,
          color 0.2s ease;
      }
    }
  }
}
</style>
