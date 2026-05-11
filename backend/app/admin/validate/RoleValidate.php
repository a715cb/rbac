<?php
namespace app\admin\validate;

use app\common\BaseValidate;

class RoleValidate extends BaseValidate
{
    protected $rules = [
        'name' => 'require|max:50',
        'code' => 'require|max:50|alphaNum',
        'data_scope' => 'in:1,2,3,4,5',
        'data_scope_dept_ids' => 'max:500',
        'sort' => 'integer',
        'status' => 'in:0,1',
        'remark' => 'max:500',
        'menu_ids' => 'array',
        'button_ids' => 'array',
        'api_ids' => 'array',
    ];

    protected $message = [
        'name.require' => '角色名称不能为空',
        'name.max' => '角色名称不能超过50个字符',
        'code.require' => '角色标识不能为空',
        'code.max' => '角色标识不能超过50个字符',
        'code.alphaNum' => '角色标识只能包含字母和数字',
        'data_scope.in' => '数据权限值无效：1全部 2本部门 3本部门及以下 4仅本人 5自定义',
        'data_scope_dept_ids.max' => '自定义数据权限部门ID不能超过500个字符',
        'sort.integer' => '排序必须为整数',
        'status.in' => '状态值无效',
        'remark.max' => '备注不能超过500个字符',
        'menu_ids.array' => '菜单ID必须为数组',
        'button_ids.array' => '按钮ID必须为数组',
        'api_ids.array' => '接口ID必须为数组',
    ];

    protected $scene = [
        'store' => ['name', 'code', 'data_scope', 'data_scope_dept_ids', 'sort', 'status', 'remark', 'menu_ids', 'button_ids', 'api_ids'],
        'update' => ['name', 'data_scope', 'data_scope_dept_ids', 'sort', 'status', 'remark'],
        'assign_menus' => ['menu_ids'],
        'assign_buttons' => ['button_ids'],
        'assign_apis' => ['api_ids'],
        'data_scope' => ['data_scope', 'data_scope_dept_ids'],
        'change_status' => ['status'],
    ];
}