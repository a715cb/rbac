<?php
namespace app\miniapp\controller;

use app\miniapp\service\HomeService;
use think\Request;

class HomeController extends MiniappBaseController
{
    public function index(Request $request)
    {
        try {
            $homeService = new HomeService();
            $data = $homeService->getHomeData();
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
