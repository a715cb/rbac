-- =============================================
-- RBAC 权限系统 - 统一初始化数据脚本
-- =============================================
-- 数据库: rbac_system
-- 整合时间: 2026-05-10
-- 说明: 创建初始管理员、角色、菜单、字典等数据，
--       并执行用户部门关联数据迁移
-- 整合来源:
--   002_init_data.sql (原始初始化数据)
--   006_migrate_user_dept_data.sql (用户部门数据迁移)
-- 默认密码: 123456 (bcrypt加密)
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE `rbac_system`;

-- =============================================
-- 1. 创建超级管理员角色
-- =============================================
INSERT INTO `sys_role` (`id`, `name`, `code`, `data_scope`, `status`, `sort`, `remark`, `created_by`, `create_time`, `updated_by`, `update_time`) VALUES
(1, '超级管理员', 'super_admin', 1, 1, 0, '系统超级管理员，拥有所有权限', NULL, NOW(), NULL, NULL);

-- =============================================
-- 2. 创建超级管理员用户 (密码: 123456)
-- =============================================
INSERT INTO `sys_user` (`id`, `username`, `password`, `nickname`, `email`, `mobile`, `avatar`, `gender`, `status`, `dept_id`, `remark`, `created_by`, `create_time`, `updated_by`, `update_time`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '超级管理员', 'admin@example.com', '13800138000', NULL, 1, 1, NULL, '系统超级管理员账号', NULL, NOW(), NULL, NULL);

-- =============================================
-- 3. 用户角色关联
-- =============================================
INSERT INTO `sys_user_role` (`user_id`, `role_id`, `create_time`) VALUES
(1, 1, NOW());

-- =============================================
-- 4. 创建系统管理目录
-- =============================================
INSERT INTO `sys_menu` (`id`, `parent_id`, `name`, `code`, `path`, `icon`, `component`, `menu_type`, `sort`, `visible`, `status`, `keep_alive`, `always_show`, `breadcrumb`, `active_menu`, `is_external`, `is_frame`, `remark`, `created_by`, `create_time`, `updated_by`, `update_time`) VALUES
(3, 0, '首页', 'dashboard', '/', 'ant-design:dashboard-outlined', NULL, 1, 0, 1, 1, 0, 0, 1, NULL, 0, 1, '首页目录', 1, NOW(), 1, NOW()),
(1, 0, '系统管理', 'system', '/system', 'ant-design:setting-outlined', NULL, 1, 100, 1, 1, 0, 1, 1, NULL, 0, 1, '系统管理模块', 1, NOW(), 1, NOW()),
(2, 0, '系统监控', 'monitor', '/monitor', 'ant-design:monitor-outlined', NULL, 1, 200, 1, 1, 0, 1, 1, NULL, 0, 1, '系统监控模块', 1, NOW(), 1, NOW());

-- =============================================
-- 4.1 创建首页子菜单
-- =============================================
INSERT INTO `sys_menu` (`id`, `parent_id`, `name`, `code`, `path`, `icon`, `component`, `menu_type`, `sort`, `visible`, `status`, `keep_alive`, `always_show`, `breadcrumb`, `active_menu`, `is_external`, `is_frame`, `remark`, `created_by`, `create_time`, `updated_by`, `update_time`) VALUES
(300, 3, '控制台', 'dashboard_console', '/dashboard', 'ant-design:dashboard-outlined', 'dashboard/index', 2, 100, 1, 1, 1, 0, 1, NULL, 0, 1, '控制台菜单', 1, NOW(), 1, NOW());

