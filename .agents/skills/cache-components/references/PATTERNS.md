# Cache Components 常见模式

## 模式1：基础缓存组件

```tsx
async function ProductList() {
  'use cache'
  cacheTag('products')
  cacheLife('hours')

  const products = await db.products.findMany()
  return <Grid products={products} />
}
```

## 模式2：带参数的缓存组件

```tsx
async function ProductDetail({ id }: { id: string }) {
  'use cache'
  cacheTag(`product-${id}`)
  cacheLife('hours')

  const product = await db.products.findUnique({ where: { id } })
  return <ProductCard product={product} />
}
```

## 模式3：缓存函数

```tsx
async function getUser(id: string) {
  'use cache'
  cacheTag(`user-${id}`)
  cacheLife('minutes')

  return db.users.findUnique({ where: { id } })
}
```

## 模式4：Server Action + 缓存失效

```tsx
'use server'
import { updateTag } from 'next/cache'

export async function updateProduct(id: string, data: FormData) {
  await db.products.update({ where: { id }, data })
  updateTag(`product-${id}`)
  updateTag('products')
}
```

## 模式5：PPR页面结构

```tsx
export default async function Page() {
  return (
    <>
      <StaticNav />
      <CachedHero />
      <Suspense fallback={<ProductSkeleton />}>
        <DynamicRecommendations />
      </Suspense>
    </>
  )
}
```

## 模式6：提取运行时数据

```tsx
// 错误：在缓存中访问cookies
async function Dashboard() {
  'use cache'
  const userId = cookies().get('userId') // ❌ 错误
}

// 正确：在外部提取，传入参数
async function Dashboard({ userId }: { userId: string }) {
  'use cache'
  cacheTag(`dashboard-${userId}`)
  const data = await getDashboardData(userId) // ✅ 正确
}
```

## 模式7：多标签缓存

```tsx
async function UserPosts({ userId }: { userId: string }) {
  'use cache'
  cacheTag('posts', `user-posts-${userId}`)
  cacheLife('minutes')

  const posts = await db.posts.findMany({ where: { authorId: userId } })
  return <PostList posts={posts} />
}
```

## 模式8：条件缓存

```tsx
async function Content({ id, preview }: { id: string; preview?: boolean }) {
  if (preview) {
    return <PreviewContent id={id} />
  }

  'use cache'
  cacheTag(`content-${id}`)
  cacheLife('days')

  const content = await db.content.findUnique({ where: { id } })
  return <FullContent content={content} />
}
```
