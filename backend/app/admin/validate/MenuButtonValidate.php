<?php
namespace app\admin\validate;

use app\common\BaseValidate;

class MenuButtonValidate extends BaseValidate
{
    protected $rules = [
        'menu_id' => 'require|integer',
        'name' => 'require|max:50',
        'code' => 'require|max:100',
        'icon' => 'max:100',
        'sort' => 'integer',
        'status' => 'in:0,1',
    ];

    protected $message = [
        'menu_id.require' => '菜单ID不能为空',
        'menu_id.integer' => '菜单ID必须为整数',
        'name.require' => '按钮名称不能为空',
        'name.max' => '按钮名称不能超过50个字符',
        'code.require' => '按钮编码不能为空',
        'code.max' => '按钮编码不能超过100个字符',
        'icon.max' => '按钮图标不能超过100个字符',
        'sort.integer' => '排序必须为整数',
        'status.in' => '状态值无效',
    ];

    protected $scene = [
        'store' => ['menu_id', 'name', 'code', 'icon', 'sort', 'status'],
        'update' => ['name', 'code', 'icon', 'sort', 'status'],
    ];
}