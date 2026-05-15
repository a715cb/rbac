<?php
namespace app\model;

use app\common\BaseModel;

class WxUser extends BaseModel
{
    protected $name = 'wx_user';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $deleteTime = 'delete_time';

    protected $defaultSoftDelete = null;

    protected $hidden = ['session_key'];

    protected $type = [
        'status' => 'integer',
        'gender' => 'integer',
        'sys_user_id' => 'integer',
    ];

    public function sysUser()
    {
        return $this->belongsTo(User::class, 'sys_user_id', 'id');
    }

    public static function findByOpenid(string $openid): ?self
    {
        return self::where('openid', $openid)->find();
    }

    public static function findByPhone(string $phone): ?self
    {
        return self::where('phone', $phone)->find();
    }

    public function linkSysUser(?int $sysUserId): bool
    {
        $this->sys_user_id = $sysUserId;
        return $this->save();
    }
}
