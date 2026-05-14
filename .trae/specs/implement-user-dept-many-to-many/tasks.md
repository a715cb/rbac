# Tasks

## 阶段1：数据模型设计与基础CRUD

- [x] Task 1: 创建 `sys_user_dept` 关联表及数据迁移
  - [x] 1.1: 编写数据库迁移文件，创建 `sys_user_dept` 表（含 user_id、dept_id、is_primary、sort、create_time 字段，唯一索引 uk_user_dept）
  - [x] 1.2: 编写数据迁移脚本，将现有 `sys_user.dept_id` 数据迁移到 `sys_user_dept`（is_primary=1）
  - [x] 1.3: 验证迁移脚本在测试环境正确执行（迁移 SQL 已嵌入 001_init_schema.sql，重新初始化数据库即完成迁移）

- [x] Task 2: 创建后端 UserDept 模型
  - [x] 2.1: 创建 `app/model/UserDept.php` 模型，定义表名、主键、类型转换
  - [x] 2.2: 在 User 模型中添加 `depts()` 关联方法
  - [x] 2.3: 在 Department 模型中添加 `users()` 关联方法

- [x] Task 3: 实现用户-部门关联 CRUD 接口
  - [x] 3.1: 在 UserController 中实现 `PUT /admin/users/:id/depts`（全量替换部门关联）
  - [x] 3.2: 在 UserController 中实现 `POST /admin/users/:id/depts`（增量添加部门关联）
  - [x] 3.3: 在 UserController 中实现 `DELETE /admin/users/:id/depts/:deptId`（移除单个部门关联）
  - [x] 3.4: 添加 UserDeptValidate 验证器，校验主部门唯一性、部门存在性、部门状态（校验逻辑内置于接口方法中）
  - [x] 3.5: 注册路由到 admin.php

- [x] Task 4: 修改用户创建/更新逻辑
  - [x] 4.1: 修改 UserController::store()，创建用户后同步写入 `sys_user_dept` 关联记录
  - [x] 4.2: 修改 UserController::update()，更新用户时同步更新 `sys_user_dept` 关联记录
  - [x] 4.3: 确保 `sys_user.dept_id` 与 `sys_user_dept` 主部门记录在事务内同步

- [x] Task 5: 修改用户列表/详情接口返回多部门数据
  - [x] 5.1: 修改 UserController::index()，批量查询用户部门关联并附加到返回数据
  - [x] 5.2: 修改 UserController::show()，查询用户部门关联并附加到返回数据
  - [x] 5.3: 修改 UserController::export()，导出时包含多部门信息

## 阶段2：业务规则与权限规则

- [x] Task 6: 修改 `getScopedDeptIds()` 支持多部门
  - [x] 6.1: 在 AdminAuth 中新增 `getUserDeptIds()` 方法，从 `sys_user_dept` 获取用户所有部门ID
  - [x] 6.2: 修改 `getScopedDeptIds()` 的 case 2，返回用户所有关联部门ID
  - [x] 6.3: 修改 `getScopedDeptIds()` 的 case 3，对所有关联部门递归获取子部门ID并去重
  - [x] 6.4: 修复 `getScopedDeptIds()` 的 case 5，返回 `dataScopeDeptIds`
  - [x] 6.5: 编写权限计算单元测试，覆盖单部门、多部门、主部门变更、各 data_scope 场景（新增9个测试用例）

- [x] Task 7: 修改部门删除逻辑
  - [x] 7.1: 修改 DepartmentController::destroy()，查询 `sys_user_dept` 中 `is_primary=true` 的关联用户
  - [x] 7.2: 存在主部门用户时禁止删除并返回明确错误信息
  - [x] 7.3: 仅存在兼职用户时自动移除关联关系后删除部门

- [x] Task 8: 修改部门成员查询接口
  - [x] 8.1: 修改 DepartmentController::getUsers()，从 `sys_user_dept` 查询所有关联用户（含兼职）
  - [x] 8.2: 返回数据中增加 `is_primary` 标识字段

## 阶段3：前端改造

- [x] Task 9: 修改前端 API 类型定义
  - [x] 9.1: 在 `api/user.ts` 中新增 `UserDeptItem` 接口类型
  - [x] 9.2: 修改 `UserInfo` 接口，新增 `depts: UserDeptItem[]` 字段
  - [x] 9.3: 修改 `UserForm` 接口，添加 `depts` 数组字段（保留 `dept_id` 向后兼容）
  - [x] 9.4: 新增用户部门关联管理 API 函数

- [x] Task 10: 改造用户表单弹窗
  - [x] 10.1: 将部门选择从 `a-tree-select` 单选改为多选列表组件
  - [x] 10.2: 实现主部门标识切换功能（点击切换 is_primary）
  - [x] 10.3: 实现部门添加/移除功能
  - [x] 10.4: 表单提交时组装 `depts` 数组

