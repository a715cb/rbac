<?php
namespace app\miniapp\validate;

use app\common\BaseValidate;

class LoginValidate extends BaseValidate
{
    protected $rules = [
        'code' => 'require',
        'iv' => 'require',
        'encrypted_data' => 'require',
        'nickname' => 'max:50',
        'avatar' => 'max:500',
    ];

    protected $message = [
        'code.require' => '登录凭证不能为空',
        'iv.require' => '加密向量不能为空',
        'encrypted_data.require' => '加密数据不能为空',
        'nickname.max' => '昵称不能超过50个字符',
        'avatar.max' => '头像地址不能超过500个字符',
    ];

    protected $scene = [
        'login' => ['code'],
        'phone' => ['iv', 'encrypted_data'],
        'profile' => ['nickname', 'avatar'],
    ];
}
