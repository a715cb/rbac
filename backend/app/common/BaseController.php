<?php
namespace app\common;

use think\App;
use think\Validate;

class BaseController
{
    protected $request;

    protected $app;

    protected $batchValidate = false;

    protected $middleware = [];

    protected $userInfo = [];

    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        $this->initialize();
    }

    protected function initialize()
    {
    }

    protected function validate(array $data, string|array $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    protected function success(mixed $data = [], string $msg = '操作成功', int $code = 200): \think\response\Json
    {
        return $this->response($code, $msg, $data);
    }

    protected function error(string $msg = '操作失败', int $code = 400, mixed $data = []): \think\response\Json
    {
        return $this->response($code, $msg, $data);
    }

    protected function response(int $code, string $msg, mixed $data = []): \think\response\Json
    {
        return json([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ]);
    }
}
