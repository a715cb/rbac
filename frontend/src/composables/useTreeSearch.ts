import { ref, type Ref } from 'vue'

export interface TreeNode {
  id: string | number
  children?: TreeNode[]
  [key: string]: any
}

const escapeHtml = (str: string): string => {
  const map: Record<string, string> = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  }
  return str.replace(/[&<>"']/g, (char) => map[char])
}

export function useTreeSearch(searchField: string = 'name') {
  const searchText = ref<string>('')
  const expandedRowKeys = ref<(string | number)[]>([])

  const highlightText = (text: string, keyword: string): string => {
    if (!keyword || !text) return escapeHtml(text)
    const escapedText = escapeHtml(text)
    const escapedKeyword = escapeHtml(keyword)
    const escapedForRegex = escapedKeyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
    const regex = new RegExp(`(${escapedForRegex})`, 'gi')
    return escapedText.replace(regex, '<span style="color:#ff5500">$1</span>')
  }

  const collectMatchedAndAncestors = (nodes: TreeNode[], keyword: string): Set<string | number> => {
    const ancestorSet = new Set<string | number>()
    const walk = (list: TreeNode[], path: (string | number)[]) => {
      for (const item of list) {
        const currentPath = [...path, item.id]
        if (
          keyword &&
          typeof item[searchField] === 'string' &&
          item[searchField].toLowerCase().includes(keyword.toLowerCase())
        ) {
          currentPath.forEach((id) => ancestorSet.add(id))
        }
        if (item.children?.length) {
          walk(item.children, currentPath)
        }
      }
    }
    walk(nodes, [])
    return ancestorSet
  }

  const doSearch = (keyword: string, treeData: Ref<TreeNode[]> | TreeNode[]) => {
    searchText.value = keyword
    const data = Array.isArray(treeData) ? treeData : treeData.value
    if (keyword.trim()) {
      const ancestors = collectMatchedAndAncestors(data, keyword)
      expandedRowKeys.value = Array.from(ancestors)
    } else {
      expandedRowKeys.value = []
    }
  }

  const resetSearch = () => {
    searchText.value = ''
    expandedRowKeys.value = []
  }

  return {
    searchText,
    expandedRowKeys,
    highlightText,
    doSearch,
    resetSearch,
    collectMatchedAndAncestors
  }
}
