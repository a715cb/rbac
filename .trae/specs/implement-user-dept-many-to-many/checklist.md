# Checklist

## 阶段1：数据模型设计与基础CRUD
- [x] `sys_user_dept` 关联表已创建，字段和索引符合规范
- [x] 数据迁移脚本可将现有 `sys_user.dept_id` 正确迁移到 `sys_user_dept`
- [x] UserDept 模型已创建，关联关系定义正确
- [x] PUT `/admin/users/:id/depts` 接口实现全量替换部门关联
- [x] POST `/admin/users/:id/depts` 接口实现增量添加部门关联
- [x] DELETE `/admin/users/:id/depts/:deptId` 接口实现移除单个关联
- [x] 主部门唯一性校验生效（多条 is_primary=true 时拒绝）
- [x] 必须指定主部门校验生效（无 is_primary=true 时拒绝）
- [x] 用户创建时同步写入 `sys_user_dept` 并保持 `dept_id` 一致
- [x] 用户更新时同步更新 `sys_user_dept` 并保持 `dept_id` 一致
- [x] 用户列表接口返回 `depts` 多部门数组
- [x] 用户详情接口返回 `depts` 多部门数组
- [x] 兼容字段 `dept_id`/`dept_name` 取主部门值

## 阶段2：业务规则与权限规则
- [x] `getScopedDeptIds()` case 2 返回用户所有关联部门ID
- [x] `getScopedDeptIds()` case 3 对所有关联部门递归获取子部门ID并去重
- [x] `getScopedDeptIds()` case 5 返回 `dataScopeDeptIds`（修复自定义数据权限）
- [ ] 权限计算单元测试覆盖单部门、多部门、主部门变更、各 data_scope 场景
- [x] 删除包含主部门用户的部门时禁止删除并返回错误提示
- [x] 删除仅包含兼职用户的部门时自动移除关联后删除
- [x] 部门成员查询返回所有关联用户（含兼职），并标识 is_primary 状态

## 阶段3：前端改造
- [x] `api/user.ts` 新增 `UserDeptItem` 类型，`UserInfo` 新增 `depts` 字段
- [x] `UserForm` 接口添加 `depts` 数组字段（保留 `dept_id` 向后兼容）
- [x] 用户表单弹窗部门选择从单选改为多选
- [x] 用户表单支持主部门标识切换
- [x] 用户表单支持部门添加/移除
- [x] 用户列表部门列显示多个部门标签，主部门有视觉区分
- [x] 用户筛选按部门匹配所有关联部门
- [x] 部门成员弹窗显示 is_primary 状态和兼职标签

## 阶段4：数据迁移与验证
- [ ] 数据迁移脚本在测试环境正确执行
- [ ] `sys_user.dept_id` 与 `sys_user_dept` 主部门记录一致性校验通过
- [ ] 用户部门关系查询响应时间 < 200ms
- [ ] 批量操作处理能力 > 1000条/分钟
- [ ] 历史功能在新系统中保持原有行为
- [ ] 权限控制逻辑无安全漏洞
- [ ] 回滚方案已制定
