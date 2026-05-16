# Redis 缓存失效重建机制优化 Spec

## Why
当前 RBAC 系统的 Redis 缓存架构存在缓存击穿（热点 Key 失效时并发请求直达 DB）、缓存雪崩（所有权限缓存统一 TTL 导致集中失效）、批量清除效率低下（逐用户遍历删除）等问题，在高并发场景下可能造成数据库压力骤增和响应延迟。

## What Changes
- 在 `SimpleCache` 中新增 `remember` 方法，内置互斥锁保护缓存重建过程，防止缓存击穿
- 为权限缓存 TTL 引入随机抖动机制，防止缓存雪崩
- 利用 Redis 缓存标签（Cache Tag）替代逐键遍历删除，优化批量缓存清除性能
- 修复 Predis 驱动下 `setIfNotExists` 的 SETNX + EXPIRE 非原子性问题
- 优化 `AdminAuth::clearCache()` 避免不必要的全局缓存清除
- 优化 `RoleController::clearRoleCache()` 避免逐用户实例化 AdminAuth 的性能开销
- 统一空结果缓存策略，防止缓存穿透

## Impact
- Affected specs: 权限缓存读写、菜单/按钮/角色变更时的缓存清除、登录安全缓存
- Affected code:
  - `backend/app/common/SimpleCache.php` — 新增 remember 方法、修复 Predis 原子性
  - `backend/app/common/AdminAuth.php` — 重构缓存读取为 remember 模式、TTL 随机抖动、优化 clearCache
  - `backend/app/admin/controller/MenuController.php` — 使用缓存标签替代逐键删除
  - `backend/app/admin/controller/MenuButtonController.php` — 使用缓存标签替代逐键删除
  - `backend/app/admin/controller/RoleController.php` — 优化 clearRoleCache 实现

## ADDED Requirements

### Requirement: 缓存重建互斥锁（防击穿）
系统 SHALL 在缓存未命中时，通过互斥锁机制确保同一时刻只有一个请求执行缓存重建，其余请求等待并获取重建后的缓存结果。

#### Scenario: 热点缓存 Key 失效时的并发请求
- **WHEN** 多个并发请求同时访问一个已失效的缓存 Key
- **THEN** 仅第一个获取到锁的请求执行数据库查询和缓存重建，其余请求等待锁释放后直接读取重建后的缓存

#### Scenario: 缓存重建超时
- **WHEN** 持有锁的请求在锁超时时间内未完成缓存重建
- **THEN** 等待中的请求不再继续等待，直接穿透到数据库查询（降级策略）

### Requirement: 缓存 TTL 随机抖动（防雪崩）
系统 SHALL 为权限缓存 TTL 引入随机抖动，避免大量缓存 Key 在同一时刻集中失效。

#### Scenario: 权限缓存写入时 TTL 计算
- **WHEN** 权限缓存写入 Redis
- **THEN** 实际 TTL = 基础 TTL + 随机偏移量（偏移范围为基础 TTL 的 ±10%），确保不同用户的缓存失效时间分散

### Requirement: 缓存标签批量清除
系统 SHALL 使用 Redis 缓存标签（Cache Tag）机制对权限缓存进行分组管理，支持按标签批量清除，替代当前的逐用户遍历删除模式。

#### Scenario: 菜单变更时清除所有用户权限缓存
- **WHEN** 菜单数据发生变更（增/删/改/状态切换）
- **THEN** 通过清除 `user_menu_cache` 标签一次性删除所有用户的菜单相关缓存，而非遍历用户列表逐个删除

#### Scenario: 角色变更时清除关联用户权限缓存
- **WHEN** 角色的权限或状态发生变更
- **THEN** 通过清除 `user_menu_cache` 标签删除所有用户权限缓存，同时清除全局缓存

### Requirement: SimpleCache remember 方法
系统 SHALL 在 `SimpleCache` 类中提供 `remember` 方法，封装"读取缓存 → 未命中时加锁重建 → 写入缓存"的完整流程。