-- =============================================
-- 5. 创建系统管理子菜单
-- =============================================
INSERT INTO `sys_menu` (`id`, `parent_id`, `name`, `code`, `path`, `icon`, `component`, `menu_type`, `sort`, `visible`, `status`, `keep_alive`, `always_show`, `breadcrumb`, `active_menu`, `is_external`, `is_frame`, `remark`, `created_by`, `create_time`, `updated_by`, `update_time`) VALUES
(100, 1, '用户管理', 'system_user', '/system/user', 'ant-design:user-outlined', 'system/user/index', 2, 100, 1, 1, 1, 1, 1, NULL, 0, 1, '用户管理菜单', 1, NOW(), 1, NOW()),
(101, 1, '角色管理', 'system_role', '/system/role', 'ant-design:team-outlined', 'system/role/index', 2, 101, 1, 1, 0, 1, 1, NULL, 0, 1, '角色管理菜单', 1, NOW(), 1, NOW()),
(102, 1, '菜单管理', 'system_menu', '/system/menu', 'ant-design:menu-outlined', 'system/menu/index', 2, 102, 1, 1, 0, 1, 1, NULL, 0, 1, '菜单管理菜单', 1, NOW(), 1, NOW()),
(103, 1, '部门管理', 'system_dept', '/system/dept', 'ant-design:apartment-outlined', 'system/dept/index', 2, 103, 1, 1, 0, 1, 1, NULL, 0, 1, '部门管理菜单', 1, NOW(), 1, NOW()),
(104, 1, '接口管理', 'system_api', '/system/api', 'ant-design:api-outlined', 'system/api/index', 2, 104, 1, 1, 0, 1, 1, NULL, 0, 1, '接口管理菜单', 1, NOW(), 1, NOW()),
(105, 1, '字典管理', 'system_dict', '/system/dict', 'ant-design:book-outlined', 'system/dict/index', 2, 105, 1, 1, 0, 1, 1, NULL, 0, 1, '字典管理菜单', 1, NOW(), 1, NOW());

-- =============================================
-- 6. 创建监控子菜单
-- =============================================
INSERT INTO `sys_menu` (`id`, `parent_id`, `name`, `code`, `path`, `icon`, `component`, `menu_type`, `sort`, `visible`, `status`, `keep_alive`, `always_show`, `breadcrumb`, `active_menu`, `is_external`, `is_frame`, `remark`, `created_by`, `create_time`, `updated_by`, `update_time`) VALUES
(200, 2, '登录日志', 'monitor_login', '/monitor/login', 'ant-design:file-text-outlined', 'monitor/login/index', 2, 100, 1, 1, 0, 1, 1, NULL, 0, 1, '登录日志菜单', 1, NOW(), 1, NOW()),
(201, 2, '操作日志', 'monitor_operation', '/monitor/operation', 'ant-design:history-outlined', 'monitor/operation/index', 2, 101, 1, 1, 0, 1, 1, NULL, 0, 1, '操作日志菜单', 1, NOW(), 1, NOW());

-- =============================================
-- 7. 创建按钮权限（48条）
-- =============================================
INSERT INTO `sys_menu_button` (`id`, `menu_id`, `name`, `code`, `icon`, `sort`, `status`, `created_by`, `create_time`, `updated_by`, `update_time`) VALUES
(1001, 100, '新增用户', 'system_user:add', 'ant-design:plus-outlined', 1, 1, 1, NOW(), 1, NOW()),
(1002, 100, '编辑用户', 'system_user:edit', 'ant-design:edit-outlined', 2, 1, 1, NOW(), 1, NOW()),
(1003, 100, '删除用户', 'system_user:delete', 'ant-design:delete-outlined', 3, 1, 1, NOW(), 1, NOW()),
(1004, 100, '分配角色', 'system_user:assign', 'ant-design:usergroup-add-outlined', 4, 1, 1, NOW(), 1, NOW()),
(1005, 100, '重置密码', 'system_user:reset', 'ant-design:key-outlined', 5, 1, 1, NOW(), 1, NOW()),
(1006, 100, '导出数据', 'system_user:export', 'ant-design:download-outlined', 6, 1, 1, NOW(), 1, NOW()),
(1007, 100, '导入数据', 'system_user:import', 'ant-design:upload-outlined', 7, 1, 1, NOW(), 1, NOW()),

(1011, 101, '新增角色', 'permission_role:add', 'ant-design:plus-outlined', 1, 1, 1, NOW(), 1, NOW()),
(1012, 101, '编辑角色', 'permission_role:edit', 'ant-design:edit-outlined', 2, 1, 1, NOW(), 1, NOW()),
(1013, 101, '删除角色', 'permission_role:delete', 'ant-design:delete-outlined', 3, 1, 1, NOW(), 1, NOW()),
(1014, 101, '分配权限', 'permission_role:permission', 'ant-design:lock-outlined', 4, 1, 1, NOW(), 1, NOW()),
(1015, 101, '数据范围', 'permission_role:data_scope', 'ant-design:cluster-outlined', 5, 1, 1, NOW(), 1, NOW()),
(1016, 101, '状态切换', 'permission_role:status', 'ant-design:swap-outlined', 6, 1, 1, NOW(), 1, NOW()),

