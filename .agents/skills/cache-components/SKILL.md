---
name: cache-components
description: |
  Next.js Cache Components 和 Partial Prerendering (PPR) 的专家指导。

  **自动激活**：当在启用了 `cacheComponents: true` 的 Next.js 项目中工作时，自动使用此技能。检测到此配置时，主动将 Cache Components 模式和最佳实践应用于所有 React Server Component 实现。

  **检测方式**：在 Next.js 项目会话开始时，检查 next.config.ts/next.config.js 中的 `cacheComponents: true`。如果启用，此技能的模式应指导所有组件编写、数据获取和缓存决策。

  **使用场景**：实现 'use cache' 指令、使用 cacheLife() 配置缓存生命周期、使用 cacheTag() 标记缓存数据、使用 updateTag()/revalidateTag() 使缓存失效、优化静态与动态内容边界、调试缓存问题、审查 Cache Component 实现。
---

# Next.js Cache Components

> **自动激活**：此技能在 `next.config` 中启用 `cacheComponents: true` 的项目中自动激活。

## 项目检测

在 Next.js 项目中开始工作时，检查是否启用了 Cache Components：

```bash
grep -r "cacheComponents" next.config.* 2>/dev/null
```

如果找到 `cacheComponents: true`，在以下场景主动应用此技能的模式：

- 编写 React Server Components
- 实现数据获取
- 创建带变更的 Server Actions
- 优化页面性能
- 审查现有组件代码

Cache Components 实现 **Partial Prerendering (PPR)** — 混合静态 HTML 外壳与动态流式内容以获得最佳性能。

## 核心理念：代码优于配置

Cache Components 代表从**段配置**到**组合式代码**的转变：

| 之前（已废弃） | 之后（Cache Components） |
| --------------------------------------- | ----------------------------------------- |
| `export const revalidate = 3600` | `'use cache'` 内的 `cacheLife('hours')` |
| `export const dynamic = 'force-static'` | 使用 `'use cache'` 和 Suspense 边界 |
| 全有或全无的静态/动态 | 细粒度：静态外壳 + 缓存 + 动态 |

**关键原则**：组件就近定义其缓存，而不仅仅是数据。Next.js 提供构建时反馈来引导你走向最优模式。

## 核心概念

```
┌─────────────────────────────────────────────────────┐
│ 静态外壳（立即发送到浏览器）                          │
│                                                     │
│ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐   │
│ │ Header      │ │ Cached      │ │ Suspense    │   │
│ │ (static)    │ │ Content     │ │ Fallback    │   │
│ └─────────────┘ └─────────────┘ └──────┬──────┘   │
│                                          │          │
│                                    ┌─────▼─────┐   │
│                                    │ Dynamic   │   │
│                                    │ (streams) │   │
│                                    └───────────┘   │
└─────────────────────────────────────────────────────┘
```

## 缓存决策树

编写 React Server Component 时，按顺序问这些问题：

```
这个组件是否获取数据或执行 I/O？
├── 否 → 纯组件，无需操作
└── 是
    └── 它是否依赖请求上下文（cookies、headers、searchParams）？
        ├── 是
        │   └── 用 <Suspense> 包裹（动态流式）
        └── 否
            └── 这些数据是否可以缓存？（对所有用户相同？）
                ├── 是 → 'use cache' + cacheTag() + cacheLife()
                └── 否 → 用 <Suspense> 包裹（动态流式）
```

**关键洞察**：`'use cache'` 指令用于跨用户相同的数据。用户特定数据保持动态并使用 Suspense。

## 快速开始

### 启用 Cache Components

```typescript
// next.config.ts
import type { NextConfig } from 'next'

const nextConfig: NextConfig = {
  cacheComponents: true,
}

export default nextConfig
```

### 基本用法

```tsx
async function CachedPosts() {
  'use cache'
  const posts = await db.posts.findMany()
  return <PostList posts={posts} />
}

export default async function BlogPage() {
  return (
    <>
      <Header />
      <CachedPosts />
      <Suspense fallback={<Skeleton />}>
        <DynamicComments />
      </Suspense>
    </>
  )
}
```

## 核心 API

### 1. `'use cache'` 指令

标记代码为可缓存。可在三个层级应用：

