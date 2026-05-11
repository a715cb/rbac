<?php
// +----------------------------------------------------------------------
// | 操作日志模型
// +----------------------------------------------------------------------
namespace app\model;

use app\common\BaseModel;

class OperationLog extends BaseModel
{
    protected $table = 'sys_operation_log';

    protected $pk = 'id';

    protected $autoWriteTimestamp = false;

    protected $createTime = false;

    protected $updateTime = false;

    protected $deleteTime = false;

    protected $defaultSoftDelete = null;

    protected $hidden = [];

    protected $append = [];

    protected $type = [
        'user_id' => 'integer',
        'status' => 'integer',
        'duration' => 'integer',
    ];

    public function recordOperation(
        ?int $userId,
        ?string $username,
        string $module,
        string $action,
        string $method,
        string $url,
        ?string $ip,
        ?string $address,
        ?string $param,
        ?string $result,
        int $status = 1,
        ?string $errorMsg = null,
        ?int $duration = null
    ): bool {
        try {
            $this->insert([
                'user_id' => $userId,
                'username' => $username,
                'module' => $module,
                'action' => $action,
                'method' => $method,
                'url' => $url,
                'ip' => $ip,
                'address' => $address,
                'param' => $param,
                'result' => $result,
                'status' => $status,
                'error_msg' => $errorMsg,
                'duration' => $duration,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUserOperations(int $userId, int $limit = 50): array
    {
        return $this->where('user_id', $userId)
            ->order('create_time', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    public function getModuleStats(string $startDate, string $endDate): array
    {
        return $this->whereBetween('create_time', [$startDate, $endDate])
            ->field('module, count(*) as count')
            ->group('module')
            ->select()
            ->toArray();
    }
}