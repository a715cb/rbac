/**
 * @文件: useExport.ts
 * @用途: CSV 导出工具组合式函数
 * @描述: 封装 CSV 文件导出的通用逻辑：字段转义、Blob 创建、下载触发。
 *        支持自定义表头和数据行，自动添加 BOM 头确保中文兼容性。
 */

/**
 * CSV 字段转义
 * @description 当字段值包含逗号、双引号或换行符时，用双引号包裹并转义内部双引号
 * @param val - 待转义的值
 * @returns 转义后的字符串
 */
export function escapeCsvField(val: unknown): string {
  const str = val == null ? '' : String(val)
  if (str.includes(',') || str.includes('"') || str.includes('\n')) {
    return `"${str.replace(/"/g, '""')}"`
  }
  return str
}

/**
 * CSV 导出选项
 */
export interface CsvExportOptions {
  /** 文件名前缀，不含日期和扩展名 */
  filename: string
  /** CSV 表头列名数组 */
  headers: string[]
  /** CSV 数据行数组，每个元素为字符串数组 */
  rows: string[][]
}

/**
 * 导出 CSV 文件
 * @description 生成带 BOM 头的 UTF-8 CSV 并触发浏览器下载
 * @param options - 导出配置
 */
export function downloadCsv(options: CsvExportOptions): void {
  const { filename, headers, rows } = options
  const csvContent = [headers.join(','), ...rows.map((row) => row.join(','))].join('\n')
  const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  const blobUrl = URL.createObjectURL(blob)
  link.href = blobUrl
  link.download = `${filename}_${new Date().toISOString().slice(0, 10)}.csv`
  link.click()
  URL.revokeObjectURL(blobUrl)
}

/**
 * CSV 导出组合式函数
 */
export function useExport() {
  return {
    escapeCsvField,
    downloadCsv
  }
}
