<?php
namespace app\admin\validate;

use app\common\BaseValidate;

class DictTypeValidate extends BaseValidate
{
    protected $rules = [
        'name' => 'require|max:100',
        'code' => 'require|regex:/^[a-zA-Z][a-zA-Z0-9_]*$/|max:100',
        'type' => 'in:string,number,date,time',
        'status' => 'in:0,1',
        'sort' => 'integer',
        'remark' => 'max:500',
    ];

    protected $message = [
        'name.require' => '字典名称不能为空',
        'name.max' => '字典名称不能超过100个字符',
        'code.require' => '字典编码不能为空',
        'code.regex' => '字典编码必须以字母开头，只能包含字母、数字和下划线',
        'code.max' => '字典编码不能超过100个字符',
        'type.in' => '字典类型值无效',
        'status.in' => '状态值无效',
        'sort.integer' => '排序必须为整数',
        'remark.max' => '备注不能超过500个字符',
    ];

    protected $scene = [
        'store' => ['name', 'code', 'type', 'status', 'sort', 'remark'],
        'update' => ['name', 'code', 'type', 'status', 'sort', 'remark'],
        'change_status' => ['status'],
    ];
}
