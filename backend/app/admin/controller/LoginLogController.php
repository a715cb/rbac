<?php
namespace app\admin\controller;

use app\common\BaseController;
use app\model\LoginLog as LoginLogModel;
use app\admin\validate\LogValidate;
use app\common\AdminAuth;
use think\Request;
use think\facade\Db;
use think\facade\Event;
use think\facade\Log;

class LoginLogController extends BaseController
{
    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 15);
        $keyword = $request->get('keyword', '');
        $status = $request->get('status');
        $startDate = $request->get('start_date', '');
        $endDate = $request->get('end_date', '');

        $where = [];

        if (!empty($keyword)) {
            $where[] = ['username|ip|address', 'like', "%{$keyword}%"];
        }

        if ($status !== null && $status !== '') {
            $where[] = ['status', '=', (int) $status];
        }

        if (!empty($startDate)) {
            $where[] = ['login_time', '>=', $startDate . ' 00:00:00'];
        }

        if (!empty($endDate)) {
            $where[] = ['login_time', '<=', $endDate . ' 23:59:59'];
        }

        $total = LoginLogModel::where($where)->count();
        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;

        $list = LoginLogModel::where($where)
            ->order('login_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        return $this->success([
            'list' => $list,
            'pagination' => [
                'page' => $page,
                'page_size' => $limit,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ], '获取成功');
    }

    public function stats(Request $request)
    {
        $data = $request->get();

        try {
            $validate = new LogValidate();
            $validate->scene('stats')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $startDate = $data['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $data['end_date'] ?? date('Y-m-d');

        $logModel = new LoginLogModel();
        $stats = $logModel->getLoginStats($startDate . ' 00:00:00', $endDate . ' 23:59:59');

        $dailyStats = LoginLogModel::whereBetween('login_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->field('DATE(login_time) as date, count(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as success, SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as failed')
            ->group('DATE(login_time)')
            ->order('date', 'asc')
            ->select()
            ->toArray();

        return $this->success([
            'summary' => $stats,
            'daily' => $dailyStats,
        ], '获取成功');
    }

    public function clean(Request $request)
    {
        $data = $request->post();

        try {
            $validate = new LogValidate();
            $validate->scene('clean_login')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $beforeDate = $data['before_date'] ?? date('Y-m-d', strtotime('-90 days'));

        try {
            $count = LoginLogModel::where('login_time', '<', $beforeDate . ' 00:00:00')->delete();

            return $this->success([
                'deleted_count' => $count,
            ], "已清理 {$count} 条登录日志");
        } catch (\Exception $e) {
            return $this->error('清理登录日志失败：' . $e->getMessage());
        }
    }

    public function clear(Request $request)
    {
        $userInfo = $request->userInfo ?? [];
        $userId = $userInfo['id'] ?? 0;

        $auth = AdminAuth::instance();
        $auth->setUser($userId);
        if (!$auth->isSuperAdmin()) {
            return $this->error('无权限执行此操作', 403);
        }

        try {
            Db::startTrans();
            $count = Db::name('login_log')->delete(true);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('清空登录日志失败：' . $e->getMessage());
        }

        try {
            Event::trigger('OperateLog', [
                'user_id' => $userId,
                'username' => $userInfo['realname'] ?? $userInfo['username'] ?? '',
                'module' => '系统',
                'action' => '清空登录日志',
                'method' => 'POST',
                'url' => '/admin/login-logs/clear',
                'ip' => $request->ip(),
                'address' => '',
                'param' => '',
                'result' => json_encode(['cleared_count' => $count]),
                'status' => 1,
                'error_msg' => null,
            ]);
        } catch (\Exception $e) {
            Log::error('清空登录日志-审计记录失败: ' . $e->getMessage());
        }

        return $this->success([
            'deleted_count' => $count,
        ], "已清空 {$count} 条登录日志");
    }

    public function delete(Request $request)
    {
        $data = $request->post();
        $ids = $data['ids'] ?? [];

        if (empty($ids)) {
            return $this->error('请选择要删除的记录', 422);
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        try {
            $count = LoginLogModel::where('id', 'in', $ids)->delete();

            return $this->success([
                'deleted_count' => $count,
            ], "已删除 {$count} 条登录日志");
        } catch (\Exception $e) {
            return $this->error('删除登录日志失败：' . $e->getMessage());
        }
    }
}