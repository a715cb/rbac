<template>
  <a-modal
    :title="`${roleName} - 权限分配`"
    :open="visible"
    :confirm-loading="loading"
    :width="800"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <a-tabs v-model:active-key="activeTab">
      <a-tab-pane key="menus" tab="菜单权限">
        <a-tree
          v-model:checked-keys="menuCheckedKeys"
          checkable
          :tree-data="menuTreeData"
          :field-names="{ children: 'children', title: 'name', key: 'id' }"
          default-expand-all
        />
      </a-tab-pane>
      <a-tab-pane key="apis" tab="接口权限">
        <a-tree
          v-model:checked-keys="apiCheckedKeys"
          checkable
          :tree-data="apiTreeData"
          :field-names="{ children: 'children', title: 'name', key: 'id' }"
          default-expand-all
        />
      </a-tab-pane>
    </a-tabs>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { message } from 'ant-design-vue'
import { getApiList } from '@/api/api'
import { getRoleDetail, assignRoleMenus, assignRoleApis } from '@/api/role'
import type { ApiInfo } from '@/api/api'
import { useMenuTree } from '@/composables/useTreeData'

type TreeNode = { id: number | string; children?: TreeNode[] }

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

const filterValidKeys = (keys: (number | string)[], tree: TreeNode[]): (number | string)[] => {
  const validKeys = collectTreeKeys(tree)
  return keys.filter((k) => validKeys.has(k))
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
const { menuTreeData, fetchMenuTree: fetchMenus } = useMenuTree()
const apiTreeData = ref<
  Array<{ id: number | string; name: string; children?: Array<{ id: number | string; name: string }> }>
>([])
const menuCheckedKeys = ref<(number | string)[]>([])
const apiCheckedKeys = ref<(number | string)[]>([])

const fetchApis = async () => {
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
  }
}

const fetchRolePermissions = async () => {
  if (!props.roleId) return
  try {
    const res = await getRoleDetail(props.roleId)
    const menuIds = (res.data.menu_ids || []).map((id: string | number) => Number(id))
    const apiIds = (res.data.api_ids || []).map((id: string | number) => Number(id))
    menuCheckedKeys.value = filterValidKeys(menuIds, menuTreeData.value)
    apiCheckedKeys.value = filterValidKeys(apiIds, apiTreeData.value)
  } catch (error: unknown) {
    if (import.meta.env.DEV)
      console.error('[RolePermissionModal] fetchRolePermissions failed:', error)
  }
}

const handleSubmit = async () => {
  if (!props.roleId) {
    message.error('角色 ID 无效')
    return
  }

  loading.value = true
  try {
    await assignRoleMenus(props.roleId, menuCheckedKeys.value.filter((k): k is number => typeof k === 'number'))
    await assignRoleApis(props.roleId, apiCheckedKeys.value.filter((k): k is number => typeof k === 'number'))
    message.success('权限分配成功')
    emit('success')
    emit('update:visible', false)
  } catch (error: unknown) {
    if (import.meta.env.DEV) console.error('[RolePermissionModal] handleSubmit failed:', error)
    // error handled by request interceptor
  } finally {
    loading.value = false
  }
}

const handleCancel = () => {
  emit('update:visible', false)
  menuCheckedKeys.value = []
  apiCheckedKeys.value = []
}

watch(
  () => props.visible,
  async (val) => {
    if (val && props.roleId) {
      menuCheckedKeys.value = []
      apiCheckedKeys.value = []
      await Promise.all([fetchMenus(), fetchApis()])
      fetchRolePermissions()
    }
  }
)
</script>
