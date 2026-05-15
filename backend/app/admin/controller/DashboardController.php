<?php
namespace app\admin\controller;

use app\common\BaseController;
use app\model\User;
use app\model\Role;
use app\model\Menu;
use app\model\Department;

/**
 * 仪表盘控制器
 *
 * 负责为管理后台首页仪表盘提供聚合统计数据，包括用户、角色、菜单、部门等
 * 核心实体的总量信息，供前端渲染统计卡片使用。
 *
 * 设计思路：
 * - 所有统计均排除软删除记录（delete_time IS NULL），确保数据与业务可见范围一致
 * - 当前仅提供总量统计，后续可按需扩展趋势、分布等维度
 */
class DashboardController extends BaseController
{
    /**
     * 获取仪表盘统计数据
     *
     * 统计用户、角色、菜单、部门四个核心实体的未删除记录总数，
     * 以键值对形式返回，供前端仪表盘卡片展示。
     *
     * @return \think\response\Json 统一响应结构，data 包含：
     *   - user_total  int 用户总数
     *   - role_total  int 角色总数
     *   - menu_total  int 菜单总数
     *   - dept_total  int 部门总数
     */
    public function statistics()
    {
        $userTotal = User::whereNull('delete_time')->count();
        $roleTotal = Role::whereNull('delete_time')->count();
        $menuTotal = Menu::whereNull('delete_time')->count();
        $deptTotal = Department::whereNull('delete_time')->count();

        return $this->success([
            'user_total' => $userTotal,
            'role_total' => $roleTotal,
            'menu_total' => $menuTotal,
            'dept_total' => $deptTotal,
        ], '获取成功');
    }
}
