<?php
namespace app\model;

use app\common\BaseModel;
use think\facade\Db;

class DictType extends BaseModel
{
    protected $name = 'dict_type';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $deleteTime = 'delete_time';

    protected $defaultSoftDelete = null;

    protected $hidden = ['delete_time'];

    protected $append = [];

    protected $type = [
        'status' => 'integer',
        'sort' => 'integer',
    ];
}
