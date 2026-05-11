<?php
namespace app\admin\controller;

use app\model\User;
use app\model\Role;
use app\model\Menu;
use app\model\Department;

class DashboardController extends BaseController
{
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