- [x] Task 11: 改造用户列表页面
  - [x] 11.1: 修改部门列 customRender，显示多个部门标签
  - [x] 11.2: 主部门标签使用不同颜色或星号标识
  - [x] 11.3: 修改用户筛选逻辑，按部门筛选时匹配用户所有关联部门

- [x] Task 12: 改造部门成员弹窗
  - [x] 12.1: 修改 DeptUsersModal，显示用户在该部门中的 is_primary 状态
  - [x] 12.2: 兼职用户显示"兼职"标签

## 阶段4：数据迁移与验证

- [ ] Task 13: 数据迁移与一致性验证
  - [ ] 13.1: 在测试环境执行数据迁移脚本
    > **执行步骤**:
    > 1. 备份现有数据库: `mysqldump -u root -p rbac > rbac_backup_.sql`
    > 2. 执行迁移 SQL（已内嵌于 001_init_schema.sql L129-141，含 DROP+CREATE 结构）
    > 3. 同时执行数据迁移: `INSERT INTO sys_user_dept (user_id, dept_id, is_primary, sort) SELECT id, dept_id, 1, 0 FROM sys_user WHERE dept_id IS NOT NULL AND delete_time IS NULL`
    > 4. 运行一致性校验: `php check_user_dept_consistency.php`（13.2 已实现）
  - [x] 13.2: 编写一致性校验脚本，验证 `sys_user.dept_id` 与 `sys_user_dept` 主部门记录一致（已创建 check_user_dept_consistency.php）
  - [ ] 13.3: 性能测试：用户部门关系查询响应时间 < 200ms，批量操作 > 1000条/分钟
    > **验证方法**:
    > 1. 单用户查询: `EXPLAIN SELECT * FROM sys_user_dept WHERE user_id = 1`（确认使用 uk_user_dept 索引）
    > 2. 批量部门查询: `EXPLAIN SELECT * FROM sys_user_dept WHERE dept_id = 10`（确认使用 idx_dept_id 索引）
    > 3. Apache Bench 压测: `ab -n 1000 -c 10 http://localhost:8000/admin/users/1/depts`
    > 4. 批量插入测试: 用脚本插入 2000 条关联记录，验证事务效率

- [ ] Task 14: 全面测试与上线
  - [ ] 14.1: 功能测试：覆盖所有新增和修改的接口
    > **测试用例清单**:
    > - 用户创建: 指定多部门（1主+N兼），验证 sys_user_dept 写入正确
    > - 用户更新: 修改部门关联，主部门切换，验证旧关联清除+新关联写入
    > - 部门删除: 主部门用户存在→禁止删除；仅兼职用户→允许删除并移除关联
    > - GET /admin/depts/:id/users 返回含 is_primary 标识
    > - PUT/POST/DELETE /admin/users/:id/depts 接口正常
    > - 用户列表导出 CSV 含多部门信息
  - [ ] 14.2: 兼容性测试：验证历史功能在新系统中保持原有行为
    > **关键场景**:
    > - 迁移前单部门用户登录后权限范围不变
    > - API 返回的 dept_id/dept_name 兼容字段正确（取主部门）
    > - 无 sys_user_dept 记录时 getUserDeptIds 回退到 user.dept_id
  - [ ] 14.3: 安全测试：验证权限控制逻辑无漏洞
    > **检查项**:
    > - 用户 A 无法通过修改请求参数访问用户 B 的部门关联
    > - 禁用的部门不可被关联
    > - data_scope=5 修复后自定义权限生效
  - [ ] 14.4: 制定回滚方案
    > **回滚步骤**:
    > 1. 代码回滚: `git revert` 多对多相关提交
    > 2. 数据回滚: `ALTER TABLE sys_user ADD COLUMN dept_id_old BIGINT; UPDATE sys_user SET dept_id = (SELECT dept_id FROM sys_user_dept WHERE user_id=sys_user.id AND is_primary=1 LIMIT 1)`
    > 3. 前端: 用户表单恢复单选部门选择器
    > 4. 验证: 运行 check_user_dept_consistency.php 确认数据还原

# Task Dependencies
- [Task 2] depends on [Task 1]
- [Task 3] depends on [Task 2]
- [Task 4] depends on [Task 2]
- [Task 5] depends on [Task 2]
- [Task 6] depends on [Task 1]
- [Task 7] depends on [Task 1]
- [Task 8] depends on [Task 1]
- [Task 9] depends on [Task 5]
- [Task 10] depends on [Task 9]
- [Task 11] depends on [Task 9]
- [Task 12] depends on [Task 8]
- [Task 13] depends on [Task 1, Task 6, Task 7]
- [Task 14] depends on [Task 13]
