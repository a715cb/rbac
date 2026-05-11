import type { TablePaginationConfig } from 'ant-design-vue'

export type { TablePaginationConfig }

export function createPagination(): TablePaginationConfig {
  return {
    current: 1,
    pageSize: 10,
    total: 0,
    showSizeChanger: true,
    showQuickJumper: true,
    showTotal: (total: number) => `共 ${total} 条`
  }
}

export function genderColor(gender: number): string {
  const colors: Record<number, string> = { 0: 'default', 1: 'blue', 2: 'pink' }
  return colors[gender] || 'default'
}

export function genderText(gender: number): string {
  const texts: Record<number, string> = { 0: '未知', 1: '男', 2: '女' }
  return texts[gender] || '未知'
}
