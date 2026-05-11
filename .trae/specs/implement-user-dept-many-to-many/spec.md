# 用户-部门多对多关系模型实施规范

## Why
当前系统用户与部门为一对多关系（`sys_user.dept_id` 单外键），无法表达用户兼职、跨部门协作等真实业务场景。多部门用户的数据权限仅基于主部门计算，导致副部门数据不可见。需要将用户-部门关系从一对多升级为多对多，引入主部门标识，同时修复自定义数据权限（data_scope=5）的功能缺失。

## What Changes
- 新增 `sys_user_dept` 关联表，存储用户与部门的多对多关系，含 `is_primary` 主部门标识
- 保留 `sys_user.dept_id` 作为主部门冗余缓存，确保向后兼容
- 修改 `getScopedDeptIds()` 方法，从基于单一 `dept_id` 改为基于用户所有关联部门计算
- 修复 `getScopedDeptIds()` 中 `case 5`（自定义数据权限）的功能缺失
- 新增用户-部门关联 CRUD 接口（全量替换、增量添加、移除关联）
- 修改用户创建/更新逻辑，同步写入 `sys_user_dept` 关联表
- 修改用户列表/详情接口，返回 `depts` 多部门数组
- 修改部门删除逻辑，区分主部门用户（禁止删除）和兼职用户（自动移除关联）
- 前端用户表单弹窗：部门选择从单选改为多选，支持主部门标识
- 前端用户列表：部门列显示多个部门标签
- 前端部门成员弹窗：包含兼职用户标识
- 数据迁移脚本：将现有 `sys_user.dept_id` 数据迁移到 `sys_user_dept`

## Impact
- Affected specs: 数据权限计算规则、用户管理CRUD、部门管理删除逻辑
- Affected code:
  - 后端：`AdminAuth.php`、`UserController.php`、`DepartmentController.php`、`User.php`、`Department.php`
  - 前端：`user/index.vue`、`UserFormModal.vue`、`DeptTree.vue`、`DeptUsersModal.vue`、`api/user.ts`、`api/dept.ts`
  - 数据库：新增 `sys_user_dept` 表、数据迁移脚本

## ADDED Requirements

### Requirement: 用户-部门多对多关联表
系统 SHALL 提供 `sys_user_dept` 关联表，存储用户与部门的多对多关系，包含 `user_id`、`dept_id`、`is_primary`、`sort`、`create_time` 字段，其中 `(user_id, dept_id)` 组成唯一索引。

#### Scenario: 创建用户时同时写入关联表
- **WHEN** 创建用户并指定部门列表
- **THEN** 系统在 `sys_user` 表插入用户记录，同时在 `sys_user_dept` 表批量插入关联记录，并同步 `sys_user.dept_id` 为主部门ID

#### Scenario: 更新用户部门关联
- **WHEN** 调用 PUT `/admin/users/:id/depts` 设置用户的部门关联列表
- **THEN** 系统在事务内先删除旧关联再批量插入新关联，最后同步 `sys_user.dept_id` 为主部门ID

#### Scenario: 主部门唯一性校验
- **WHEN** 用户设置部门关联列表中有多条 `is_primary=true` 的记录
- **THEN** 系统拒绝操作并返回错误提示"只能有一个主部门"

#### Scenario: 必须指定主部门
- **WHEN** 用户设置部门关联列表中没有 `is_primary=true` 的记录
- **THEN** 系统拒绝操作并返回错误提示"必须指定一个主部门"

### Requirement: 多部门数据权限计算
系统 SHALL 修改 `getScopedDeptIds()` 方法，从基于用户单一 `dept_id` 计算改为基于用户所有关联部门计算。

#### Scenario: data_scope=2（本部门）多部门用户
- **WHEN** 用户属于部门A和部门B，角色 data_scope=2
- **THEN** `getScopedDeptIds()` 返回 [A的ID, B的ID]，用户可查看两个部门的数据

#### Scenario: data_scope=3（本部门及以下）多部门用户
- **WHEN** 用户属于部门A和部门B，角色 data_scope=3
- **THEN** `getScopedDeptIds()` 返回部门A、B及其所有子部门的ID列表（去重）

#### Scenario: data_scope=5（自定义数据权限）修复
- **WHEN** 角色配置 data_scope=5 且 `data_scope_dept_ids` 非空
- **THEN** `getScopedDeptIds()` 返回 `data_scope_dept_ids` 中的部门ID列表

### Requirement: 部门删除关联用户检查
系统 SHALL 在删除部门时区分主部门用户和兼职用户。

#### Scenario: 删除包含主部门用户的部门
- **WHEN** 删除的部门下存在 `is_primary=true` 的关联用户
- **THEN** 系统禁止删除并返回错误提示"该部门下存在主部门用户，请先转移"

#### Scenario: 删除仅包含兼职用户的部门
- **WHEN** 删除的部门下仅存在 `is_primary=false` 的关联用户
- **THEN** 系统自动移除这些用户与该部门的关联关系，然后删除部门

### Requirement: 用户表单多部门选择
系统 SHALL 在用户表单弹窗中将部门选择从单选改为多选，支持标记主部门。

#### Scenario: 新增用户时选择多个部门
- **WHEN** 用户在新增用户表单中选择多个部门
- **THEN** 第一个选中的部门默认为主部门，其余为兼职部门

#### Scenario: 编辑用户时修改部门关联
- **WHEN** 用户在编辑用户表单中修改部门关联
- **THEN** 可添加/移除部门、切换主部门标识，保存后全量替换关联关系

### Requirement: 用户列表多部门显示
系统 SHALL 在用户列表的部门列显示用户的所有关联部门。

#### Scenario: 多部门用户在列表中显示
- **WHEN** 用户属于多个部门
- **THEN** 部门列显示所有部门标签，主部门标签带星号或不同颜色标识

## MODIFIED Requirements

### Requirement: 用户创建/更新接口
用户创建和更新接口 SHALL 在事务内同步写入 `sys_user_dept` 关联表，并保持 `sys_user.dept_id` 与主部门一致。请求体中 `dept_id` 单字段替换为 `depts` 数组字段。

### Requirement: 用户列表/详情接口
用户列表和详情接口 SHALL 返回 `depts` 数组字段，包含用户所有关联部门的 `dept_id`、`dept_name`、`is_primary`、`sort` 信息。同时保留 `dept_id` 和 `dept_name` 兼容字段（取主部门值）。

### Requirement: 部门成员查询接口
部门成员查询接口（GET `/admin/depts/:id/users`）SHALL 返回该部门的所有关联用户（含主部门和兼职部门用户），并在返回数据中标识用户在该部门中的 `is_primary` 状态。

## REMOVED Requirements

### Requirement: 无
本次变更无移除的需求，所有现有功能保持向后兼容。