(1021, 102, '新增菜单', 'system_menu:add', 'ant-design:plus-outlined', 1, 1, 1, NOW(), 1, NOW()),
(1022, 102, '编辑菜单', 'system_menu:edit', 'ant-design:edit-outlined', 2, 1, 1, NOW(), 1, NOW()),
(1023, 102, '删除菜单', 'system_menu:delete', 'ant-design:delete-outlined', 3, 1, 1, NOW(), 1, NOW()),

(1031, 103, '新增部门', 'system_dept:add', 'ant-design:plus-outlined', 1, 1, 1, NOW(), 1, NOW()),
(1032, 103, '编辑部门', 'system_dept:edit', 'ant-design:edit-outlined', 2, 1, 1, NOW(), 1, NOW()),
(1033, 103, '删除部门', 'system_dept:delete', 'ant-design:delete-outlined', 3, 1, 1, NOW(), 1, NOW()),

(1041, 104, '新增接口', 'permission_api:add', 'ant-design:plus-outlined', 1, 1, NULL, NOW(), NULL, NOW()),
(1042, 104, '编辑接口', 'permission_api:edit', 'ant-design:edit-outlined', 2, 1, NULL, NOW(), NULL, NOW()),
(1043, 104, '删除接口', 'permission_api:delete', 'ant-design:delete-outlined', 3, 1, NULL, NOW(), NULL, NOW()),
(1044, 104, '状态切换', 'permission_api:status', 'ant-design:swap-outlined', 4, 1, NULL, NOW(), NULL, NOW()),

(1051, 105, '新增字典类型', 'system_dict:add_type', 'ant-design:plus-outlined', 1, 1, NULL, NOW(), NULL, NOW()),
(1052, 105, '编辑字典类型', 'system_dict:edit_type', 'ant-design:edit-outlined', 2, 1, NULL, NOW(), NULL, NOW()),
(1053, 105, '删除字典类型', 'system_dict:delete_type', 'ant-design:delete-outlined', 3, 1, NULL, NOW(), NULL, NOW()),
(1054, 105, '字典类型状态', 'system_dict:type_status', 'ant-design:swap-outlined', 4, 1, NULL, NOW(), NULL, NOW()),
(1055, 105, '新增字典数据', 'system_dict:add_data', 'ant-design:plus-outlined', 5, 1, NULL, NOW(), NULL, NOW()),
(1056, 105, '编辑字典数据', 'system_dict:edit_data', 'ant-design:edit-outlined', 6, 1, NULL, NOW(), NULL, NOW()),
(1057, 105, '删除字典数据', 'system_dict:delete_data', 'ant-design:delete-outlined', 7, 1, NULL, NOW(), NULL, NOW()),
(1058, 105, '字典数据状态', 'system_dict:data_status', 'ant-design:swap-outlined', 8, 1, NULL, NOW(), NULL, NOW()),
(1059, 105, '字典数据排序', 'system_dict:data_sort', 'ant-design:sort-ascending-outlined', 9, 1, NULL, NOW(), NULL, NOW()),

(2001, 200, '清理日志', 'admin:login-log:clean', 'ant-design:clear-outlined', 1, 1, NULL, NOW(), NULL, NOW()),
(2002, 200, '清空日志', 'admin:login-log:clear', 'ant-design:delete-outlined', 2, 1, NULL, NOW(), NULL, NOW()),
(2003, 200, '删除日志', 'admin:login-log:delete', 'ant-design:delete-outlined', 3, 1, NULL, NOW(), NULL, NOW()),
(2004, 200, '导出数据', 'admin:login-log:export', 'ant-design:download-outlined', 4, 1, NULL, NOW(), NULL, NOW()),

(2011, 201, '清理日志', 'admin:operation-log:clean', 'ant-design:clear-outlined', 1, 1, NULL, NOW(), NULL, NOW()),
(2012, 201, '清空日志', 'admin:operation-log:clear', 'ant-design:delete-outlined', 2, 1, NULL, NOW(), NULL, NOW()),
(2013, 201, '删除日志', 'admin:operation-log:delete', 'ant-design:delete-outlined', 3, 1, NULL, NOW(), NULL, NOW()),

