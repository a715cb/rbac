# Cache Components 故障排查

## 常见错误

### 错误：Dynamic data outside Suspense

```
Error: Accessing cookies/headers/searchParams outside a Suspense boundary
```

**原因**：在 `<Suspense>` 边界之外访问了动态API。

**解决方案**：
```tsx
<Suspense fallback={<Skeleton />}>
  <ComponentThatUsesCookies />
</Suspense>
```

### 错误：Uncached data outside Suspense

```
Error: Accessing uncached data outside Suspense
```

**原因**：在静态外壳中存在未缓存的数据获取。

**解决方案**：要么缓存数据，要么用Suspense包裹：
```tsx
// 方案1：缓存
async function ProductData({ id }: { id: string }) {
  'use cache'
  return await db.products.findUnique({ where: { id } })
}

// 方案2：动态
<Suspense fallback={<Loading />}>
  <DynamicProductData id={id} />
</Suspense>
```

### 错误：Request data inside cache

```
Error: Cannot access cookies/headers inside 'use cache'
```

**原因**：在 `'use cache'` 范围内访问了请求上下文API。

**解决方案**：将运行时数据提取到缓存边界之外，通过参数传入。

### 错误：Empty generateStaticParams

```
Error: generateStaticParams must return at least one param
```

**原因**：启用了Cache Components后，`generateStaticParams` 不能返回空数组。

**解决方案**：
```tsx
// ❌ 错误
export function generateStaticParams() {
  return []
}

// ✅ 正确
export async function generateStaticParams() {
  const products = await getPopularProducts()
  return products.map(({ category, slug }) => ({ category, slug }))
}
```

## 调试清单

- [ ] 确认 `cacheComponents: true` 在 `next.config` 中
- [ ] 所有缓存函数都是 `async`
- [ ] `'use cache'` 是函数体第一条语句
- [ ] `cacheLife()` 紧跟 `'use cache'` 之后调用
- [ ] 没有在缓存范围内访问 `cookies()`/`headers()`
- [ ] 动态组件用 `<Suspense>` 包裹
- [ ] Server Actions 在变更后调用了 `updateTag()`/`revalidateTag()`
- [ ] 没有使用废弃的 `export const revalidate`
- [ ] 没有使用废弃的 `export const dynamic`
- [ ] `generateStaticParams()` 返回了至少一个参数

## 性能调优

### 缓存命中率低

- 检查缓存键是否包含不必要的参数
- 确保使用语义化的 `cacheTag()`
- 适当调整 `cacheLife()` 配置

### 页面加载慢

- 检查是否有过多动态组件未用Suspense包裹
- 确保静态外壳尽可能大
- 减少Suspense边界的数量
