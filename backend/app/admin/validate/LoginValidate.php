<?php
// +----------------------------------------------------------------------
// | 登录验证器
// +----------------------------------------------------------------------
namespace app\admin\validate;

use app\common\BaseValidate;

class LoginValidate extends BaseValidate
{
    protected $rules = [
        'username' => 'require|max:50',
        'password' => 'require|min:6|max:50',
    ];

    protected $message = [
        'username.require' => '用户名不能为空',
        'username.max' => '用户名不能超过50个字符',
        'password.require' => '密码不能为空',
        'password.min' => '密码不能少于6个字符',
        'password.max' => '密码不能超过50个字符',
    ];

    protected $scene = [
        'login' => ['username', 'password'],
    ];
}