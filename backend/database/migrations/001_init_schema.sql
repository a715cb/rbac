-- =============================================
-- RBAC 权限系统 - 统一数据库架构脚本
-- =============================================
-- 数据库: rbac_system
-- 整合时间: 2026-05-10
-- 说明: 基于实际数据库结构整合 001~006 迁移脚本，
--       修复已知问题，补充缺失表，优化索引设计
-- 整合来源:
--   001_init_schema.sql (核心15表)
--   003_add_user_unique_indexes.sql (唯一索引，已合并，改为条件索引)
--   004_wx_tables.sql (微信2表)
--   005_create_user_dept_table.sql (用户部门关联表)
--   新增: business, business_interaction (代码已有但迁移缺失)
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `rbac_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rbac_system`;

-- =============================================
-- 1. 用户表
-- =============================================
-- 变更说明:
--   - 保留 dept_id 字段（主部门，向后兼容，代码中大量使用）
--   - email/mobile 唯一索引改为条件索引（排除软删除记录）
--     原方案 uk_email/uk_mobile 不区分软删除，会导致软删除后无法复用
--   - 新增 idx_mobile 普通索引（实际数据库已有）
-- =============================================
DROP TABLE IF EXISTS `sys_user`;
CREATE TABLE `sys_user` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  `password` VARCHAR(255) NOT NULL COMMENT '密码（bcrypt哈希）',
  `nickname` VARCHAR(50) DEFAULT NULL COMMENT '昵称',
  `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
  `mobile` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
  `avatar` VARCHAR(255) DEFAULT NULL COMMENT '头像',
  `gender` TINYINT DEFAULT 0 COMMENT '性别：0未知 1男 2女',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `dept_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '主部门ID（冗余字段，与sys_user_dept.is_primary=1同步）',
  `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '最后登录IP',
  `last_login_time` DATETIME DEFAULT NULL COMMENT '最后登录时间',
  `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `update_time` DATETIME DEFAULT NULL COMMENT '更新时间',
  `delete_time` DATETIME DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_dept_id` (`dept_id`),
  KEY `idx_status` (`status`),
  KEY `idx_mobile` (`mobile`),
  KEY `idx_email_active` (`email`, `delete_time`),
  KEY `idx_mobile_active` (`mobile`, `delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- =============================================
