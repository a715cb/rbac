<?php
// +----------------------------------------------------------------------
// | 登录日志模型
// +----------------------------------------------------------------------
namespace app\model;

use app\common\BaseModel;

class LoginLog extends BaseModel
{
    protected $table = 'sys_login_log';

    protected $pk = 'id';

    protected $autoWriteTimestamp = false;

    protected $createTime = false;

    protected $updateTime = false;

    protected $deleteTime = false;

    protected $defaultSoftDelete = null;

    protected $hidden = [];

    protected $append = [];

    protected $type = [
        'status' => 'integer',
        'login_time' => 'datetime',
    ];

    public function recordLogin(
        string $username,
        int $status,
        string $msg = '',
        string $ip = '',
        string $address = '',
        string $userAgent = '',
        string $os = '',
        string $browser = ''
    ): bool {
        try {
            $this->insert([
                'username' => $username,
                'ip' => $ip,
                'address' => $address,
                'user_agent' => $userAgent,
                'os' => $os,
                'browser' => $browser,
                'status' => $status,
                'msg' => $msg,
                'login_time' => date('Y-m-d H:i:s'),
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getLoginLogsByUsername(string $username, int $limit = 10): array
    {
        return $this->where('username', $username)
            ->order('login_time', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    public function getLoginStats(string $startDate, string $endDate): array
    {
        $total = $this->whereBetween('login_time', [$startDate, $endDate])->count();

        $success = $this->whereBetween('login_time', [$startDate, $endDate])
            ->where('status', 1)
            ->count();

        $failed = $this->whereBetween('login_time', [$startDate, $endDate])
            ->where('status', 0)
            ->count();

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
        ];
    }
}