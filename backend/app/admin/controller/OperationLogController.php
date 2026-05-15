<?php
namespace app\admin\controller;

use app\common\BaseController;
use app\model\OperationLog as OperationLogModel;
use app\admin\validate\LogValidate;
use app\common\AdminAuth;
use think\Request;
use think\facade\Db;
use think\facade\Event;
use think\facade\Log;

class OperationLogController extends BaseController
{
    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 15);
        $keyword = $request->get('keyword', '');
        $userId = $request->get('user_id', '');
        $status = $request->get('status');
        $module = $request->get('module', '');
        $action = $request->get('action', '');
        $method = $request->get('method', '');
        $ip = $request->get('ip', '');
        $startDate = $request->get('start_date', '');
        $endDate = $request->get('end_date', '');

        $where = [];

        if (!empty($keyword)) {
            $where[] = ['username|url|ip|address', 'like', "%{$keyword}%"];
        }

        if (!empty($userId)) {
            $where[] = ['user_id', '=', (int) $userId];
        }

        if ($status !== null && $status !== '') {
            $where[] = ['status', '=', (int) $status];
        }

        if (!empty($module)) {
            $where[] = ['module', '=', $module];
        }

        if (!empty($action)) {
            $where[] = ['action', '=', $action];
        }

        if (!empty($method)) {
            $where[] = ['method', '=', strtoupper($method)];
        }

        if (!empty($ip)) {
            $where[] = ['ip', 'like', "%{$ip}%"];
        }

        if (!empty($startDate)) {
            $where[] = ['create_time', '>=', $startDate . ' 00:00:00'];
        }

        if (!empty($endDate)) {
            $where[] = ['create_time', '<=', $endDate . ' 23:59:59'];
        }

        $total = OperationLogModel::where($where)->count();
        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;

        $list = OperationLogModel::where($where)
            ->order('create_time', 'desc')
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

        $logModel = new OperationLogModel();
        $moduleStats = $logModel->getModuleStats($startDate . ' 00:00:00', $endDate . ' 23:59:59');

        $total = OperationLogModel::whereBetween('create_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->count();
        $success = OperationLogModel::whereBetween('create_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 1)
            ->count();
        $failed = OperationLogModel::whereBetween('create_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 0)
            ->count();

        $dailyStats = OperationLogModel::whereBetween('create_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->field('DATE(create_time) as date, count(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as success, SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as failed')
            ->group('DATE(create_time)')
            ->order('date', 'asc')
            ->select()
            ->toArray();

        $topUsers = OperationLogModel::whereBetween('create_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->field('username, count(*) as count')
            ->whereNotNull('username')
            ->group('username')
            ->order('count', 'desc')
            ->limit(10)
            ->select()
            ->toArray();

        return $this->success([
            'summary' => [
                'total' => $total,
                'success' => $success,
                'failed' => $failed,
            ],
            'module_stats' => $moduleStats,
            'daily' => $dailyStats,
            'top_users' => $topUsers,
        ], '获取成功');
    }

    public function clean(Request $request)
    {
        $data = $request->post();

        try {
            $validate = new LogValidate();
            $validate->scene('clean_operation')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $beforeDate = $data['before_date'] ?? date('Y-m-d', strtotime('-90 days'));

        try {
            $count = OperationLogModel::where('create_time', '<', $beforeDate . ' 00:00:00')->delete();

            return $this->success([
                'deleted_count' => $count,
            ], "已清理 {$count} 条操作日志");
        } catch (\Exception $e) {
            return $this->error('清理操作日志失败：' . $e->getMessage());
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
            $count = Db::name('operation_log')->delete(true);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('清空操作日志失败：' . $e->getMessage());
        }

        try {
            Event::trigger('OperateLog', [
                'user_id' => $userId,
                'username' => $userInfo['realname'] ?? $userInfo['username'] ?? '',
                'module' => '系统',
                'action' => '清空操作日志',
                'method' => 'POST',
                'url' => '/admin/operation-logs/clear',
                'ip' => $request->ip(),
                'address' => '',
                'param' => '',
                'result' => json_encode(['cleared_count' => $count]),
                'status' => 1,
                'error_msg' => null,
            ]);
        } catch (\Exception $e) {
            Log::error('清空操作日志-审计记录失败: ' . $e->getMessage());
        }

        return $this->success([
            'deleted_count' => $count,
        ], "已清空 {$count} 条操作日志");
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
            $count = OperationLogModel::where('id', 'in', $ids)->delete();

            return $this->success([
                'deleted_count' => $count,
            ], "已删除 {$count} 条操作日志");
        } catch (\Exception $e) {
            return $this->error('删除操作日志失败：' . $e->getMessage());
        }
    }
}