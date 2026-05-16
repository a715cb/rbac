<?php
namespace app\admin\validate;

use app\common\BaseValidate;

class ProfileValidate extends BaseValidate
{
    protected $rules = [
        'nickname' => 'max:50',
        'email' => 'email',
        'mobile' => 'mobile',
        'gender' => 'in:0,1,2',
        'old_password' => 'require|min:6',
        'password' => 'require|min:6|max:50|confirm',
    ];

    protected $message = [
        'nickname.max' => '昵称不能超过50个字符',
        'email.email' => '邮箱格式不正确',
        'mobile.mobile' => '手机号格式不正确',
        'gender.in' => '性别值无效',
        'old_password.require' => '原密码不能为空',
        'old_password.min' => '原密码不能少于6个字符',
        'password.require' => '新密码不能为空',
        'password.min' => '新密码不能少于6个字符',
        'password.max' => '新密码不能超过50个字符',
        'password.confirm' => '两次输入的密码不一致',
    ];

    protected $scene = [
        'update' => ['nickname', 'email', 'mobile', 'gender'],
        'change_password' => ['old_password', 'password'],
    ];
}