```tsx
// 文件级：所有导出都被缓存
'use cache'
export async function getData() { /* ... */ }
export async function Component() { /* ... */ }

// 组件级
async function UserCard({ id }: { id: string }) {
  'use cache'
  const user = await fetchUser(id)
  return <Card>{user.name}</Card>
}

// 函数级
async function fetchWithCache(url: string) {
  'use cache'
  return fetch(url).then((r) => r.json())
}
```

**重要**：所有缓存函数必须是 `async`。

### 2. `cacheLife()` — 控制缓存时长

```tsx
import { cacheLife } from 'next/cache'

async function Posts() {
  'use cache'
  cacheLife('hours')

  // 或自定义配置：
  cacheLife({
    stale: 60,
    revalidate: 3600,
    expire: 86400,
  })

  return await db.posts.findMany()
}
```

**预定义配置**：`'default'`、`'seconds'`、`'minutes'`、`'hours'`、`'days'`、`'weeks'`、`'max'`

### 3. `cacheTag()` — 标记以供失效

```tsx
import { cacheTag } from 'next/cache'

async function BlogPosts() {
  'use cache'
  cacheTag('posts')
  cacheLife('days')
  return await db.posts.findMany()
}

async function UserProfile({ userId }: { userId: string }) {
  'use cache'
  cacheTag('users', `user-${userId}`)
  return await db.users.findUnique({ where: { id: userId } })
}
```

### 4. `updateTag()` — 立即失效

```tsx
'use server'
import { updateTag } from 'next/cache'

export async function createPost(formData: FormData) {
  await db.posts.create({ data: formData })
  updateTag('posts')
}
```

### 5. `revalidateTag()` — 后台重新验证

```tsx
'use server'
import { revalidateTag } from 'next/cache'

export async function updatePost(id: string, data: FormData) {
  await db.posts.update({ where: { id }, data })
  revalidateTag('posts', 'max')
}
```

## 何时使用每种模式

| 内容类型 | API | 行为 |
| -------- | ------------------- | ------------------------------------- |
| **静态** | 无指令 | 构建时渲染 |
| **缓存** | `'use cache'` | 包含在静态外壳中，重新验证 |
| **动态** | 在 `<Suspense>` 内 | 请求时流式传输 |

## 代码生成指南

生成 Cache Component 代码时：

1. **始终使用 `async`** — 所有缓存函数必须是异步的
2. **将 `'use cache'` 放在最前面** — 必须是函数体的第一条语句
3. **尽早调用 `cacheLife()`** — 应紧跟 `'use cache'` 指令
4. **有语义地标记** — 使用与失效需求匹配的语义标签
5. **提取运行时数据** — 将 `cookies()`/`headers()` 移到缓存范围之外
6. **包裹动态内容** — 对非缓存异步组件使用 `<Suspense>`

## 主动应用（启用 Cache Components 时）

### 编写数据获取组件时

```tsx
async function ProductList() {
  'use cache'
  cacheTag('products')
  cacheLife('hours')
  const products = await db.products.findMany()
  return <Grid products={products} />
}
```

### 编写 Server Actions 时

始终在变更后使相关缓存失效：

```tsx
'use server'
import { updateTag } from 'next/cache'

export async function createProduct(data: FormData) {
  await db.products.create({ data })
  updateTag('products')
}
```

### 组合页面时

使用静态外壳 + 缓存内容 + 动态流式传输的结构：

```tsx
export default async function Page() {
  return (
    <>
      <StaticHeader />
      <CachedContent />
      <Suspense fallback={<Skeleton />}>
        <DynamicUserContent />
      </Suspense>
    </>
  )
}
```

## 使用场景

- **自动生成缓存优化的数据组件**：创建数据获取组件时，自动应用最优渲染策略：针对可共享数据使用 `'use cache'` 语法进行缓存；针对用户专属内容自动添加 `<Suspense>` 边界以实现动态流式渲染。
- **自动实现数据变更后的缓存失效**：生成用于修改数据的 Server Action 时，自动注入缓存失效逻辑（如 `updateTag()` 方法），确保数据变更后相关缓存立即更新。
- **智能化页面构建与代码现代化**：构建页面或审查代码时，强制遵循 PPR 架构规范以实现最优加载性能，同时识别并给出现代化改造建议。