(3011, 301, '新增按钮', 'permission_button:add', 'ant-design:plus-outlined', 1, 1, NULL, NOW(), NULL, NOW()),
(3012, 301, '编辑按钮', 'permission_button:edit', 'ant-design:edit-outlined', 2, 1, NULL, NOW(), NULL, NOW()),
(3013, 301, '删除按钮', 'permission_button:delete', 'ant-design:delete-outlined', 3, 1, NULL, NOW(), NULL, NOW()),
(3014, 301, '查看详情', 'permission_button:detail', 'ant-design:eye-outlined', 4, 1, NULL, NOW(), NULL, NOW()),
(3015, 301, '状态切换', 'permission_button:status', 'ant-design:swap-outlined', 5, 1, NULL, NOW(), NULL, NOW()),
(3016, 301, '批量启用', 'permission_button:batch_enable', 'ant-design:check-circle-outlined', 6, 1, NULL, NOW(), NULL, NOW()),
(3017, 301, '批量禁用', 'permission_button:batch_disable', 'ant-design:stop-outlined', 7, 1, NULL, NOW(), NULL, NOW()),
(3018, 301, '批量删除', 'permission_button:batch_delete', 'ant-design:delete-outlined', 8, 1, NULL, NOW(), NULL, NOW());

-- =============================================
-- 8. 角色菜单关联（超级管理员拥有所有菜单）
-- =============================================
INSERT INTO `sys_role_menu` (`role_id`, `menu_id`, `create_time`)
SELECT 1, id, NOW() FROM `sys_menu` WHERE `status` = 1;

-- =============================================
-- 9. 角色菜单按钮关联（超级管理员拥有所有按钮）
-- =============================================
INSERT INTO `sys_role_menu_button` (`role_id`, `menu_button_id`, `create_time`)
SELECT 1, id, NOW() FROM `sys_menu_button` WHERE `status` = 1;

-- 管理员角色(role_id=2)的按钮关联：全部按钮
INSERT INTO `sys_role_menu_button` (`role_id`, `menu_button_id`, `create_time`)
SELECT 2, id, NOW() FROM `sys_menu_button` WHERE `status` = 1;

-- 人事经理角色(role_id=4)的按钮关联：用户管理 + 部门管理按钮
INSERT INTO `sys_role_menu_button` (`role_id`, `menu_button_id`, `create_time`) VALUES
(4, 1001, NOW()), (4, 1002, NOW()), (4, 1003, NOW()), (4, 1004, NOW()), (4, 1005, NOW()),
(4, 1006, NOW()), (4, 1007, NOW()),
(4, 1031, NOW()), (4, 1032, NOW()), (4, 1033, NOW());

-- 普通用户角色(role_id=3)的按钮关联：日志删除和导出
INSERT INTO `sys_role_menu_button` (`role_id`, `menu_button_id`, `create_time`) VALUES
(3, 2003, NOW()), (3, 2004, NOW()), (3, 2013, NOW());

-- =============================================
-- 10. 创建字典类型
-- =============================================
INSERT INTO `sys_dict_type` (`id`, `name`, `code`, `type`, `status`, `remark`, `created_by`, `create_time`, `updated_by`, `update_time`) VALUES
(1, '用户性别', 'user_gender', 'string', 1, '用户性别字典', 1, NOW(), 1, NOW()),
(2, '用户状态', 'user_status', 'string', 1, '用户状态字典', 1, NOW(), 1, NOW()),
(3, '菜单类型', 'menu_type', 'string', 1, '菜单类型字典', 1, NOW(), 1, NOW()),
(4, '数据权限', 'data_scope', 'number', 1, '数据权限范围字典', 1, NOW(), 1, NOW()),
(5, '是否', 'yes_no', 'string', 1, '是/否字典', 1, NOW(), 1, NOW()),
(6, '角色状态', 'role_status', 'number', 1, '角色状态字典', 1, NOW(), 1, NOW());

-- =============================================
-- 11. 创建字典数据
-- =============================================
INSERT INTO `sys_dict_data` (`dict_type_id`, `label`, `value`, `sort`, `status`, `remark`, `created_by`, `create_time`, `updated_by`, `update_time`) VALUES
(1, '未知', '0', 0, 1, NULL, 1, NOW(), 1, NOW()),
(1, '男', '1', 1, 1, NULL, 1, NOW(), 1, NOW()),
(1, '女', '2', 2, 1, NULL, 1, NOW(), 1, NOW()),

(2, '正常', '1', 1, 1, NULL, 1, NOW(), 1, NOW()),
(2, '禁用', '0', 2, 1, NULL, 1, NOW(), 1, NOW()),

(3, '目录', '1', 1, 1, NULL, 1, NOW(), 1, NOW()),
(3, '菜单', '2', 2, 1, NULL, 1, NOW(), 1, NOW()),
(3, '按钮', '3', 3, 1, NULL, 1, NOW(), 1, NOW()),

