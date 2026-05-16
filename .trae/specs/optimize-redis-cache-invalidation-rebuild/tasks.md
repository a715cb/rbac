# Tasks

- [x] Task 1: SimpleCache 增强 — 新增 remember 方法和修复 Predis 原子性
  - [x] SubTask 1.1: 新增 `remember(string $key, int $ttl, callable $callback, string $tag = null)` 方法，实现互斥锁保护的缓存重建流程
  - [x] SubTask 1.2: 新增 `lock(string $key, int $ttl): bool` 和 `unlock(string $key): void` 私有方法，基于 Redis SET NX EX 实现分布式互斥锁
  - [x] SubTask 1.3: 新增 `getJitteredTtl(int $baseTtl): int` 方法，计算带随机抖动的 TTL（±10%）
  - [x] SubTask 1.4: 修复 `setIfNotExists` 方法中 Predis 驱动使用 `SET key value EX ttl NX` 原子命令替代 SETNX + EXPIRE
  - [x] SubTask 1.5: 新增 `clearTag(string $tag): bool` 方法，封装 ThinkPHP Cache 标签清除

- [x] Task 2: AdminAuth 缓存读取重构 — 使用 remember 模式 + TTL 抖动 + 空结果缓存
  - [x] SubTask 2.1: 重构 `getMenuCodes()` 使用 `SimpleCache::remember()` 替代手动 get/set 模式
  - [x] SubTask 2.2: 重构 `getApiCodes()` 使用 `SimpleCache::remember()` 替代手动 get/set 模式
  - [x] SubTask 2.3: 重构 `getButtonCodes()` 使用 `SimpleCache::remember()` 替代手动 get/set 模式
  - [x] SubTask 2.4: 重构 `getMenuTree()` 使用 `SimpleCache::remember()` 替代手动 get/set 模式，确保空结果也缓存
  - [x] SubTask 2.5: 重构 `getAllMenuCodes()`、`getAllApiCodes()`、`getAllButtonCodes()` 使用 `SimpleCache::remember()`
  - [x] SubTask 2.6: 所有 remember 调用传入缓存标签 `user_menu_cache`（用户级）或 `global_menu_cache`（全局级）

- [x] Task 3: AdminAuth clearCache 作用域优化
  - [x] SubTask 3.1: 修改 `clearCache()` 仅清除当前用户维度缓存，不再清除全局缓存
  - [x] SubTask 3.2: 新增 `clearGlobalCache()` 方法，专门清除 `all_menu_codes`、`all_api_codes`、`all_button_codes`
  - [x] SubTask 3.3: 新增 `clearAllUserCache()` 方法，通过缓存标签 `user_menu_cache` 批量清除所有用户权限缓存

- [x] Task 4: MenuController/MenuButtonController 缓存清除优化
  - [x] SubTask 4.1: 重构 MenuController 的 `clearAllMenuCache()` 使用 `AdminAuth::clearAllUserCache()` + `clearGlobalCache()` 替代逐用户遍历删除
  - [x] SubTask 4.2: 重构 MenuButtonController 的 `clearAllMenuCache()` 使用同样的标签清除方式
  - [x] SubTask 4.3: 移除两处 Controller 中 `Db::name('user')->where('status', 1)->column('id')` 的数据库查询

- [x] Task 5: RoleController clearRoleCache 性能优化
  - [x] SubTask 5.1: 重构 `clearRoleCache()` 使用 `AdminAuth::clearAllUserCache()` + `clearGlobalCache()` 替代逐用户实例化 AdminAuth 的方式
  - [x] SubTask 5.2: 移除 `Db::name('user_role')->where('role_id', $roleId)->column('user_id')` 的数据库查询

- [x] Task 6: 缓存标签配置更新
  - [x] SubTask 6.1: 更新 `config/cache.php` 中 Redis 驱动的 `tag_prefix` 配置为有效值（如 `rbac_tag:`）
  - [x] SubTask 6.2: 验证 ThinkPHP Cache 标签功能在当前版本下正常工作

# Task Dependencies
- [Task 2] depends on [Task 1] — AdminAuth 重构依赖 SimpleCache 的 remember 和 getJitteredTtl 方法
- [Task 3] depends on [Task 1] — clearAllUserCache 依赖 SimpleCache 的 clearTag 方法
- [Task 4] depends on [Task 3] — Controller 优化依赖 AdminAuth 的 clearAllUserCache 和 clearGlobalCache
- [Task 5] depends on [Task 3] — RoleController 优化依赖 AdminAuth 的 clearAllUserCache 和 clearGlobalCache
- [Task 6] 无依赖，可与 Task 1 并行
