-- ============================================
-- RBAC 系统数据库表结构
-- 自动生成时间: 2026-05-16 03:05:27
-- 数据库: rbac_system
-- 表前缀: sys_
-- ============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------
-- 表结构: sys_api
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_api`;
CREATE TABLE `sys_api` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '接口ID',
  `menu_id` bigint(20) unsigned DEFAULT NULL COMMENT '所属菜单',
  `name` varchar(100) NOT NULL COMMENT '接口名称',
  `code` varchar(100) NOT NULL COMMENT '接口标识',
  `method` varchar(10) NOT NULL COMMENT '请求方法：GET/POST/PUT/DELETE',
  `path` varchar(200) NOT NULL COMMENT '接口路径',
  `group` varchar(50) DEFAULT NULL COMMENT '接口分组',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT '创建者',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_by` bigint(20) unsigned DEFAULT NULL COMMENT '更新者',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  UNIQUE KEY `uk_method_path` (`method`,`path`),
  KEY `idx_menu_id` (`menu_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8020 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='接口表';

-- --------------------------------------------
-- 表结构: sys_business
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_business`;
CREATE TABLE `sys_business` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `title` varchar(200) NOT NULL COMMENT '标题',
  `category` varchar(50) DEFAULT NULL COMMENT '分类',
  `cover` varchar(500) DEFAULT NULL COMMENT '封面图URL',
  `content` longtext DEFAULT NULL COMMENT '内容',
  `summary` varchar(500) DEFAULT NULL COMMENT '摘要',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT '创建者',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_by` bigint(20) unsigned DEFAULT NULL COMMENT '更新者',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商务内容表';

-- --------------------------------------------
-- 表结构: sys_business_interaction
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_business_interaction`;
CREATE TABLE `sys_business_interaction` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `wx_user_id` bigint(20) unsigned NOT NULL COMMENT '微信用户ID',
  `business_id` bigint(20) unsigned NOT NULL COMMENT '商务内容ID',
  `type` varchar(20) NOT NULL COMMENT '交互类型：favorite/like/collect',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_business_type` (`wx_user_id`,`business_id`,`type`),
  KEY `idx_business_id` (`business_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户交互表';

-- --------------------------------------------
-- 表结构: sys_config
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_config`;
CREATE TABLE `sys_config` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `name` varchar(100) NOT NULL COMMENT '配置名称',
  `code` varchar(100) NOT NULL COMMENT '配置编码',
  `value` text DEFAULT NULL COMMENT '配置值',
  `type` varchar(50) NOT NULL DEFAULT 'string' COMMENT '类型：string/number/json/xml',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT '创建者',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_by` bigint(20) unsigned DEFAULT NULL COMMENT '更新者',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- --------------------------------------------
-- 表结构: sys_department
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_department`;
CREATE TABLE `sys_department` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '部门ID',
  `parent_id` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT '父部门ID',
  `name` varchar(50) NOT NULL COMMENT '部门名称',
  `code` varchar(50) NOT NULL COMMENT '部门编码',
  `leader` varchar(50) DEFAULT NULL COMMENT '负责人',
  `phone` varchar(20) DEFAULT NULL COMMENT '联系电话',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT '创建者',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_by` bigint(20) unsigned DEFAULT NULL COMMENT '更新者',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='部门表';

-- --------------------------------------------
-- 表结构: sys_dict_data
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_dict_data`;
CREATE TABLE `sys_dict_data` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '字典数据ID',
  `dict_type_id` bigint(20) unsigned NOT NULL COMMENT '字典类型ID',
  `label` varchar(100) NOT NULL COMMENT '字典标签',
  `value` varchar(100) NOT NULL COMMENT '字典键值',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT '创建者',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_by` bigint(20) unsigned DEFAULT NULL COMMENT '更新者',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_value_delete_time` (`dict_type_id`,`value`,`delete_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典数据表';

-- --------------------------------------------
-- 表结构: sys_dict_type
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_dict_type`;
CREATE TABLE `sys_dict_type` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '字典ID',
  `name` varchar(100) NOT NULL COMMENT '字典名称',
  `code` varchar(100) NOT NULL COMMENT '字典编码',
  `type` varchar(50) NOT NULL DEFAULT 'string' COMMENT '类型：string/number/date/time',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `sort` int(11) DEFAULT 0 COMMENT 'æŽ’åº',
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT '创建者',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_by` bigint(20) unsigned DEFAULT NULL COMMENT '更新者',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code_delete_time` (`code`,`delete_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='字典类型表';

-- --------------------------------------------
-- 表结构: sys_login_log
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_login_log`;
CREATE TABLE `sys_login_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `ip` varchar(50) DEFAULT NULL COMMENT '登录IP',
  `address` varchar(255) DEFAULT NULL COMMENT '登录地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT 'User-Agent',
  `os` varchar(100) DEFAULT NULL COMMENT '操作系统',
  `browser` varchar(100) DEFAULT NULL COMMENT '浏览器',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '登录状态：0失败 1成功',
  `msg` varchar(255) DEFAULT NULL COMMENT '提示消息',
  `login_time` datetime NOT NULL COMMENT '登录时间',
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_login_time` (`login_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=790 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='登录日志表';

-- --------------------------------------------
-- 表结构: sys_menu
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_menu`;
CREATE TABLE `sys_menu` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '菜单ID',
  `parent_id` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT '父菜单ID',
  `name` varchar(50) NOT NULL COMMENT '菜单名称',
  `code` varchar(100) NOT NULL COMMENT '菜单标识',
  `path` varchar(200) DEFAULT NULL COMMENT '路由路径',
  `icon` varchar(100) DEFAULT NULL COMMENT '图标',
  `component` varchar(255) DEFAULT NULL COMMENT '组件路径',
  `menu_type` tinyint(4) NOT NULL COMMENT '菜单类型：1目录 2菜单 3按钮',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `visible` tinyint(4) DEFAULT 1 COMMENT '显示状态：0隐藏 1显示',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `keep_alive` tinyint(4) DEFAULT 1 COMMENT '是否缓存：0否 1是',
  `always_show` tinyint(4) DEFAULT 1 COMMENT '是否总是显示：0否 1是',
  `breadcrumb` tinyint(4) DEFAULT 1 COMMENT '是否显示面包屑：0否 1是',
  `active_menu` varchar(255) DEFAULT NULL COMMENT '高亮菜单',
  `is_external` tinyint(4) DEFAULT 0 COMMENT '是否外链：0否 1是',
  `is_frame` tinyint(4) DEFAULT 1 COMMENT '是否iframe：0否 1是',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT '创建者',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_by` bigint(20) unsigned DEFAULT NULL COMMENT '更新者',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_menu_type` (`menu_type`)
) ENGINE=InnoDB AUTO_INCREMENT=305 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='菜单表';

-- --------------------------------------------
-- 表结构: sys_menu_button
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_menu_button`;
CREATE TABLE `sys_menu_button` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '按钮ID',
  `menu_id` bigint(20) unsigned NOT NULL COMMENT '菜单ID',
  `name` varchar(50) NOT NULL COMMENT '按钮名称',
  `code` varchar(100) NOT NULL COMMENT '按钮编码',
  `icon` varchar(100) DEFAULT NULL COMMENT '按钮图标',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT '创建者',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_by` bigint(20) unsigned DEFAULT NULL COMMENT '更新者',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_menu_button` (`menu_id`,`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3019 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='菜单按钮表';

-- --------------------------------------------
-- 表结构: sys_operation_log
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_operation_log`;
CREATE TABLE `sys_operation_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `user_id` bigint(20) unsigned DEFAULT NULL COMMENT '用户ID',
  `username` varchar(50) DEFAULT NULL COMMENT '用户名',
  `module` varchar(100) DEFAULT NULL COMMENT '操作模块',
  `action` varchar(100) DEFAULT NULL COMMENT '操作功能',
  `method` varchar(10) DEFAULT NULL COMMENT '请求方法',
  `url` varchar(500) DEFAULT NULL COMMENT '请求地址',
  `ip` varchar(50) DEFAULT NULL COMMENT 'IP地址',
  `address` varchar(255) DEFAULT NULL COMMENT '操作地址',
  `param` text DEFAULT NULL COMMENT '请求参数',
  `result` text DEFAULT NULL COMMENT '返回结果',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '操作状态：0异常 1正常',
  `error_msg` text DEFAULT NULL COMMENT '错误信息',
  `duration` int(11) DEFAULT NULL COMMENT '耗时（毫秒）',
  `create_time` datetime DEFAULT NULL COMMENT '操作时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_username` (`username`),
  KEY `idx_action` (`action`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=391 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='操作日志表';

-- --------------------------------------------
-- 表结构: sys_role
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_role`;
CREATE TABLE `sys_role` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `name` varchar(50) NOT NULL COMMENT '角色名称',
  `code` varchar(50) NOT NULL COMMENT '角色编码',
  `data_scope` tinyint(4) NOT NULL DEFAULT 1 COMMENT '数据权限：1全部 2本部门 3本部门及以下 4仅本人 5自定义',
  `data_scope_dept_ids` varchar(500) DEFAULT NULL COMMENT '自定义数据权限部门ID列表',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT '创建者',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_by` bigint(20) unsigned DEFAULT NULL COMMENT '更新者',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色表';

-- --------------------------------------------
-- 表结构: sys_role_api
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_role_api`;
CREATE TABLE `sys_role_api` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `role_id` bigint(20) unsigned NOT NULL COMMENT '角色ID',
  `api_id` bigint(20) unsigned NOT NULL COMMENT '接口ID',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_api` (`role_id`,`api_id`),
  KEY `idx_api_id` (`api_id`)
) ENGINE=InnoDB AUTO_INCREMENT=632 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色接口关联表';

-- --------------------------------------------
-- 表结构: sys_role_menu
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_role_menu`;
CREATE TABLE `sys_role_menu` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `role_id` bigint(20) unsigned NOT NULL COMMENT '角色ID',
  `menu_id` bigint(20) unsigned NOT NULL COMMENT '菜单ID',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_menu` (`role_id`,`menu_id`),
  KEY `idx_menu_id` (`menu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=157 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色菜单关联表';

-- --------------------------------------------
-- 表结构: sys_role_menu_button
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_role_menu_button`;
CREATE TABLE `sys_role_menu_button` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `role_id` bigint(20) unsigned NOT NULL COMMENT '角色ID',
  `menu_button_id` bigint(20) unsigned NOT NULL COMMENT '菜单按钮ID',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_button` (`role_id`,`menu_button_id`),
  KEY `idx_button_id` (`menu_button_id`)
) ENGINE=InnoDB AUTO_INCREMENT=236 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色菜单按钮关联表';

-- --------------------------------------------
-- 表结构: sys_user
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_user`;
CREATE TABLE `sys_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码（加密）',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
  `mobile` varchar(20) DEFAULT NULL COMMENT '手机号',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `gender` tinyint(4) DEFAULT 0 COMMENT '性别：0未知 1男 2女',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `dept_id` bigint(20) unsigned DEFAULT NULL COMMENT '部门ID',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录IP',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT '创建者',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_by` bigint(20) unsigned DEFAULT NULL COMMENT '更新者',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_dept_id` (`dept_id`),
  KEY `idx_status` (`status`),
  KEY `idx_mobile` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- --------------------------------------------
-- 表结构: sys_user_dept
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_user_dept`;
CREATE TABLE `sys_user_dept` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` bigint(20) unsigned NOT NULL COMMENT '用户ID',
  `dept_id` bigint(20) unsigned NOT NULL COMMENT '部门ID',
  `is_primary` tinyint(4) NOT NULL DEFAULT 0 COMMENT '是否主部门：0否 1是',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_dept` (`user_id`,`dept_id`),
  KEY `idx_dept_id` (`dept_id`),
  KEY `idx_is_primary` (`user_id`,`is_primary`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户部门关联表';

-- --------------------------------------------
-- 表结构: sys_user_role
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_user_role`;
CREATE TABLE `sys_user_role` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id` bigint(20) unsigned NOT NULL COMMENT '用户ID',
  `role_id` bigint(20) unsigned NOT NULL COMMENT '角色ID',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role` (`user_id`,`role_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户角色关联表';

-- --------------------------------------------
-- 表结构: sys_wx_config
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_wx_config`;
CREATE TABLE `sys_wx_config` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `app_id` varchar(50) NOT NULL COMMENT '小程序AppID',
  `app_secret` varchar(100) DEFAULT NULL COMMENT '小程序AppSecret',
  `config_key` varchar(100) NOT NULL COMMENT '配置键',
  `config_value` text DEFAULT NULL COMMENT '配置值',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_key` (`config_key`),
  KEY `idx_app_id` (`app_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='小程序配置表';

-- --------------------------------------------
-- 表结构: sys_wx_user
-- --------------------------------------------
DROP TABLE IF EXISTS `sys_wx_user`;
CREATE TABLE `sys_wx_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `openid` varchar(64) NOT NULL COMMENT '微信openid',
  `unionid` varchar(64) DEFAULT NULL COMMENT '微信unionid',
  `session_key` varchar(128) DEFAULT NULL COMMENT '会话密钥',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(500) DEFAULT NULL COMMENT '头像URL',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `gender` tinyint(4) DEFAULT 0 COMMENT '性别：0未知 1男 2女',
  `sys_user_id` bigint(20) unsigned DEFAULT NULL COMMENT '关联系统用户ID',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态：0禁用 1正常',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_openid` (`openid`),
  KEY `idx_unionid` (`unionid`),
  KEY `idx_phone` (`phone`),
  KEY `idx_sys_user_id` (`sys_user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='微信用户表';

SET FOREIGN_KEY_CHECKS = 1;