(4, '全部数据', '1', 1, 1, NULL, 1, NOW(), 1, NOW()),
(4, '本部门数据', '2', 2, 1, NULL, 1, NOW(), 1, NOW()),
(4, '本部门及以下数据', '3', 3, 1, NULL, 1, NOW(), 1, NOW()),
(4, '仅本人数据', '4', 4, 1, NULL, 1, NOW(), 1, NOW()),
(4, '自定义数据', '5', 5, 1, NULL, 1, NOW(), 1, NOW()),

(5, '是', '1', 1, 1, NULL, 1, NOW(), 1, NOW()),
(5, '否', '0', 2, 1, NULL, 1, NOW(), 1, NOW()),

(6, '正常', '1', 1, 1, NULL, 1, NOW(), 1, NOW()),
(6, '禁用', '0', 2, 1, NULL, 1, NOW(), 1, NOW());

-- =============================================
-- 12. 创建系统配置
-- =============================================
INSERT INTO `sys_config` (`id`, `name`, `code`, `value`, `type`, `status`, `remark`, `created_by`, `create_time`, `updated_by`, `update_time`) VALUES
(1, '登录验证码', 'login_captcha', 'true', 'string', 1, '是否启用登录验证码', 1, NOW(), 1, NOW()),
(2, '密码最小长度', 'password_min_length', '6', 'number', 1, '密码最小长度要求', 1, NOW(), 1, NOW()),
(3, '登录失败锁定次数', 'login_fail_lock_count', '5', 'number', 1, '连续失败次数后锁定账户', 1, NOW(), 1, NOW()),
(4, '登录失败锁定时间', 'login_fail_lock_minutes', '15', 'number', 1, '账户锁定时间（分钟）', 1, NOW(), 1, NOW()),
(5, 'Token有效期', 'token_expire_hours', '2', 'number', 1, 'Access Token有效期（小时）', 1, NOW(), 1, NOW()),
(6, '刷新Token有效期', 'refresh_token_expire_days', '7', 'number', 1, '刷新Token有效期（天）', 1, NOW(), 1, NOW());

-- =============================================
-- 13. 创建初始API接口数据
-- =============================================
INSERT INTO `sys_api` (`id`, `menu_id`, `name`, `code`, `method`, `path`, `group`, `status`, `created_by`, `create_time`, `updated_by`, `update_time`) VALUES
-- 认证接口
(1001, NULL, '管理员登录', 'admin:auth:login', 'POST', '/admin/login', 'auth', 1, 1, NOW(), 1, NOW()),
(1002, NULL, '管理员登出', 'admin:auth:logout', 'POST', '/admin/logout', 'auth', 1, 1, NOW(), 1, NOW()),
(1003, NULL, '刷新Token', 'admin:auth:refresh', 'POST', '/admin/refresh-token', 'auth', 1, 1, NOW(), 1, NOW()),
(1004, NULL, '获取个人信息', 'admin:auth:profile', 'GET', '/admin/profile', 'auth', 1, 1, NOW(), 1, NOW()),
(1005, NULL, '修改密码', 'admin:auth:password', 'PUT', '/admin/password', 'auth', 1, 1, NOW(), 1, NOW()),

-- 用户管理接口
(2001, 100, '用户列表', 'admin:user:list', 'GET', '/admin/users', 'user', 1, 1, NOW(), 1, NOW()),
(2002, 100, '用户详情', 'admin:user:detail', 'GET', '/admin/users/:id', 'user', 1, 1, NOW(), 1, NOW()),
(2003, 100, '创建用户', 'admin:user:create', 'POST', '/admin/users', 'user', 1, 1, NOW(), 1, NOW()),
(2004, 100, '更新用户', 'admin:user:update', 'PUT', '/admin/users/:id', 'user', 1, 1, NOW(), 1, NOW()),
(2005, 100, '删除用户', 'admin:user:delete', 'DELETE', '/admin/users/:id', 'user', 1, 1, NOW(), 1, NOW()),
(2006, 100, '分配角色', 'admin:user:assign', 'POST', '/admin/users/:id/roles', 'user', 1, 1, NOW(), 1, NOW()),
(2007, 100, '重置密码', 'admin:user:reset', 'POST', '/admin/users/:id/reset-password', 'user', 1, 1, NOW(), 1, NOW()),
(2008, 100, '导出用户', 'admin:user:export', 'GET', '/admin/users/export', 'user', 1, 1, NOW(), 1, NOW()),
(2009, 100, '导入用户', 'admin:user:import', 'POST', '/admin/users/import', 'user', 1, 1, NOW(), 1, NOW()),
(2010, 100, '修改用户状态', 'admin:user:changeStatus', 'PUT', '/admin/users/:id/status', 'user', 1, 1, NOW(), 1, NOW()),

