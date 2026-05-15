<?php
namespace app\model;

use app\common\BaseModel;

class BusinessInteraction extends BaseModel
{
    protected $name = 'business_interaction';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = false;

    protected $deleteTime = false;

    protected $type = [
        'wx_user_id' => 'integer',
        'business_id' => 'integer',
    ];

    public static function toggle(int $wxUserId, int $businessId, string $type): array
    {
        $existing = self::where('wx_user_id', $wxUserId)
            ->where('business_id', $businessId)
            ->where('type', $type)
            ->find();

        if ($existing) {
            $existing->delete();
            return ['action' => 'cancelled', 'type' => $type, 'target_id' => $businessId];
        }

        $new = new self();
        $new->wx_user_id = $wxUserId;
        $new->business_id = $businessId;
        $new->type = $type;
        $new->save();

        return ['action' => 'created', 'type' => $type, 'target_id' => $businessId];
    }
}
