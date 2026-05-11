# 规则目录 — 代码质量

## 条件类名使用工具函数
紧急度: True
类别: 代码质量

### 描述
确保条件CSS通过共享的 `classNames` 工具函数处理，而非自定义三元表达式、字符串拼接或模板字符串。集中管理类逻辑使组件更一致、更易维护。

### 建议修复
```ts
import { cn } from '@/utils/classnames'
const classNames = cn(isActive ? 'text-primary-600' : 'text-gray-500')
```

## Tailwind优先样式
紧急度: True
类别: 代码质量

### 描述
优先使用Tailwind CSS工具类，而非添加新的 `.module.css` 文件，除非Tailwind组合无法实现所需样式。将样式保持在Tailwind中可提高一致性并减少维护开销。

添加、编辑或删除代码质量规则时更新此文件，以保持目录的准确性。

## 类名排序以便覆盖

### 描述
编写组件时，始终将传入的 `className` 属性放在组件自身类值之后，以便下游消费者可以覆盖或扩展样式。这保留了组件的默认值，同时仍允许外部调用者更改或移除特定样式。

示例：
```tsx
import { cn } from '@/utils/classnames'
const Button = ({ className }) => {
  return <div className={cn('bg-primary-600', className)}></div>
}
```
