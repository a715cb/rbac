# Cache Components API 参考

## 指令和函数

### `'use cache'`

标记函数或组件为可缓存。

**使用层级：**
- 文件级：文件顶部声明，所有导出均被缓存
- 组件级：组件函数体第一行
- 函数级：异步函数体第一行

**约束：**
- 所有被标记的函数必须是 `async`
- 不能在 `'use cache'` 范围内访问 `cookies()`、`headers()` 等请求上下文API

### `cacheLife(profile)`

控制缓存的生命周期。

**参数：**
- `profile: string | object` — 预定义配置名或自定义配置

**预定义配置：**

| 配置名 | stale | revalidate | expire |
| ------ | ----- | ---------- | ------ |
| `'default'` | - | - | - |
| `'seconds'` | 0s | 5s | 60s |
| `'minutes'` | 60s | 300s | 3600s |
| `'hours'` | 300s | 3600s | 86400s |
| `'days'` | 3600s | 86400s | 604800s |
| `'weeks'` | 86400s | 604800s | 2592000s |
| `'max'` | 86400s | 2592000s | ∞ |

**自定义配置：**
```typescript
cacheLife({
  stale: number    // 客户端缓存有效期（秒）
  revalidate: number // 开始后台刷新时间（秒）
  expire: number    // 绝对过期时间（秒）
})
```

### `cacheTag(...tags)`

为缓存数据添加标签，用于后续失效。

**参数：**
- `tags: ...string` — 一个或多个标签名

**示例：**
```typescript
cacheTag('posts')
cacheTag('users', `user-${userId}`)
```

### `updateTag(tag)`

立即使指定标签的缓存失效（读己之写语义）。

**参数：**
- `tag: string` — 要失效的标签名

**使用场景：** Server Action中数据变更后调用

### `revalidateTag(tag, profile?)`

后台重新验证指定标签的缓存（stale-while-revalidate语义）。

**参数：**
- `tag: string` — 要重新验证的标签名
- `profile?: string` — 缓存生命周期配置（默认 `'default'`）

## 核心概念

### 缓存键

函数参数自动成为缓存键的一部分。不同参数产生不同的缓存条目。

### 静态外壳与动态流

- **静态外壳**：不使用任何缓存或动态API的组件，构建时渲染
- **缓存内容**：使用 `'use cache'` 的组件，包含在静态外壳中
- **动态流**：使用请求上下文API的组件，需用 `<Suspense>` 包裹

### 参数排列与子外壳

`generateStaticParams` 提供的每个参数排列都会生成可复用的子外壳。
