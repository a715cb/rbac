import { ref } from 'vue'
import { getMenuTree } from '@/api/menu'
import { getDeptTree } from '@/api/dept'

type TreeNode = { id: number; name: string; children?: TreeNode[] }

const normalizeTreeIds = <T extends { id: string | number; name: string; children?: T[] }>(
  tree: T[]
): TreeNode[] => {
  return tree.map((node) => ({
    ...node,
    id: Number(node.id),
    children: node.children ? normalizeTreeIds(node.children) : undefined
  }))
}

export function useMenuTree() {
  const menuTreeData = ref<TreeNode[]>([])

  const fetchMenuTree = async () => {
    try {
      const res = await getMenuTree()
      menuTreeData.value = normalizeTreeIds(res.data.tree)
    } catch (error) {
      if (import.meta.env.DEV) console.error('[useMenuTree] Fetch menu tree failed:', error)
    }
  }

  return { menuTreeData, fetchMenuTree }
}

export function useDeptTree() {
  const deptTreeData = ref<TreeNode[]>([])

  const fetchDeptTree = async () => {
    try {
      const res = await getDeptTree()
      deptTreeData.value = normalizeTreeIds(res.data.tree)
    } catch (error) {
      if (import.meta.env.DEV) console.error('[useDeptTree] Fetch dept tree failed:', error)
    }
  }

  return { deptTreeData, fetchDeptTree }
}