-- 角色管理接口
(3001, 101, '角色列表', 'admin:role:list', 'GET', '/admin/roles', 'role', 1, 1, NOW(), 1, NOW()),
(3002, 101, '角色详情', 'admin:role:detail', 'GET', '/admin/roles/:id', 'role', 1, 1, NOW(), 1, NOW()),
(3003, 101, '创建角色', 'admin:role:create', 'POST', '/admin/roles', 'role', 1, 1, NOW(), 1, NOW()),
(3004, 101, '更新角色', 'admin:role:update', 'PUT', '/admin/roles/:id', 'role', 1, 1, NOW(), 1, NOW()),
(3005, 101, '删除角色', 'admin:role:delete', 'DELETE', '/admin/roles/:id', 'role', 1, 1, NOW(), 1, NOW()),
(3006, 101, '分配菜单权限', 'admin:role:assignMenu', 'POST', '/admin/roles/:id/menus', 'role', 1, 1, NOW(), 1, NOW()),
(3007, 101, '分配按钮权限', 'admin:role:assignButton', 'POST', '/admin/roles/:id/buttons', 'role', 1, 1, NOW(), 1, NOW()),
(3008, 101, '分配API权限', 'admin:role:assignApi', 'POST', '/admin/roles/:id/apis', 'role', 1, 1, NOW(), 1, NOW()),
(3009, 101, '设置数据范围', 'admin:role:setDataScope', 'PUT', '/admin/roles/:id/data-scope', 'role', 1, 1, NOW(), 1, NOW()),
(3010, 101, '修改角色状态', 'admin:role:changeStatus', 'PUT', '/admin/roles/:id/status', 'role', 1, 1, NOW(), 1, NOW()),

-- 菜单管理接口
(4001, 102, '菜单列表', 'admin:menu:list', 'GET', '/admin/menus', 'menu', 1, 1, NOW(), 1, NOW()),
(4002, 102, '菜单树形', 'admin:menu:tree', 'GET', '/admin/menus/tree', 'menu', 1, 1, NOW(), 1, NOW()),
(4003, 102, '菜单详情', 'admin:menu:detail', 'GET', '/admin/menus/:id', 'menu', 1, 1, NOW(), 1, NOW()),
(4004, 102, '创建菜单', 'admin:menu:create', 'POST', '/admin/menus', 'menu', 1, 1, NOW(), 1, NOW()),
(4005, 102, '更新菜单', 'admin:menu:update', 'PUT', '/admin/menus/:id', 'menu', 1, 1, NOW(), 1, NOW()),
(4006, 102, '删除菜单', 'admin:menu:delete', 'DELETE', '/admin/menus/:id', 'menu', 1, 1, NOW(), 1, NOW()),
(4007, 102, '获取菜单按钮', 'admin:menu:getButtons', 'GET', '/admin/menus/:id/buttons', 'menu', 1, 1, NOW(), 1, NOW()),
(4008, 102, '创建菜单按钮', 'admin:menu:createButton', 'POST', '/admin/menus/:id/buttons', 'menu', 1, 1, NOW(), 1, NOW()),
(4009, 102, '更新菜单按钮', 'admin:menu:updateButton', 'PUT', '/admin/menus/:id/buttons/:buttonId', 'menu', 1, 1, NOW(), 1, NOW()),
(4010, 102, '删除菜单按钮', 'admin:menu:deleteButton', 'DELETE', '/admin/menus/:id/buttons/:buttonId', 'menu', 1, 1, NOW(), 1, NOW()),

