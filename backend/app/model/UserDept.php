<?php
namespace app\model;

use app\common\BaseModel;

class UserDept extends BaseModel
{
    protected $name = 'user_dept';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = false;

    protected $deleteTime = false;

    protected $defaultSoftDelete = null;

    protected $hidden = [];

    protected $append = [];

    protected $type = [
        'user_id' => 'integer',
        'dept_id' => 'integer',
        'is_primary' => 'integer',
        'sort' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'dept_id', 'id');
    }
}
