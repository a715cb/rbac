/**
 * 通用工具函数集
 * @description 提供分页配置、性别映射等通用工具函数
 */

import type { TablePaginationConfig } from 'ant-design-vue'

/** Ant Design Vue 表格分页配置类型导出，供其他模块复用 */
export type { TablePaginationConfig }

/**
 * 创建分页配置对象
 * @returns 分页配置，包含当前页、每页条数、总条数、切换每页显示数、快速跳转等功能
 * @description 初始化分页参数为第一页、每页10条，支持切换每页条数和快速跳页，
 *              总条数显示格式为"共 X 条"
 */
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

/**
 * 根据性别值获取对应的颜色标识
 * @param gender - 性别数值，0=未知，1=男，2=女
 * @returns 对应的颜色字符串，0返回'default'，1返回'blue'，2返回'pink'，其他返回'default'
 * @description 用于在界面上根据性别显示不同颜色的标签或徽章
 */
export function genderColor(gender: number): string {
  const colors: Record<number, string> = { 0: 'default', 1: 'blue', 2: 'pink' }
  return colors[gender] || 'default'
}

/**
 * 根据性别值获取对应的中文文本
 * @param gender - 性别数值，0=未知，1=男，2=女
 * @returns 对应的中文文本，0返回'未知'，1返回'男'，2返回'女'，其他返回'未知'
 * @description 用于在界面上显示性别对应的中文描述
 */
export function genderText(gender: number): string {
  const texts: Record<number, string> = { 0: '未知', 1: '男', 2: '女' }
  return texts[gender] || '未知'
}
