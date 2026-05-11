<?php
namespace app\miniapp\controller;

use app\miniapp\service\BusinessService;
use think\Request;

class BusinessController extends MiniappBaseController
{
    public function list(Request $request)
    {
        try {
            $params = $request->get();
            $service = new BusinessService();
            $data = $service->getList($params);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function detail(Request $request, int $id)
    {
        try {
            $service = new BusinessService();
            $data = $service->getDetail($id);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function operate(Request $request)
    {
        try {
            $data = $request->post();
            $wxUser = $request->wxUser ?? [];
            $service = new BusinessService();
            $result = $service->operate($data, $wxUser);
            return $this->success($result, '操作成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
