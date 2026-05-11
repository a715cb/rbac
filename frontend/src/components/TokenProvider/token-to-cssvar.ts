import type { GlobalToken } from 'ant-design-vue/es/theme'

const prefixCls = '--ant-'

const styleId = `${prefixCls}dynamic-theme` // 固定ID，用于复用同一个<style>元素，避免重复调用时创建多个冗余样式标签

const toKebabCase = (str: string): string => {
  return str.replace(/([A-Z])/g, '-$1').toLowerCase()
}

const formatKey = (key: string) => {
  return `${prefixCls}${toKebabCase(key)}`
}

const colorKey = [
  'colorPrimary',
  'colorPrimaryBg',
  'colorBgContainer',
  'colorBgLayout',
  'colorBorder',
  'borderRadius',
  'colorBorderSecondary',
  'colorText',
  'colorTextSecondary',
  'colorTextTertiary',
  'colorTextQuaternary'
]

export const registerTokenToCSSVar = (token: GlobalToken) => {
  const variables: Record<string, any> = {}
  if (!token) return
  for (const key in token) {
    const val = token[key as keyof GlobalToken]
    if (colorKey.includes(key)) {
      variables[formatKey(key)] = typeof val === 'number' ? `${val}px` : val
    }
  }
  const cssList = Object.keys(variables).map((key) => `${key}: ${variables[key]};`)
  let styleEl = document.getElementById(styleId) as HTMLStyleElement | null
  if (!styleEl) {
    styleEl = document.createElement('style')
    styleEl.id = styleId
    document.head.appendChild(styleEl)
  }
  styleEl.textContent = `:root {${cssList.join('\n')}}`
}
