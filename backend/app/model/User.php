<?php
namespace app\model;

use app\common\BaseModel;

class User extends BaseModel
{
    protected $name = 'user';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $deleteTime = 'delete_time';

    protected $defaultSoftDelete = null;

    protected $hidden = ['password'];

    protected $append = [];

    protected $type = [
        'status' => 'integer',
        'gender' => 'integer',
        'dept_id' => 'integer',
    ];

    public function depts()
    {
        return $this->hasMany(UserDept::class, 'user_id', 'id');
    }
}
