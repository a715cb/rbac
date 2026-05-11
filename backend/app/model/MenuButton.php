<?php
// +----------------------------------------------------------------------
// | 菜单按钮模型
// +----------------------------------------------------------------------
namespace app\model;

use app\common\BaseModel;

class MenuButton extends BaseModel
{
    protected $table = 'sys_menu_button';

    protected $pk = 'id';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $deleteTime = 'delete_time';

    protected $defaultSoftDelete = null;

    protected $hidden = [];

    protected $append = [];

    protected $type = [
        'menu_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    public function getButtonsByMenu(int $menuId): array
    {
        return $this->where('menu_id', $menuId)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }

    public function getButtonByCode(int $menuId, string $code): ?array
    {
        $button = $this->where('menu_id', $menuId)
            ->where('code', $code)
            ->where('status', 1)
            ->find();

        return $button ? $button->toArray() : null;
    }
}