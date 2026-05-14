import { ref, type Ref } from 'vue'
import { getMenuTree } from '@/api/menu'
import { getDeptTree } from '@/api/dept'

export type TreeNode = { id: number; name: string; children?: TreeNode[] }

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

/** 模块级缓存：部门树数据，所有页面共享，避免重复请求 */
const cachedDeptTree = ref<TreeNode[]>([])
const deptTreeLoading = ref(false)
let deptTreeLoaded = false

export function useDeptTree(): {
  deptTreeData: Ref<TreeNode[]>
  loading: Ref<boolean>
  fetchDeptTree: (forceRefresh?: boolean) => Promise<void>
  refresh: () => Promise<void>
} {
  const fetchDeptTree = async (forceRefresh = false) => {
    if (deptTreeLoaded && !forceRefresh) {
      return
    }
    deptTreeLoading.value = true
    try {
      const res = await getDeptTree()
      cachedDeptTree.value = normalizeTreeIds(res.data.tree)
      deptTreeLoaded = true
    } catch (error) {
      if (import.meta.env.DEV) console.error('[useDeptTree] Fetch dept tree failed:', error)
    } finally {
      deptTreeLoading.value = false
    }
  }

  const refresh = () => fetchDeptTree(true)

  return { deptTreeData: cachedDeptTree, loading: deptTreeLoading, fetchDeptTree, refresh }
}
