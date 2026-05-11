<?php
// +----------------------------------------------------------------------
// | 基础验证器
// +----------------------------------------------------------------------
namespace app\common;

use think\Validate;

/**
 * 基础验证器
 */
class BaseValidate extends Validate
{
    /**
     * 验证失败错误信息
     * @var array
     */
    protected $message = [];

    /**
     * 场景
     * @var array
     */
    protected $scene = [];

    /**
     * 验证规则
     * @var array
     */
    protected $rules = [];

    protected $failException = true;

    protected function rules()
    {
        return $this->rules;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function getSceneRules(): array
    {
        $scene = $this->currentScene;
        if ($scene && isset($this->scene[$scene])) {
            // 如果设置了场景
            $scene = $this->scene[$scene];
            // 获取场景验证规则
            $rules = [];
            foreach ($scene as $key => $field) {
                if (is_string($key)) {
                    $rules[$key] = $field;
                } else {
                    $rules[$field] = $this->rules[$field] ?? '';
                }
            }
            return $rules;
        }
        return $this->rules;
    }
}
