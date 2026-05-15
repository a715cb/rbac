<?php
/**
 * @文件: BaseModel.php
 * @用途: 所有业务模型的公共基类
 * @描述: 封装 ThinkPHP Model 的通用配置，统一时间戳字段命名、软删除策略和数据输出格式，
 *        业务模型继承此类即可自动获得自动时间戳、软删除和字段隐藏等能力
 * @核心逻辑:
 *   1. 启用自动时间戳，字段名为 create_time / update_time
 *   2. 启用软删除，字段名为 delete_time，未删除时值为 null
 *   3. 时间字段自动格式化为 Y-m-d H:i:s
 */
namespace app\common;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 基础模型类
 * 所有业务模型应继承此类，以获得统一的时间戳管理和软删除支持
 * @see https://www.kancloud.cn/manual/thinkphp8/1688456
 */
class BaseModel extends Model
{
    use SoftDelete;

    /**
     * 隐藏字段
     * @var array API 输出时自动排除的字段名列表，子类按需覆盖
     */
    protected $hidden = [];

    /**
     * 追加属性
     * @var array API 输出时自动追加的虚拟字段名列表，子类按需覆盖
     */
    protected $append = [];

    /**
     * 自动写入时间戳
     * @var bool|string true 表示自动识别，'timestamp' 表示整型时间戳
     */
    protected $autoWriteTimestamp = true;

    /**
     * 创建时间字段名
     * @var string 对应数据库 create_time 列
     */
    protected $createTime = 'create_time';

    /**
     * 更新时间字段名
     * @var string 对应数据库 update_time 列
     */
    protected $updateTime = 'update_time';

    /**
     * 软删除字段名
     * @var string 对应数据库 delete_time 列，null 表示未删除
     */
    protected $deleteTime = 'delete_time';

    /**
     * 软删除默认值
     * @var mixed 软删除字段在未删除记录中的值，null 表示字段为 NULL
     */
    protected $defaultSoftDelete = null;

    /**
     * 时间字段格式化
     * @var string|bool 时间字段取出后的显示格式，false 表示返回原始值
     */
    protected $dateFormat = 'Y-m-d H:i:s';
}