#### Scenario: 缓存命中
- **WHEN** 调用 `remember($key, $ttl, $callback)` 且缓存 Key 存在
- **THEN** 直接返回缓存值，不执行 callback

#### Scenario: 缓存未命中且获取锁成功
- **WHEN** 调用 `remember($key, $ttl, $callback)` 且缓存 Key 不存在
- **THEN** 获取互斥锁，执行 callback 获取数据，写入缓存后释放锁并返回数据

#### Scenario: 缓存未命中但锁被其他请求持有
- **WHEN** 调用 `remember($key, $ttl, $callback)` 且缓存 Key 不存在，但互斥锁已被其他请求持有
- **THEN** 短暂等待后重新尝试读取缓存（锁持有者已重建），若仍无缓存则降级直接执行 callback

## MODIFIED Requirements

### Requirement: Predis 驱动 setIfNotExists 原子性
原实现：Predis 驱动使用 `SETNX + EXPIRE` 两条命令，存在非原子窗口（SETNX 成功但 EXPIRE 前进程崩溃会导致 Key 永不过期）。
修改后：Predis 驱动使用 `SET key value EX ttl NX` 一条命令，与 phpredis 驱动保持一致的原子语义。

#### Scenario: Predis 驱动下 setIfNotExists 调用
- **WHEN** 使用 Predis 驱动调用 `setIfNotExists($key, $value, $ttl)`
- **THEN** 使用 `SET $key $value EX $ttl NX` 原子命令，保证写入和过期时间设置的原子性

### Requirement: AdminAuth clearCache 作用域优化
原实现：每次调用 `clearCache()` 都会删除全局缓存（`all_menu_codes`、`all_api_codes`、`all_button_codes`），即使变更仅影响单个用户。
修改后：`clearCache()` 仅清除当前用户维度的缓存；新增 `clearGlobalCache()` 方法专门清除全局缓存；菜单/角色变更时同时调用两者。

#### Scenario: 用户登出时清除缓存
- **WHEN** 用户登出调用 `clearCache()`
- **THEN** 仅清除该用户的 `user_menu_codes_{userId}`、`user_api_codes_{userId}`、`user_button_codes_{userId}`、`user_menu_tree_{userId}`，不清除全局缓存

#### Scenario: 菜单变更时清除缓存
- **WHEN** 菜单数据变更
- **THEN** 通过缓存标签清除所有用户权限缓存，并调用 `clearGlobalCache()` 清除全局缓存

### Requirement: RoleController clearRoleCache 性能优化
原实现：遍历角色关联用户，为每个用户实例化 `AdminAuth` 并调用 `setUser()` + `clearCache()`，每次 setUser 都会触发数据库查询加载角色。
修改后：直接通过缓存标签批量清除，无需逐用户实例化 AdminAuth。

#### Scenario: 角色权限变更时清除缓存
- **WHEN** 角色的权限分配或状态发生变更
- **THEN** 通过清除缓存标签一次性删除所有用户权限缓存，并清除全局缓存，无需遍历用户列表

### Requirement: 空结果缓存策略统一
原实现：`getButtonCodes()` 缓存空数组，但 `getMenuTree()` 未缓存空结果。
修改后：所有权限查询方法统一缓存空结果（空数组），TTL 与正常结果一致，防止缓存穿透。

#### Scenario: 用户无任何按钮权限
- **WHEN** 用户查询按钮权限码返回空数组
- **THEN** 空数组被写入缓存，后续请求直接从缓存返回空结果，不穿透到数据库

## REMOVED Requirements

### Requirement: MenuController/MenuButtonController 逐用户遍历删除缓存
**Reason**: 使用缓存标签批量清除后，不再需要遍历用户列表逐个删除缓存键
**Migration**: `clearAllMenuCache()` 方法改为通过缓存标签清除，移除数据库查询用户列表的逻辑
