<?php
namespace app\admin\validate;

use app\common\BaseValidate;

class DictDataValidate extends BaseValidate
{
    protected $rules = [
        'dict_type_id' => 'require|integer',
        'label' => 'require|max:100',
        'value' => 'require|max:100',
        'status' => 'in:0,1',
        'sort' => 'integer',
        'remark' => 'max:500',
    ];

    protected $message = [
        'dict_type_id.require' => '字典类型ID不能为空',
        'dict_type_id.integer' => '字典类型ID必须为整数',
        'label.require' => '字典标签不能为空',
        'label.max' => '字典标签不能超过100个字符',
        'value.require' => '字典键值不能为空',
        'value.max' => '字典键值不能超过100个字符',
        'status.in' => '状态值无效',
        'sort.integer' => '排序必须为整数',
        'remark.max' => '备注不能超过500个字符',
    ];

    protected $scene = [
        'store' => ['dict_type_id', 'label', 'value', 'status', 'sort', 'remark'],
        'update' => ['label', 'value', 'status', 'sort', 'remark'],
        'change_status' => ['status'],
    ];
}
