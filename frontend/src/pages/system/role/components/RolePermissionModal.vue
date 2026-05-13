<!--
  @文件: RolePermissionModal.vue
  @用途: 角色权限分配弹窗组件，为指定角色分配菜单权限、按钮权限和接口权限
  @描述: 以 Tab 页签形式切换菜单权限、按钮权限和接口权限三种类型，通过树形控件展示权限层级结构并支持勾选操作；
         弹窗打开时并行加载菜单树、按钮树和接口列表，再加载角色已有权限并回显勾选状态；关闭弹窗时清空勾选状态避免残留数据。
  @核心逻辑:
    1. 弹窗打开时并行加载菜单树、按钮树和接口列表，再加载角色已有权限并回显勾选状态
    2. 提交时分别调用菜单权限、按钮权限和接口权限的分配接口
    3. 关闭弹窗时清空勾选状态，避免残留数据
-->
<template>
  <!-- 权限分配弹窗，标题动态显示角色名称 -->
  <a-modal
    :title="`${roleName} - 权限分配`"
    :open="visible"
    :confirm-loading="loading"
    :width="800"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <!-- 权限类型切换：菜单权限 / 按钮权限 / 接口权限 -->
    <a-tabs v-model:active-key="activeTab">
      <!-- 菜单权限树：展示系统菜单层级，支持勾选 -->
      <a-tab-pane key="menus" tab="菜单权限">
        <a-spin :spinning="tabLoading.menus">
          <a-tree
            v-model:checked-keys="menuCheckedKeys"
            checkable
            :tree-data="menuTreeData"
            :field-names="{ children: 'children', title: 'name', key: 'id' }"
            default-expand-all
          />
        </a-spin>
      </a-tab-pane>
      <!-- 按钮权限树：按菜单分组展示按钮，支持勾选 -->
      <a-tab-pane key="buttons" tab="按钮权限">
        <a-spin :spinning="tabLoading.buttons">
          <a-tree
            v-model:checked-keys="buttonCheckedKeys"
            checkable
            :tree-data="buttonTreeData"
            :field-names="{ children: 'children', title: 'name', key: 'id' }"
            default-expand-all
          />
        </a-spin>
      </a-tab-pane>
      <!-- 接口权限树：按分组展示 API 接口，支持勾选 -->
      <a-tab-pane key="apis" tab="接口权限">
        <a-spin :spinning="tabLoading.apis">
          <a-tree
            v-model:checked-keys="apiCheckedKeys"
            checkable
            :tree-data="apiTreeData"
            :field-names="{ children: 'children', title: 'name', key: 'id' }"
            default-expand-all
          />
        </a-spin>
      </a-tab-pane>
    </a-tabs>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { message } from 'ant-design-vue'
import { getApiList } from '@/api/api'
import { getMenuTree } from '@/api/menu'
import { getRoleDetail, assignRoleMenus, assignRoleButtons, assignRoleApis } from '@/api/role'
import type { ApiInfo } from '@/api/api'
import type { MenuInfo } from '@/api/menu'

type TreeNode = { id: number | string; children?: TreeNode[] }
type ButtonTreeNode = { id: number | string; name: string; children?: ButtonTreeNode[] }

/**
 * 递归收集树中所有节点的 id
 * @param tree - 树形数据数组
 * @returns 包含所有节点 id 的 Set 集合
 */
const collectTreeKeys = (tree: TreeNode[]): Set<number | string> => {
  const keys = new Set<number | string>()
  const walk = (nodes: TreeNode[]) => {
    for (const node of nodes) {
      keys.add(node.id)
      if (node.children) walk(node.children)
    }
  }
  walk(tree)
  return keys
}

/**
 * 过滤出树中实际存在的有效 key，剔除已不存在于树中的脏数据
 * @param keys - 待过滤的 key 数组
 * @param tree - 用于校验的树形数据
 * @returns 仅包含树中存在节点的 key 数组
 */
const filterValidKeys = (keys: (number | string)[], tree: TreeNode[]): (number | string)[] => {
  const validKeys = collectTreeKeys(tree)
  return keys.filter((k) => validKeys.has(k))
}

/**
 * 从菜单树构建按钮权限树
 * @description 递归遍历菜单树，保持菜单层级结构，
 *              每个菜单的按钮作为该菜单节点的直接子节点，
 *              子菜单递归构建的按钮子树也作为该菜单节点的子节点，
 *              仅包含有按钮的菜单分支
 * @param menus - 菜单树数据（含 buttons 字段）
 * @returns 按钮权限树数组
 */
const buildButtonTree = (menus: MenuInfo[]): ButtonTreeNode[] => {
  const result: ButtonTreeNode[] = []
  for (const menu of menus) {
    const childNodes: ButtonTreeNode[] = []
    if (menu.buttons && menu.buttons.length > 0) {
      for (const btn of menu.buttons) {
        childNodes.push({ id: Number(btn.id), name: `${btn.name} (${btn.code})` })
      }
    }
    if (menu.children && menu.children.length > 0) {
      const subTree = buildButtonTree(menu.children)
      childNodes.push(...subTree)
    }
    if (childNodes.length > 0) {
      result.push({ id: `menu_${menu.id}`, name: menu.name, children: childNodes })
    }
  }
  return result
}

interface Props {
  visible: boolean
  roleId?: number
  roleName?: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'success'): void
}>()

const loading = ref(false)

const activeTab = ref('menus')

const tabLoading = reactive({ menus: false, buttons: false, apis: false })

const menuTreeData = ref<TreeNode[]>([])

const buttonTreeData = ref<ButtonTreeNode[]>([])

