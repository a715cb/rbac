<?php
namespace app\admin\controller;

use app\common\BaseController as CommonBaseController;
use think\App;

abstract class BaseController extends CommonBaseController
{
    protected $app;

    protected $appName = 'admin';

    public function __construct(App $app)
    {
        $this->app = $app;
        parent::__construct($app);

        $this->initialize();
    }

    protected function initialize(): void
    {
    }
}