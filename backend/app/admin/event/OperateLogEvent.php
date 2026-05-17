<?php
/**
 * @file OperateLogEvent.php
 * @purpose 操作日志事件处理器
 * @description 监听 OperateLog 事件，将中间件采集的操作日志数据写入数据库。
 *              由 RecordOperate 中间件通过 Event::trigger('OperateLog', ...) 触发调用，
 *              实现日志记录与业务逻辑的解耦。
 * @note 写入失败时仅记录错误日志，不抛出异常，避免影响主请求的响应
 */

namespace app\admin\event;

use app\model\OperationLog;
use think\facade\Log;

/**
 * 操作日志事件处理器
 *
 * 负责接收操作日志数据并持久化到 operation_log 数据表。
 * 作为 ThinkPHP 事件监听器使用，由框架事件系统自动调度。
 *
 * 事件数据结构（由 RecordOperate 中间件传入）：
 *   - user_id    : int    操作用户 ID
 *   - username   : string 操作用户名
 *   - module     : string 操作模块（如"用户管理"）
 *   - action     : string 操作动作（如"新增"、"编辑"）
 *   - method     : string HTTP 方法（POST/PUT/DELETE 等）
 *   - url        : string 请求路径
 *   - ip         : string 操作者 IP 地址
 *   - address    : string IP 归属地（可选，需配合 IP 解析服务）
 *   - param      : string 请求参数（JSON 字符串）
 *   - result     : string 响应结果（JSON 字符串，可能为 null）
 *   - status     : int    操作状态（1=成功，0=失败）
 *   - error_msg  : string 失败时的错误信息
 *   - duration   : float  请求耗时（毫秒，可选）
 */
class OperateLogEvent
{
    /**
     * 处理操作日志事件：将日志数据写入数据库
     *
     * @param array $data 操作日志数据，键名对应 operation_log 表字段，
     *                    缺失字段使用默认值填充
     * @return void
     *
     * 处理流程：
     *   1. 使用 OperationLog::create() 将日志数据写入数据库
     *   2. 缺失字段通过 ?? 运算符提供默认值，确保数据完整性
     *   3. create_time 字段使用当前时间，不依赖数据库自动填充
     *   4. 写入异常时捕获并记录到日志文件，不向上抛出
     *
     * @note 本方法为事件监听器入口，异常不应向外传播，
     *       否则会导致主请求返回 500 错误
     */
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
