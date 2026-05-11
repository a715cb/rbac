<?php
namespace app\admin\event;

use app\model\OperationLog;
use think\facade\Log;

class OperateLogEvent
{
    public function handle(array $data): void
    {
        try {
            OperationLog::create([
                'user_id' => $data['user_id'] ?? null,
                'username' => $data['username'] ?? '',
                'module' => $data['module'] ?? '',
                'action' => $data['action'] ?? '',
                'method' => $data['method'] ?? '',
                'url' => $data['url'] ?? '',
                'ip' => $data['ip'] ?? '',
                'address' => $data['address'] ?? '',
                'param' => $data['param'] ?? '',
                'result' => $data['result'] ?? '',
                'status' => $data['status'] ?? 1,
                'error_msg' => $data['error_msg'] ?? null,
                'duration' => $data['duration'] ?? null,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            Log::error('操作日志写入失败: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