-- 部门管理接口
(5001, 103, '部门列表', 'admin:dept:list', 'GET', '/admin/depts', 'dept', 1, 1, NOW(), 1, NOW()),
(5002, 103, '部门树形', 'admin:dept:tree', 'GET', '/admin/depts/tree', 'dept', 1, 1, NOW(), 1, NOW()),
(5003, 103, '部门详情', 'admin:dept:detail', 'GET', '/admin/depts/:id', 'dept', 1, 1, NOW(), 1, NOW()),
(5004, 103, '创建部门', 'admin:dept:create', 'POST', '/admin/depts', 'dept', 1, 1, NOW(), 1, NOW()),
(5005, 103, '更新部门', 'admin:dept:update', 'PUT', '/admin/depts/:id', 'dept', 1, 1, NOW(), 1, NOW()),
(5006, 103, '删除部门', 'admin:dept:delete', 'DELETE', '/admin/depts/:id', 'dept', 1, 1, NOW(), 1, NOW()),
(5007, 103, '修改部门状态', 'admin:dept:setStatus', 'PUT', '/admin/depts/:id/status', 'dept', 1, 1, NOW(), 1, NOW()),
(5008, 103, '部门排序', 'admin:dept:setSort', 'PUT', '/admin/depts/:id/sort', 'dept', 1, 1, NOW(), 1, NOW()),
(5009, 103, '部门用户列表', 'admin:dept:getUsers', 'GET', '/admin/depts/:id/users', 'dept', 1, 1, NOW(), 1, NOW()),

-- 接口管理接口
(6001, 104, '接口列表', 'admin:api:list', 'GET', '/admin/apis', 'api', 1, 1, NOW(), 1, NOW()),
(6002, 104, '接口详情', 'admin:api:detail', 'GET', '/admin/apis/:id', 'api', 1, 1, NOW(), 1, NOW()),
(6003, 104, '创建接口', 'admin:api:create', 'POST', '/admin/apis', 'api', 1, 1, NOW(), 1, NOW()),
(6004, 104, '更新接口', 'admin:api:update', 'PUT', '/admin/apis/:id', 'api', 1, 1, NOW(), 1, NOW()),
(6005, 104, '删除接口', 'admin:api:delete', 'DELETE', '/admin/apis/:id', 'api', 1, 1, NOW(), 1, NOW()),
(6006, 104, '接口分组列表', 'admin:api:getGroups', 'GET', '/admin/apis/groups', 'api', 1, 1, NOW(), 1, NOW()),
(6007, 104, '按菜单获取接口', 'admin:api:getByMenu', 'GET', '/admin/apis/menu/:menuId', 'api', 1, 1, NOW(), 1, NOW()),
(6008, 104, '修改接口状态', 'admin:api:setStatus', 'PUT', '/admin/apis/:id/status', 'api', 1, 1, NOW(), 1, NOW()),

-- 字典管理接口
(7001, 105, '字典类型列表', 'admin:dict:typeList', 'GET', '/admin/dict/types', 'dict', 1, 1, NOW(), 1, NOW()),
(7002, 105, '字典类型详情', 'admin:dict:typeDetail', 'GET', '/admin/dict/types/:id', 'dict', 1, 1, NOW(), 1, NOW()),
(7003, 105, '创建字典类型', 'admin:dict:typeCreate', 'POST', '/admin/dict/types', 'dict', 1, 1, NOW(), 1, NOW()),
(7004, 105, '更新字典类型', 'admin:dict:typeUpdate', 'PUT', '/admin/dict/types/:id', 'dict', 1, 1, NOW(), 1, NOW()),
(7005, 105, '删除字典类型', 'admin:dict:typeDelete', 'DELETE', '/admin/dict/types/:id', 'dict', 1, 1, NOW(), 1, NOW()),
(7006, 105, '字典数据列表', 'admin:dict:dataList', 'GET', '/admin/dict/data', 'dict', 1, 1, NOW(), 1, NOW()),
(7007, 105, '字典数据详情', 'admin:dict:dataDetail', 'GET', '/admin/dict/data/:id', 'dict', 1, 1, NOW(), 1, NOW()),
(7008, 105, '创建字典数据', 'admin:dict:dataCreate', 'POST', '/admin/dict/data', 'dict', 1, 1, NOW(), 1, NOW()),
(7009, 105, '更新字典数据', 'admin:dict:dataUpdate', 'PUT', '/admin/dict/data/:id', 'dict', 1, 1, NOW(), 1, NOW()),
(7010, 105, '删除字典数据', 'admin:dict:dataDelete', 'DELETE', '/admin/dict/data/:id', 'dict', 1, 1, NOW(), 1, NOW()),
(7011, 105, '修改字典类型状态', 'admin:dict:typeChangeStatus', 'PUT', '/admin/dict/types/:id/status', 'dict', 1, 1, NOW(), 1, NOW()),
(7012, 105, '字典数据排序', 'admin:dict:dataUpdateSort', 'POST', '/admin/dict/data/sort', 'dict', 1, 1, NOW(), 1, NOW()),
(7013, 105, '修改字典数据状态', 'admin:dict:dataChangeStatus', 'PUT', '/admin/dict/data/:id/status', 'dict', 1, 1, NOW(), 1, NOW()),
(7014, 105, '按编码获取字典', 'admin:dict:dictByCode', 'GET', '/admin/dict/code/:code', 'dict', 1, 1, NOW(), 1, NOW()),

