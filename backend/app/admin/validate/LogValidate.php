<?php
namespace app\admin\validate;

use app\common\BaseValidate;

class LogValidate extends BaseValidate
{
    protected $rules = [
        'start_date' => 'dateFormat:Y-m-d',
        'end_date' => 'dateFormat:Y-m-d',
        'before_date' => 'dateFormat:Y-m-d',
    ];

    protected $message = [
        'start_date.dateFormat' => '开始日期格式不正确',
        'end_date.dateFormat' => '结束日期格式不正确',
        'before_date.dateFormat' => '清理日期格式不正确',
    ];

    protected $scene = [
        'stats' => ['start_date', 'end_date'],
        'clean_login' => ['before_date'],
        'clean_operation' => ['before_date'],
    ];
}