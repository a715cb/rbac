<?php
namespace app\admin\validate;

use app\common\BaseValidate;

class ApiValidate extends BaseValidate
{
    protected $rules = [
        'menu_id' => 'integer|egt:0',
        'name' => 'require|max:100',
        'code' => 'require|max:100',
        'method' => 'require|in:GET,POST,PUT,DELETE,PATCH,OPTIONS,HEAD',
        'path' => 'require|max:200',
        'group' => 'max:50',
        'status' => 'in:0,1',
    ];

    protected $message = [
        'menu_id.integer' => '菜单ID必须为整数',
        'menu_id.egt' => '菜单ID必须大于等于0',
        'name.require' => '接口名称不能为空',
        'name.max' => '接口名称不能超过100个字符',
        'code.require' => '接口标识不能为空',
        'code.max' => '接口标识不能超过100个字符',
        'method.require' => '请求方法不能为空',
        'method.in' => '请求方法无效，仅支持 GET/POST/PUT/DELETE/PATCH/OPTIONS/HEAD',
        'path.require' => '接口路径不能为空',
        'path.max' => '接口路径不能超过200个字符',
        'group.max' => '接口分组不能超过50个字符',
        'status.in' => '状态值无效',
    ];

    protected $scene = [
        'store' => ['menu_id', 'name', 'code', 'method', 'path', 'group', 'status'],
        'update' => ['menu_id', 'name', 'code', 'method', 'path', 'group', 'status'],
        'setStatus' => ['status'],
    ];
}