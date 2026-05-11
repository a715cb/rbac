<?php
// +----------------------------------------------------------------------
// | 修改密码验证器
// +----------------------------------------------------------------------
namespace app\admin\validate;

use app\common\BaseValidate;

class ChangePasswordValidate extends BaseValidate
{
    protected $rules = [
        'old_password' => 'require|min:6',
        'password' => 'require|min:6|max:50|confirm',
    ];

    protected $message = [
        'old_password.require' => '原密码不能为空',
        'old_password.min' => '原密码不能少于6个字符',
        'password.require' => '新密码不能为空',
        'password.min' => '新密码不能少于6个字符',
        'password.max' => '新密码不能超过50个字符',
        'password.confirm' => '两次输入的密码不一致',
    ];

    protected $scene = [
        'change' => ['old_password', 'password'],
    ];
}