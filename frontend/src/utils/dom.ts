/**
 * DOM 相关工具函数
 * @description 提供 CSS 变量读取等 DOM 操作工具函数
 */

/**
 * 读取 CSS 变量值
 * @param variableName - CSS 变量名称（不带前缀的变量名，如 '--primary-color'）
 * @param defaultValue - 默认值，当 CSS 变量不存在或为空时返回此值，默认为空字符串
 * @returns CSS 变量的计算值，如果变量不存在或为空则返回 defaultValue
 * @description 通过 getComputedStyle 获取根元素（document.documentElement）的 CSS 变量值，
 *              返回前会去除首尾空格。适用于读取 Ant Design 等 UI 框架的 CSS 变量，
 *              用于主题适配或动态样式计算
 * @example
 * ```ts
 * // 获取主色调
 * const primaryColor = getCssVar('--primary-color', '#1890ff')
 *
 * // 获取边框颜色
 * const borderColor = getCssVar('--border-color-base', '#d9d9d9')
 * ```
 */
export function getCssVar(variableName: string, defaultValue = ''): string {
  const value = getComputedStyle(document.documentElement).getPropertyValue(variableName)
  return value ? value.trim() : defaultValue
}