-- 登录日志接口
(8001, 200, '登录日志列表', 'admin:loginLog:list', 'GET', '/admin/login-logs', 'log', 1, 1, NOW(), 1, NOW()),
(8002, 200, '登录日志统计', 'admin:loginLog:stats', 'GET', '/admin/login-logs/stats', 'log', 1, 1, NOW(), 1, NOW()),
(8003, 200, '清理登录日志', 'admin:loginLog:clean', 'POST', '/admin/login-logs/clean', 'log', 1, 1, NOW(), 1, NOW()),
(8004, 200, '清空登录日志', 'admin:loginLog:clear', 'POST', '/admin/login-logs/clear', 'log', 1, 1, NOW(), 1, NOW()),
(8005, 200, '删除登录日志', 'admin:loginLog:delete', 'POST', '/admin/login-logs/delete', 'log', 1, 1, NOW(), 1, NOW()),

-- 操作日志接口
(9001, 201, '操作日志列表', 'admin:operationLog:list', 'GET', '/admin/operation-logs', 'log', 1, 1, NOW(), 1, NOW()),
(9002, 201, '操作日志统计', 'admin:operationLog:stats', 'GET', '/admin/operation-logs/stats', 'log', 1, 1, NOW(), 1, NOW()),
(9003, 201, '清理操作日志', 'admin:operationLog:clean', 'POST', '/admin/operation-logs/clean', 'log', 1, 1, NOW(), 1, NOW()),
(9004, 201, '清空操作日志', 'admin:operationLog:clear', 'POST', '/admin/operation-logs/clear', 'log', 1, 1, NOW(), 1, NOW()),
(9005, 201, '删除操作日志', 'admin:operationLog:delete', 'POST', '/admin/operation-logs/delete', 'log', 1, 1, NOW(), 1, NOW()),

-- 仪表盘接口
(10001, NULL, '仪表盘统计', 'admin:dashboard:statistics', 'GET', '/admin/dashboard/statistics', 'dashboard', 1, 1, NOW(), 1, NOW()),

-- 个人信息接口
(11001, NULL, '获取个人信息', 'admin:profile:show', 'GET', '/admin/profile', 'profile', 1, 1, NOW(), 1, NOW()),
(11002, NULL, '更新个人信息', 'admin:profile:update', 'PUT', '/admin/profile', 'profile', 1, 1, NOW(), 1, NOW()),
(11003, NULL, '上传头像', 'admin:profile:avatar', 'POST', '/admin/profile/avatar', 'profile', 1, 1, NOW(), 1, NOW()),
(11004, NULL, '修改个人密码', 'admin:profile:password', 'PUT', '/admin/profile/password', 'profile', 1, 1, NOW(), 1, NOW());

-- =============================================
-- 14. 角色接口关联（超级管理员拥有所有接口）
-- =============================================
INSERT INTO `sys_role_api` (`role_id`, `api_id`, `create_time`)
SELECT 1, id, NOW() FROM `sys_api` WHERE `status` = 1;

-- =============================================
-- 15. 用户部门关联数据迁移（合并自 006_migrate_user_dept_data.sql）
-- =============================================
-- 将 sys_user.dept_id 迁移到 sys_user_dept，建立多对多关联
-- sys_user.dept_id 保留为冗余字段（主部门），与 sys_user_dept.is_primary=1 同步
INSERT INTO `sys_user_dept` (`user_id`, `dept_id`, `is_primary`, `sort`, `create_time`)
SELECT `id`, `dept_id`, 1, 0, NOW()
FROM `sys_user`
WHERE `dept_id` IS NOT NULL AND `dept_id` > 0
AND NOT EXISTS (
  SELECT 1 FROM `sys_user_dept` WHERE `sys_user_dept`.`user_id` = `sys_user`.`id` AND `sys_user_dept`.`dept_id` = `sys_user`.`dept_id`
);

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- 执行完成
-- =============================================
-- 初始化数据说明:
-- 1. 默认管理员账号: admin / 123456
-- 2. 超级管理员拥有所有菜单、按钮、接口权限
-- 3. 包含完整的菜单树和按钮权限
-- 4. 包含常用字典数据和系统配置
-- 5. 已将 sys_user.dept_id 数据迁移到 sys_user_dept（多对多关联）
-- =============================================
