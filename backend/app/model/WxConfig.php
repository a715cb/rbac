<?php
namespace app\model;

use think\Model;

class WxConfig extends Model
{
    protected $table = 'wx_config';

    protected $pk = 'id';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $type = [
        'status' => 'integer',
    ];

    protected static $cache = [];

    public static function getValue(string $key, $default = null)
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $config = self::where('config_key', $key)->where('status', 1)->find();
        if ($config) {
            self::$cache[$key] = $config->config_value;
            return $config->config_value;
        }

        return $default;
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
