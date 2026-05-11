# 规则目录 — 业务逻辑

## 不能在Node组件中使用workflowStore
紧急度: True

### 描述
节点组件的文件路径模式：`web/app/components/workflow/nodes/[nodeName]/node.tsx`

Node组件在从模板创建RAG管道时也会被使用，但在该上下文中没有workflowStore Provider，会导致白屏。[此Issue](https://github.com/langgenius/dify/issues/29168)正是由这个原因引起的。

### 建议修复
使用 `import { useNodes } from 'reactflow'` 替代 `import useNodes from '@/app/components/workflow/store/workflow/use-nodes'`。
