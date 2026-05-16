<?php
namespace app\admin\validate;

use app\common\BaseValidate;

class DepartmentValidate extends BaseValidate
{
    protected $rules = [
        'parent_id' => 'require|integer|egt:0',
        'name' => 'require|max:50',
        'code' => 'require|max:50',
        'leader' => 'max:50',
        'phone' => 'mobile',
        'email' => 'email',
        'sort' => 'integer|egt:0',
        'status' => 'in:0,1',
    ];

    protected $message = [
        'parent_id.require' => '父部门ID不能为空',
        'parent_id.integer' => '父部门ID必须为整数',
        'parent_id.egt' => '父部门ID必须大于等于0',
        'name.require' => '部门名称不能为空',
        'name.max' => '部门名称不能超过50个字符',
        'code.require' => '部门编码不能为空',
        'code.max' => '部门编码不能超过50个字符',
        'leader.max' => '负责人不能超过50个字符',
        'phone.mobile' => '联系电话格式不正确',
        'email.email' => '邮箱格式不正确',
        'sort.integer' => '排序必须为整数',
        'sort.egt' => '排序必须大于等于0',
        'status.in' => '状态值无效',
    ];

    protected $scene = [
        'store' => ['parent_id', 'name', 'code', 'leader', 'phone', 'email', 'sort', 'status'],
        'update' => ['parent_id', 'name', 'code', 'leader', 'phone', 'email', 'sort', 'status'],
        'setStatus' => ['status'],
        'setSort' => ['sort'],
    ];

}