const apiTreeData = ref<
  Array<{
    id: number | string
    name: string
    children?: Array<{ id: number | string; name: string }>
  }>
>([])

const menuCheckedKeys = ref<(number | string)[]>([])
const buttonCheckedKeys = ref<(number | string)[]>([])
const apiCheckedKeys = ref<(number | string)[]>([])

/**
 * 从菜单树提取纯菜单节点（去除 buttons 字段），用于菜单权限树展示
 * @param menus - 含 buttons 字段的菜单树
 * @returns 仅包含 id、name、children 的菜单树
 */
const extractMenuTree = (menus: MenuInfo[]): TreeNode[] => {
  return menus.map((menu) => ({
    id: Number(menu.id),
    name: menu.name,
    children: menu.children ? extractMenuTree(menu.children) : undefined
  }))
}

/**
 * 获取菜单树并同步构建菜单权限树和按钮权限树
 * @description 一次 API 请求获取含 buttons 字段的完整菜单树，
 *              同时提取纯菜单树用于菜单权限 Tab，
 *              构建按钮权限树用于按钮权限 Tab
 */
const fetchMenuAndButtonTree = async () => {
  tabLoading.menus = true
  tabLoading.buttons = true
  try {
    const res = await getMenuTree()
    const tree = res.data.tree
    menuTreeData.value = extractMenuTree(tree)
    buttonTreeData.value = buildButtonTree(tree)
  } catch (error: unknown) {
    if (import.meta.env.DEV)
      console.error('[RolePermissionModal] fetchMenuAndButtonTree failed:', error)
  } finally {
    tabLoading.menus = false
    tabLoading.buttons = false
  }
}

/**
 * 获取接口列表并按分组构建树形数据
 * @description 调用 getApiList 接口获取全部 API 数据，
 *              按 group 字段分组，每组作为父节点，组内接口作为子节点，
 *              子节点名称格式为 "METHOD /path"
 */
const fetchApis = async () => {
  tabLoading.apis = true
  try {
    const res = await getApiList({ limit: 1000 })
    const groups = new Map<string, Array<{ id: number | string; name: string }>>()
    res.data.list.forEach((api: ApiInfo) => {
      const group = api.group || '未分组'
      if (!groups.has(group)) {
        groups.set(group, [])
      }
      groups.get(group)?.push({ id: Number(api.id), name: `${api.method} ${api.path}` })
    })
    apiTreeData.value = Array.from(groups.entries()).map(([name, children], index) => ({
      id: `group_${index}`,
      name,
      children
    }))
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[RolePermissionModal] fetchApis failed:', error)
  } finally {
    tabLoading.apis = false
  }
}

/**
 * 获取角色当前已有的权限并回显到树形控件
 * @description 根据 roleId 获取角色详情，提取 menu_ids、button_ids 和 api_ids，
 *              过滤掉树中不存在的无效 key 后设置勾选状态
 */
const fetchRolePermissions = async () => {
  if (!props.roleId) return
  try {
    const res = await getRoleDetail(props.roleId)
    const menuIds = (res.data.menu_ids || []).map((id: string | number) => Number(id))
    const buttonIds = (res.data.button_ids || []).map((id: string | number) => Number(id))
    const apiIds = (res.data.api_ids || []).map((id: string | number) => Number(id))
    menuCheckedKeys.value = filterValidKeys(menuIds, menuTreeData.value)
    buttonCheckedKeys.value = filterValidKeys(
      buttonIds,
      buttonTreeData.value as unknown as TreeNode[]
    )
    apiCheckedKeys.value = filterValidKeys(apiIds, apiTreeData.value)
  } catch (error: unknown) {
    if (import.meta.env.DEV)
      console.error('[RolePermissionModal] fetchRolePermissions failed:', error)
  }
}

/**
 * 提交权限分配
 * @description 分别调用菜单权限、按钮权限和接口权限的分配接口，
 *              仅提交 number 类型的 key（过滤掉分组虚拟节点），
 *              成功后触发 success 事件并关闭弹窗
 */
const handleSubmit = async () => {
  if (!props.roleId) {
    message.error('角色 ID 无效')
    return
  }

  loading.value = true
  try {
    await assignRoleMenus(
      props.roleId,
      menuCheckedKeys.value.filter((k): k is number => typeof k === 'number')
    )
    await assignRoleButtons(
      props.roleId,
      buttonCheckedKeys.value.filter((k): k is number => typeof k === 'number')
    )
    await assignRoleApis(
      props.roleId,
      apiCheckedKeys.value.filter((k): k is number => typeof k === 'number')
    )
    message.success('权限分配成功')
    emit('success')
    emit('update:visible', false)
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[RolePermissionModal] handleSubmit failed:', error)
  } finally {
    loading.value = false
  }
}

/**
 * 取消/关闭弹窗
 * @description 通知父组件关闭弹窗，并清空勾选状态避免数据残留
 */
const handleCancel = () => {
  emit('update:visible', false)
  menuCheckedKeys.value = []
  buttonCheckedKeys.value = []
  apiCheckedKeys.value = []
}

/**
 * 监听弹窗可见状态变化
 * @description 弹窗打开且 roleId 有效时，清空旧勾选状态，
 *              并行加载菜单树、按钮树和接口列表，完成后加载角色已有权限
 */
watch(
  () => props.visible,
  async (val) => {
    if (val && props.roleId) {
      menuCheckedKeys.value = []
      buttonCheckedKeys.value = []
      apiCheckedKeys.value = []
      await Promise.all([fetchMenuAndButtonTree(), fetchApis()])
      fetchRolePermissions()
    }
  }
)
</script>
