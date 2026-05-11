<?php
// +----------------------------------------------------------------------
// | 基础模型类
// +----------------------------------------------------------------------
namespace app\common;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 基础模型
 */
class BaseModel extends Model
{
    use SoftDelete;

    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = [];

    /**
     * 追加属性
     * @var array
     */
    protected $append = [];

    /**
     * 自动写入时间戳
     * @var bool|string
     */
    protected $autoWriteTimestamp = true;

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'create_time';

    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'update_time';

    /**
     * 软删除字段
     * @var string
     */
    protected $deleteTime = 'delete_time';

    /**
     * 软删除默认值
     * @var mixed
     */
    protected $defaultSoftDelete = null;

    /**
     * 时间字段取出后自动转成格式化好的字符串
     * @var bool
     */
    protected $dateFormat = 'Y-m-d H:i:s';
}
