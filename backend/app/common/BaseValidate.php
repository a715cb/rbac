<?php
/**
 * @文件: BaseValidate.php
 * @用途: 所有业务验证器的公共基类
 * @描述: 封装 ThinkPHP Validate 的通用能力，支持场景化验证和规则动态获取，
 *        业务验证器继承此类即可通过定义 $rules 和 $scene 属性快速构建验证逻辑
 * @核心逻辑:
 *   1. 验证失败时自动抛出 ValidateException（$failException = true）
 *   2. 支持通过 $scene 属性定义验证场景，按场景选取不同字段规则
 *   3. 提供 getRules / getSceneRules 方法供控制器动态获取当前验证规则
 */
namespace app\common;

use think\Validate;

/**
 * 基础验证器类
 * 所有业务验证器应继承此类，通过 $rules 和 $scene 属性声明验证规则和场景
 * @see https://www.kancloud.cn/manual/thinkphp8/1688429
 */
class BaseValidate extends Validate
{
    /**
     * 验证失败错误信息
     * @var array 自定义字段验证失败提示，键为"字段名.规则名"，值为提示文本
     */
    protected $message = [];

    /**
     * 验证场景定义
     * @var array 键为场景名，值为该场景需要验证的字段名或"字段名=>规则"映射
     *            示例：['create' => ['name', 'age'], 'update' => ['name']]
     */
    protected $scene = [];

    /**
     * 验证规则
     * @var array 键为字段名，值为验证规则字符串（如 'require|max:50'）
     */
    protected $rules = [];

    /**
     * 验证失败时是否抛出异常
     * @var bool true 表示验证失败自动抛出 ValidateException，由 AppException 统一处理
     */
    protected $failException = true;

    /**
     * 获取验证规则（供框架内部调用）
     * @return array 当前验证规则数组
     * @description 覆盖父类 rules() 方法，返回类属性 $rules 而非方法内硬编码
     */
    protected function rules()
    {
        return $this->rules;
    }

    /**
     * 获取全部验证规则
     * @return array 完整的验证规则数组
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * 获取当前场景的验证规则
     * @return array 当前场景对应的验证规则子集；未设置场景时返回全部规则
     * @description 根据当前激活的场景（$currentScene）从 $rules 中筛选对应字段规则：
     *              - 场景值为索引数组时（如 ['name', 'age']），从 $rules 中提取对应字段规则
     *              - 场景值为关联数组时（如 ['name' => 'require']），直接使用场景中定义的规则
     */
    public function getSceneRules(): array
    {
        $scene = $this->currentScene;
        if ($scene && isset($this->scene[$scene])) {
            $scene = $this->scene[$scene];
            $rules = [];
            foreach ($scene as $key => $field) {
                if (is_string($key)) {
                    // 关联数组：键为字段名，值为该场景下专用的规则
                    $rules[$key] = $field;
                } else {
                    // 索引数组：字段名从 $rules 中取对应规则，无则默认空字符串
                    $rules[$field] = $this->rules[$field] ?? '';
                }
            }
            return $rules;
        }
        return $this->rules;
    }
}
