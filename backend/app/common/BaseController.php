<?php
/**
 * 基础控制器抽象类
 *
 * @命名空间 app\common
 * @类名 BaseController
 * @描述 为所有控制器提供统一的HTTP响应格式
 *
 * @设计目的
 *   - 统一项目所有控制器的HTTP响应格式
 *   - 提供标准化的成功/错误响应方法
 *   - 简化控制器开发，减少重复代码
 *
 * @使用约束
 *   - 控制器应通过 success/error 方法返回统一格式的JSON响应
 *   - 构造函数必须接收 App 实例并调用 parent::__construct()
 *
 * @响应格式规范
 *   成功响应：{"code": 200, "msg": "操作成功", "data": {...}}
 *   错误响应：{"code": 400, "msg": "错误信息", "data": {...}}
 *
 * @继承关系
 *   CommonBaseController (common)
 *       ├── admin/BaseController (保留用于模块特定配置)
 *       │   ├── AuthController
 *       │   ├── UserController
 *       │   └── ... (12个业务控制器)
 *       │
 *       └── miniapp/MiniappBaseController
 *           └── ... (4个小程序控制器)
 *
 * @依赖框架
 *   - thinkphp/framework: ^8.0
 *   - think\Request: 请求对象
 *
 * @版本 v2.0
 * @作者 RBAC Development Team
 * @日期 2024
 */

namespace app\common;

use think\App;

/**
 * 基础控制器抽象类
 *
 * 提供控制器通用的响应格式和请求处理功能
 */
class BaseController
{
    /** @var \think\Request 请求对象实例，提供对当前HTTP请求的完整访问 */
    protected \think\Request $request;

    /** @var \think\App 应用实例，提供对框架核心功能的访问 */
    protected \think\App $app;

    /**
     * 构造函数
     *
     * @param \think\App $app 框架应用实例，由容器自动注入
     *
     * @功能描述
     *   - 初始化请求对象和应用实例
     *   - 调用 initialize() 钩子方法供子类扩展
     *   - 确保所有控制器都能访问框架核心功能
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        $this->initialize();
    }

    /**
     * 初始化钩子方法
     *
     * @描述 供子类重写的初始化方法，在构造函数中自动调用
     *
     * @使用场景
     *   - 子类可重写此方法添加自定义初始化逻辑
     *   - 适合放置一些通用的预处理操作
     */
    protected function initialize()
    {
    }

    /**
     * 返回成功响应
     *
     * @param mixed $data 响应数据，可为数组、对象或标量值
     * @param string $msg 成功消息，默认为"操作成功"
     * @param int $code HTTP状态码，默认为200
     *
     * @return \think\response\Json JSON格式的HTTP响应
     *
     * @功能描述
     *   - 统一成功响应的格式和数据结构
     *   - 响应码200表示请求成功处理
     *   - data字段包含业务数据或空数组
     *
     * @使用示例
     *   // 返回简单成功响应
     *   return $this->success();
     *
     *   // 返回带数据的成功响应
     *   return $this->success(['id' => 1, 'name' => 'admin'], '创建成功');
     *
     * @响应格式
     *   {
     *     "code": 200,
     *     "msg": "操作成功",
     *     "data": {...}
     *   }
     */
    protected function success(mixed $data = [], string $msg = '操作成功', int $code = 200): \think\response\Json
    {
        return $this->response($code, $msg, $data);
    }

    /**
     * 返回错误响应
     *
     * @param string $msg 错误消息，描述错误原因
     * @param int $code HTTP状态码，默认为400（Bad Request）
     * @param mixed $data 附加数据，可选
     *
     * @return \think\response\Json JSON格式的HTTP响应
     *
     * @功能描述
     *   - 统一错误响应的格式和数据结构
     *   - code字段使用HTTP标准状态码
     *   - msg字段提供友好的错误提示信息
     *
     * @常用错误码
     *   - 400: 请求参数错误
     *   - 401: 未认证或认证失效
     *   - 403: 无权限访问
     *   - 404: 资源不存在
     *   - 422: 数据验证失败
     *   - 500: 服务器内部错误
     *
     * @使用示例
     *   // 返回通用错误响应
     *   return $this->error('操作失败');
     *
     *   // 返回带状态码的错误响应
     *   return $this->error('用户不存在', 404);
     *
     * @响应格式
     *   {
     *     "code": 400,
     *     "msg": "操作失败",
     *     "data": {...}
     *   }
     */
    protected function error(string $msg = '操作失败', int $code = 400, mixed $data = []): \think\response\Json
    {
        return $this->response($code, $msg, $data);
    }

    /**
     * 构建JSON响应
     *
     * @param int $code HTTP状态码
     * @param string $msg 响应消息
     * @param mixed $data 响应数据
     *
     * @return \think\response\Json JSON格式的HTTP响应
     *
     * @功能描述
     *   - 内部方法，统一构建响应数据结构
     *   - 被 success() 和 error() 方法调用
     *   - 提供标准化的响应结构
     *
     * @响应结构
     *   {
     *     "code": int,
     *     "msg": string,
     *     "data": mixed
     *   }
     */
    protected function response(int $code, string $msg, mixed $data = []): \think\response\Json
    {
        return json([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ]);
    }
}
