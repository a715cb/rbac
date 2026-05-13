-- =============================================
-- RBAC 权限系统 - 迁移权限模块菜单路径和权限代码
-- =============================================
-- 执行时间: 2026-05-13
-- 说明: 将 system/button, system/api, system/menu, system/role 相关路径和权限代码
--       更新为 permission/button, permission/api, permission/menu, permission/role
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE `rbac_system`;

-- =============================================
-- 1. 更新按钮管理菜单的 component 路径
-- =============================================
UPDATE `sys_menu`
SET `component` = 'permission/button/index'
WHERE `code` = 'system_button';

-- =============================================
-- 2. 更新接口管理菜单的 component 路径
-- =============================================
UPDATE `sys_menu`
SET `component` = 'permission/api/index'
WHERE `code` = 'system_api';

-- =============================================
-- 3. 更新菜单管理菜单的 component 路径
-- =============================================
UPDATE `sys_menu`
SET `component` = 'permission/menu/index'
WHERE `code` = 'system_menu';

-- =============================================
-- 4. 更新角色管理菜单的 component 路径
-- =============================================
UPDATE `sys_menu`
SET `component` = 'permission/role/index'
WHERE `code` = 'system_role';

-- =============================================
-- 5. 更新按钮权限代码 (system_button -> permission_button)
-- =============================================
UPDATE `sys_menu_button`
SET `code` = REPLACE(`code`, 'system_button', 'permission_button')
WHERE `code` LIKE '%system_button%';

-- =============================================
-- 6. 更新菜单权限代码 (system_menu -> permission_menu)
-- =============================================
UPDATE `sys_menu_button`
SET `code` = REPLACE(`code`, 'system_menu', 'permission_menu')
WHERE `code` LIKE '%system_menu%';

-- =============================================
-- 7. 更新角色权限代码 (system_role -> permission_role)
-- =============================================
UPDATE `sys_menu_button`
SET `code` = REPLACE(`code`, 'system_role', 'permission_role')
WHERE `code` LIKE '%system_role%';

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- 执行完成
-- =============================================
-- 验证查询:
-- SELECT id, name, code, path, component FROM sys_menu
-- WHERE code IN ('system_button', 'system_api', 'system_menu', 'system_role');
--
-- SELECT id, menu_id, name, code FROM sys_menu_button
-- WHERE code LIKE '%button%' OR code LIKE '%menu%' OR code LIKE '%role%';
-- =============================================