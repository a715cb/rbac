<?php
/**
 * 后台管理服务层索引
 *
 * @包名: app\admin\service
 * @描述: 后台管理模块服务层类索引
 *
 * @服务列表:
 *   1. AdminAuthService - 认证核心服务
 *   2. LoginSecurityService - 登录安全服务
 *   3. UserAgentParserService - User-Agent解析服务
 *   4. UserService - 用户管理服务
 *   5. DepartmentService - 部门管理服务
 *   6. ApiService - 接口管理服务
 *
 * @使用说明:
 *   所有服务均采用单例模式，可通过 getInstance() 方法获取实例
 *
 * @示例:
 *   use app\admin\service\AdminAuthService;
 *
 *   $authService = AdminAuthService::getInstance();
 *   $result = $authService->login($username, $password, $ip, $userAgent);
 */

namespace app\admin\service;

// 引入所有服务类
use app\admin\service\AdminAuthService;
use app\admin\service\LoginSecurityService;
use app\admin\service\UserAgentParserService;
use app\admin\service\UserService;
use app\admin\service\DepartmentService;
use app\admin\service\ApiService;
