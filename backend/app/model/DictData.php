<?php
namespace app\model;

use app\common\BaseModel;
use think\facade\Db;

class DictData extends BaseModel
{
    protected $table = 'sys_dict_data';

    protected $pk = 'id';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $deleteTime = 'delete_time';

    protected $defaultSoftDelete = null;

    protected $hidden = ['delete_time'];

    protected $append = [];

    protected $type = [
        'dict_type_id' => 'integer',
        'status' => 'integer',
        'sort' => 'integer',
    ];
}
