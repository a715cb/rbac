# 规则目录 — 性能

## React Flow 数据使用
紧急度: True
类别: 性能

### 描述
渲染React Flow时，优先使用 `useNodes`/`useEdges` 进行UI消费，在变更或读取节点/边状态的回调中依赖 `useStoreApi`。避免在这些Hooks之外手动拉取Flow数据。

## 复杂属性记忆化
紧急度: True
类别: 性能

### 描述
将传递给子组件的复杂属性值（对象、数组、Map）用 `useMemo` 包裹，以保证引用稳定并防止不必要的重渲染。

添加、编辑或删除性能规则时更新此文件，以保持目录的准确性。

错误示例：
```tsx
<HeavyComp
    config={{
        provider: ...,
        detail: ...
    }}
/>
```

正确示例：
```tsx
const config = useMemo(() => ({
    provider: ...,
    detail: ...
}), [provider, detail]);
<HeavyComp
    config={config}
/>
```
