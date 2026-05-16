<?php
namespace app\admin\validate;

use app\common\BaseValidate;

class UserValidate extends BaseValidate
{
    protected $rules = [
        'username' => 'require|max:50|alphaNum',
        'password' => 'require|min:6|max:50',
        'nickname' => 'max:50',
        'email' => 'email',
        'mobile' => 'mobile',
        'status' => 'in:0,1',
        'gender' => 'in:0,1,2',
        'dept_id' => 'integer',
        'role_ids' => 'array',
        'old_password' => 'require|min:6',
        'confirm_password' => 'require|min:6',
    ];

    protected $message = [
        'username.require' => '用户名不能为空',
        'username.max' => '用户名不能超过50个字符',
        'username.alphaNum' => '用户名只能包含字母和数字',
        'password.require' => '密码不能为空',
        'password.min' => '密码不能少于6个字符',
        'password.max' => '密码不能超过50个字符',
        'nickname.max' => '昵称不能超过50个字符',
        'email.email' => '邮箱格式不正确',
        'mobile.mobile' => '手机号格式不正确',
        'status.in' => '状态值无效',
        'gender.in' => '性别值无效',
        'dept_id.integer' => '部门ID必须为整数',
        'role_ids.array' => '角色ID必须为数组',
        'old_password.require' => '原密码不能为空',
        'old_password.min' => '原密码不能少于6个字符',
        'confirm_password.require' => '确认密码不能为空',
        'confirm_password.min' => '确认密码不能少于6个字符',
    ];

    protected $scene = [
        'store' => ['username', 'password', 'nickname', 'email', 'mobile', 'status', 'gender', 'dept_id', 'role_ids'],
        'update' => ['nickname', 'email', 'mobile', 'status', 'gender', 'dept_id', 'role_ids'],
        'assign_roles' => ['role_ids'],
        'reset_password' => ['password'],
        'change_status' => ['status'],
    ];

}