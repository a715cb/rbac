# 7. API 接口文档

## 认证接口（无需 Token）

| 方法   | 路径                     | 说明       |
| ---- | ---------------------- | -------- |
| POST | `/admin/login`         | 用户登录     |
| POST | `/admin/logout`        | 用户登出     |
| POST | `/admin/refresh-token` | 刷新 Token |

## 需认证接口（需 Bearer Token）

### 用户管理

| 方法     | 路径                                | 说明       |
| ------ | --------------------------------- | -------- |
| GET    | `/admin/users`                    | 用户列表（分页） |
| GET    | `/admin/users/:id`                | 用户详情     |
| POST   | `/admin/users`                    | 创建用户     |
| PUT    | `/admin/users/:id`                | 更新用户     |
| DELETE | `/admin/users/:id`                | 删除用户     |
| POST   | `/admin/users/:id/assign-roles`   | 分配角色     |
| POST   | `/admin/users/:id/reset-password` | 重置密码     |
| PUT    | `/admin/users/:id/status`         | 切换状态     |
| GET    | `/admin/users/export`             | 导出用户     |
| POST   | `/admin/users/import`             | 导入用户     |

### 角色管理

| 方法     | 路径                                | 说明       |
| ------ | --------------------------------- | -------- |
| GET    | `/admin/roles`                    | 角色列表（分页） |
| GET    | `/admin/roles/:id`                | 角色详情     |
| POST   | `/admin/roles`                    | 创建角色     |
| PUT    | `/admin/roles/:id`                | 更新角色     |
| DELETE | `/admin/roles/:id`                | 删除角色     |
| POST   | `/admin/roles/:id/assign-menus`   | 分配菜单权限   |
| POST   | `/admin/roles/:id/assign-buttons` | 分配按钮权限   |
| POST   | `/admin/roles/:id/assign-apis`    | 分配接口权限   |
| PUT    | `/admin/roles/:id/data-scope`     | 设置数据权限   |
| PUT    | `/admin/roles/:id/status`         | 切换状态     |

### 菜单管理

| 方法     | 路径                                   | 说明       |
| ------ | ------------------------------------ | -------- |
| GET    | `/admin/menus`                       | 菜单列表（树形） |
| GET    | `/admin/menus/tree`                  | 菜单树（精简）  |
| GET    | `/admin/menus/:id`                   | 菜单详情     |
| POST   | `/admin/menus`                       | 创建菜单     |
| PUT    | `/admin/menus/:id`                   | 更新菜单     |
| DELETE | `/admin/menus/:id`                   | 删除菜单     |
| GET    | `/admin/menus/:id/buttons`           | 获取菜单按钮   |
| POST   | `/admin/menus/:id/buttons`           | 创建按钮     |
| PUT    | `/admin/menus/:id/buttons/:buttonId` | 更新按钮     |
| DELETE | `/admin/menus/:id/buttons/:buttonId` | 删除按钮     |

### 部门管理

| 方法     | 路径                        | 说明       |
| ------ | ------------------------- | -------- |
| GET    | `/admin/depts`            | 部门列表（树形） |
| GET    | `/admin/depts/tree`       | 部门树      |
| GET    | `/admin/depts/:id`        | 部门详情     |
| POST   | `/admin/depts`            | 创建部门     |
| PUT    | `/admin/depts/:id`        | 更新部门     |
| DELETE | `/admin/depts/:id`        | 删除部门     |
| PUT    | `/admin/depts/:id/status` | 切换状态     |
| PUT    | `/admin/depts/:id/sort`   | 设置排序     |
| GET    | `/admin/depts/:id/users`  | 部门用户列表   |

### 接口管理

| 方法     | 路径                         | 说明       |
| ------ | -------------------------- | -------- |
| GET    | `/admin/apis`              | 接口列表（分页） |
| GET    | `/admin/apis/groups`       | 接口分组列表   |
| GET    | `/admin/apis/menu/:menuId` | 按菜单获取接口  |
| GET    | `/admin/apis/:id`          | 接口详情     |
| POST   | `/admin/apis`              | 创建接口     |
| PUT    | `/admin/apis/:id`          | 更新接口     |
| DELETE | `/admin/apis/:id`          | 删除接口     |
| PUT    | `/admin/apis/:id/status`   | 切换状态     |

### 字典管理

| 方法     | 路径                             | 说明        |
| ------ | ------------------------------ | --------- |
| GET    | `/admin/dict/types`            | 字典类型列表    |
| POST   | `/admin/dict/types`            | 创建字典类型    |
| GET    | `/admin/dict/types/:id`        | 字典类型详情    |
| PUT    | `/admin/dict/types/:id`        | 更新字典类型    |
| DELETE | `/admin/dict/types/:id`        | 删除字典类型    |
| PUT    | `/admin/dict/types/:id/status` | 切换字典类型状态  |
| GET    | `/admin/dict/data`             | 字典数据列表    |
| POST   | `/admin/dict/data`             | 创建字典数据    |
| GET    | `/admin/dict/data/:id`         | 字典数据详情    |
| PUT    | `/admin/dict/data/:id`         | 更新字典数据    |
| DELETE | `/admin/dict/data/:id`         | 删除字典数据    |
| PUT    | `/admin/dict/data/:id/status`  | 切换字典数据状态  |
| POST   | `/admin/dict/data/sort`        | 排序更新      |
| GET    | `/admin/dict/code/:code`       | 按编码获取字典选项 |

### 日志管理

| 方法   | 路径                             | 说明       |
| ---- | ------------------------------ | -------- |
| GET  | `/admin/login-logs`            | 登录日志列表   |
| GET  | `/admin/login-logs/stats`      | 登录日志统计   |
| POST | `/admin/login-logs/clean`      | 清理历史日志   |
| POST | `/admin/login-logs/clear`      | 清空日志（超管） |
| POST | `/admin/login-logs/delete`     | 批量删除     |
| GET  | `/admin/operation-logs`        | 操作日志列表   |
| GET  | `/admin/operation-logs/stats`  | 操作日志统计   |
| POST | `/admin/operation-logs/clean`  | 清理历史日志   |
| POST | `/admin/operation-logs/clear`  | 清空日志（超管） |
| POST | `/admin/operation-logs/delete` | 批量删除     |

### 个人中心

| 方法   | 路径                            | 说明     |
| ---- | ----------------------------- | ------ |
| GET  | `/admin/profile`              | 获取个人信息 |
| PUT  | `/admin/profile`              | 更新个人信息 |
| POST | `/admin/profile/avatar`       | 上传头像   |
| PUT  | `/admin/profile/password`     | 修改密码   |
| GET  | `/admin/dashboard/statistics` | 仪表盘统计  |
