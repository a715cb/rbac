<?php
namespace app\model;

use app\common\BaseModel;

class Business extends BaseModel
{
    protected $table = 'business';

    protected $pk = 'id';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $deleteTime = 'delete_time';

    protected $defaultSoftDelete = null;

    protected $type = [
        'status' => 'integer',
        'sort' => 'integer',
    ];

    public static function getListByCondition(string $keyword = '', string $category = '', int $page = 1, int $pageSize = 10): array
    {
        $query = self::where('status', 1);

        if (!empty($keyword)) {
            $query->where('title', 'like', '%' . $keyword . '%');
        }

        if (!empty($category)) {
            $query->where('category', $category);
        }

        $total = $query->count();
        $list = $query->order('sort', 'desc')
            ->order('id', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();

        return [
            'list' => $list,
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
            ],
        ];
    }
}