-- 2. 部门表
-- =============================================
DROP TABLE IF EXISTS `sys_department`;
CREATE TABLE `sys_department` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '部门ID',
  `parent_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父部门ID',
  `name` VARCHAR(50) NOT NULL COMMENT '部门名称',
  `code` VARCHAR(50) NOT NULL COMMENT '部门编码',
  `leader` VARCHAR(50) DEFAULT NULL COMMENT '负责人',
  `phone` VARCHAR(20) DEFAULT NULL COMMENT '联系电话',
  `email` VARCHAR(100) DEFAULT NULL COMMENT '邮箱',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `update_time` DATETIME DEFAULT NULL COMMENT '更新时间',
  `delete_time` DATETIME DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='部门表';

-- =============================================
-- 3. 角色表
-- =============================================
DROP TABLE IF EXISTS `sys_role`;
CREATE TABLE `sys_role` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
  `code` VARCHAR(50) NOT NULL COMMENT '角色编码',
  `data_scope` TINYINT NOT NULL DEFAULT 1 COMMENT '数据权限：1全部 2本部门 3本部门及以下 4仅本人 5自定义',
  `data_scope_dept_ids` VARCHAR(500) DEFAULT NULL COMMENT '自定义数据权限部门ID列表',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `update_time` DATETIME DEFAULT NULL COMMENT '更新时间',
  `delete_time` DATETIME DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';

-- =============================================
-- 4. 用户角色关联表
-- =============================================
DROP TABLE IF EXISTS `sys_user_role`;
CREATE TABLE `sys_user_role` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
  `role_id` BIGINT UNSIGNED NOT NULL COMMENT '角色ID',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role` (`user_id`, `role_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户角色关联表';

-- =============================================
-- 5. 用户部门关联表（多对多）
-- =============================================
-- 变更说明:
--   - 从 005_create_user_dept_table.sql 合并
--   - 与 sys_user.dept_id 形成双写：dept_id 为主部门冗余字段
-- =============================================
DROP TABLE IF EXISTS `sys_user_dept`;
CREATE TABLE `sys_user_dept` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
  `dept_id` BIGINT UNSIGNED NOT NULL COMMENT '部门ID',
  `is_primary` TINYINT NOT NULL DEFAULT 0 COMMENT '是否主部门：0否 1是',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_dept` (`user_id`, `dept_id`),
  KEY `idx_dept_id` (`dept_id`),
  KEY `idx_is_primary` (`user_id`, `is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户部门关联表';

-- =============================================
-- 6. 菜单表
-- =============================================
DROP TABLE IF EXISTS `sys_menu`;
CREATE TABLE `sys_menu` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '菜单ID',
  `parent_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父菜单ID',
  `name` VARCHAR(50) NOT NULL COMMENT '菜单名称',
  `code` VARCHAR(100) NOT NULL COMMENT '菜单标识',
  `path` VARCHAR(200) DEFAULT NULL COMMENT '路由路径',
  `icon` VARCHAR(100) DEFAULT NULL COMMENT '图标',
  `component` VARCHAR(255) DEFAULT NULL COMMENT '组件路径',
  `menu_type` TINYINT NOT NULL COMMENT '菜单类型：1目录 2菜单 3按钮',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `visible` TINYINT DEFAULT 1 COMMENT '显示状态：0隐藏 1显示',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `keep_alive` TINYINT DEFAULT 1 COMMENT '是否缓存：0否 1是',
  `always_show` TINYINT DEFAULT 1 COMMENT '是否总是显示：0否 1是',
  `breadcrumb` TINYINT DEFAULT 1 COMMENT '是否显示面包屑：0否 1是',
  `active_menu` VARCHAR(255) DEFAULT NULL COMMENT '高亮菜单',
  `is_external` TINYINT DEFAULT 0 COMMENT '是否外链：0否 1是',
  `is_frame` TINYINT DEFAULT 1 COMMENT '是否iframe：0否 1是',
  `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `update_time` DATETIME DEFAULT NULL COMMENT '更新时间',
  `delete_time` DATETIME DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_menu_type` (`menu_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='菜单表';

-- =============================================
-- 7. 角色菜单关联表
-- =============================================
DROP TABLE IF EXISTS `sys_role_menu`;
CREATE TABLE `sys_role_menu` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `role_id` BIGINT UNSIGNED NOT NULL COMMENT '角色ID',
  `menu_id` BIGINT UNSIGNED NOT NULL COMMENT '菜单ID',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_menu` (`role_id`, `menu_id`),
  KEY `idx_menu_id` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色菜单关联表';

-- =============================================
-- 8. 菜单按钮表
-- =============================================
DROP TABLE IF EXISTS `sys_menu_button`;
CREATE TABLE `sys_menu_button` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '按钮ID',
  `menu_id` BIGINT UNSIGNED NOT NULL COMMENT '菜单ID',
  `name` VARCHAR(50) NOT NULL COMMENT '按钮名称',
  `code` VARCHAR(100) NOT NULL COMMENT '按钮编码',
  `icon` VARCHAR(100) DEFAULT NULL COMMENT '按钮图标',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `update_time` DATETIME DEFAULT NULL COMMENT '更新时间',
  `delete_time` DATETIME DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_menu_button` (`menu_id`, `code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='菜单按钮表';

-- =============================================
-- 9. 角色菜单按钮关联表
-- =============================================
DROP TABLE IF EXISTS `sys_role_menu_button`;
CREATE TABLE `sys_role_menu_button` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `role_id` BIGINT UNSIGNED NOT NULL COMMENT '角色ID',
  `menu_button_id` BIGINT UNSIGNED NOT NULL COMMENT '菜单按钮ID',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_button` (`role_id`, `menu_button_id`),
  KEY `idx_button_id` (`menu_button_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色菜单按钮关联表';

-- =============================================
-- 10. 接口表
-- =============================================
DROP TABLE IF EXISTS `sys_api`;
CREATE TABLE `sys_api` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '接口ID',
  `menu_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '所属菜单',
  `name` VARCHAR(100) NOT NULL COMMENT '接口名称',
  `code` VARCHAR(100) NOT NULL COMMENT '接口标识',
  `method` VARCHAR(10) NOT NULL COMMENT '请求方法：GET/POST/PUT/DELETE',
  `path` VARCHAR(200) NOT NULL COMMENT '接口路径',
  `group` VARCHAR(50) DEFAULT NULL COMMENT '接口分组',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `update_time` DATETIME DEFAULT NULL COMMENT '更新时间',
  `delete_time` DATETIME DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  UNIQUE KEY `uk_method_path` (`method`, `path`),
  KEY `idx_menu_id` (`menu_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='接口表';

-- =============================================
-- 11. 角色接口关联表
-- =============================================
DROP TABLE IF EXISTS `sys_role_api`;
CREATE TABLE `sys_role_api` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `role_id` BIGINT UNSIGNED NOT NULL COMMENT '角色ID',
  `api_id` BIGINT UNSIGNED NOT NULL COMMENT '接口ID',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_api` (`role_id`, `api_id`),
  KEY `idx_api_id` (`api_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色接口关联表';

-- =============================================
-- 12. 登录日志表
-- =============================================
DROP TABLE IF EXISTS `sys_login_log`;
CREATE TABLE `sys_login_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  `ip` VARCHAR(50) DEFAULT NULL COMMENT '登录IP',
  `address` VARCHAR(255) DEFAULT NULL COMMENT '登录地址',
  `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'User-Agent',
  `os` VARCHAR(100) DEFAULT NULL COMMENT '操作系统',
  `browser` VARCHAR(100) DEFAULT NULL COMMENT '浏览器',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '登录状态：0失败 1成功',
  `msg` VARCHAR(255) DEFAULT NULL COMMENT '提示消息',
  `login_time` DATETIME NOT NULL COMMENT '登录时间',
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_login_time` (`login_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='登录日志表';

-- =============================================
-- 13. 操作日志表
-- =============================================
DROP TABLE IF EXISTS `sys_operation_log`;
CREATE TABLE `sys_operation_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `user_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '用户ID',
  `username` VARCHAR(50) DEFAULT NULL COMMENT '用户名',
  `module` VARCHAR(100) DEFAULT NULL COMMENT '操作模块',
  `action` VARCHAR(100) DEFAULT NULL COMMENT '操作功能',
  `method` VARCHAR(10) DEFAULT NULL COMMENT '请求方法',
  `url` VARCHAR(500) DEFAULT NULL COMMENT '请求地址',
  `ip` VARCHAR(50) DEFAULT NULL COMMENT 'IP地址',
  `address` VARCHAR(255) DEFAULT NULL COMMENT '操作地址',
  `param` TEXT DEFAULT NULL COMMENT '请求参数',
  `result` TEXT DEFAULT NULL COMMENT '返回结果',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '操作状态：0异常 1正常',
  `error_msg` TEXT DEFAULT NULL COMMENT '错误信息',
  `duration` INT DEFAULT NULL COMMENT '耗时（毫秒）',
  `create_time` DATETIME DEFAULT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_username` (`username`),
  KEY `idx_action` (`action`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

-- =============================================
-- 14. 字典类型表
-- =============================================
DROP TABLE IF EXISTS `sys_dict_type`;
CREATE TABLE `sys_dict_type` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '字典ID',
  `name` VARCHAR(100) NOT NULL COMMENT '字典名称',
  `code` VARCHAR(100) NOT NULL COMMENT '字典编码',
  `type` VARCHAR(50) NOT NULL DEFAULT 'string' COMMENT '类型：string/number/date/time',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `update_time` DATETIME DEFAULT NULL COMMENT '更新时间',
  `delete_time` DATETIME DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code_delete_time` (`code`, `delete_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典类型表';

-- =============================================
-- 15. 字典数据表
-- =============================================
DROP TABLE IF EXISTS `sys_dict_data`;
CREATE TABLE `sys_dict_data` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '字典数据ID',
  `dict_type_id` BIGINT UNSIGNED NOT NULL COMMENT '字典类型ID',
  `label` VARCHAR(100) NOT NULL COMMENT '字典标签',
  `value` VARCHAR(100) NOT NULL COMMENT '字典键值',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `update_time` DATETIME DEFAULT NULL COMMENT '更新时间',
  `delete_time` DATETIME DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_value_delete_time` (`dict_type_id`, `value`, `delete_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典数据表';

-- =============================================
-- 16. 系统配置表
-- =============================================
DROP TABLE IF EXISTS `sys_config`;
CREATE TABLE `sys_config` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `name` VARCHAR(100) NOT NULL COMMENT '配置名称',
  `code` VARCHAR(100) NOT NULL COMMENT '配置编码',
  `value` TEXT DEFAULT NULL COMMENT '配置值',
  `type` VARCHAR(50) NOT NULL DEFAULT 'string' COMMENT '类型：string/number/json/xml',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `remark` VARCHAR(500) DEFAULT NULL COMMENT '备注',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `update_time` DATETIME DEFAULT NULL COMMENT '更新时间',
  `delete_time` DATETIME DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- =============================================
-- 17. 微信用户表
-- =============================================
-- 变更说明:
--   - 从 004_wx_tables.sql 合并
--   - 移除 delete_time 字段（实际数据库无此字段，WxUser模型未使用软删除）
-- =============================================
DROP TABLE IF EXISTS `wx_user`;
CREATE TABLE `wx_user` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `openid` VARCHAR(64) NOT NULL COMMENT '微信openid',
  `unionid` VARCHAR(64) DEFAULT NULL COMMENT '微信unionid',
  `session_key` VARCHAR(128) DEFAULT NULL COMMENT '会话密钥',
  `nickname` VARCHAR(50) DEFAULT NULL COMMENT '昵称',
  `avatar` VARCHAR(500) DEFAULT NULL COMMENT '头像URL',
  `phone` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
  `gender` TINYINT DEFAULT 0 COMMENT '性别：0未知 1男 2女',
  `sys_user_id` BIGINT UNSIGNED DEFAULT NULL COMMENT '关联系统用户ID',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  `update_time` DATETIME DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_openid` (`openid`),
  KEY `idx_unionid` (`unionid`),
  KEY `idx_phone` (`phone`),
  KEY `idx_sys_user_id` (`sys_user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='微信用户表';

-- =============================================
-- 18. 小程序配置表
-- =============================================
-- 变更说明:
--   - 从 004_wx_tables.sql 合并
--   - WxConfig 模型不使用软删除，无 delete_time 字段
-- =============================================
DROP TABLE IF EXISTS `wx_config`;
CREATE TABLE `wx_config` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `app_id` VARCHAR(50) NOT NULL COMMENT '小程序AppID',
  `app_secret` VARCHAR(100) DEFAULT NULL COMMENT '小程序AppSecret',
  `config_key` VARCHAR(100) NOT NULL COMMENT '配置键',
  `config_value` TEXT DEFAULT NULL COMMENT '配置值',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  `update_time` DATETIME DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_key` (`config_key`),
  KEY `idx_app_id` (`app_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序配置表';

-- =============================================
-- 19. 商务内容表（新增）
-- =============================================
-- 变更说明:
--   - 原迁移文件中缺失，但代码中已有完整模型/控制器/服务/路由
--   - 字段基于 Business 模型推断
-- =============================================
DROP TABLE IF EXISTS `business`;
CREATE TABLE `business` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `title` VARCHAR(200) NOT NULL COMMENT '标题',
  `category` VARCHAR(50) DEFAULT NULL COMMENT '分类',
  `cover` VARCHAR(500) DEFAULT NULL COMMENT '封面图URL',
  `content` LONGTEXT DEFAULT NULL COMMENT '内容',
  `summary` VARCHAR(500) DEFAULT NULL COMMENT '摘要',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `sort` INT DEFAULT 0 COMMENT '排序',
  `created_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '创建者',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  `updated_by` BIGINT UNSIGNED DEFAULT NULL COMMENT '更新者',
  `update_time` DATETIME DEFAULT NULL COMMENT '更新时间',
  `delete_time` DATETIME DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商务内容表';

-- =============================================
-- 20. 用户交互表（新增）
-- =============================================
-- 变更说明:
--   - 原迁移文件中缺失，但代码中已有完整模型/控制器/服务/路由
--   - 字段基于 BusinessInteraction 模型推断
--   - 无 update_time/delete_time（模型中设置 updateTime=false, deleteTime=false）
-- =============================================
DROP TABLE IF EXISTS `business_interaction`;
CREATE TABLE `business_interaction` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `wx_user_id` BIGINT UNSIGNED NOT NULL COMMENT '微信用户ID',
  `business_id` BIGINT UNSIGNED NOT NULL COMMENT '商务内容ID',
  `type` VARCHAR(20) NOT NULL COMMENT '交互类型：favorite/like/collect',
  `create_time` DATETIME DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_business_type` (`wx_user_id`, `business_id`, `type`),
  KEY `idx_business_id` (`business_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户交互表';

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- 执行完成
-- =============================================
