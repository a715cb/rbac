<?php
namespace app\miniapp\service;

use app\model\Business;
use app\model\BusinessInteraction;

class BusinessService
{
    public function getList(array $params): array
    {
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 10);
        $keyword = trim($params['keyword'] ?? '');
        $category = trim($params['category'] ?? '');

        return Business::getListByCondition($keyword, $category, $page, $pageSize);
    }

    public function getDetail(int $id): array
    {
        $business = Business::where('status', 1)->find($id);
        if (!$business) {
            throw new \RuntimeException('内容不存在或已下架');
        }

        return $business->toArray();
    }

    public function operate(array $data, array $wxUser): array
    {
        $type = trim($data['type'] ?? '');
        $targetId = (int) ($data['target_id'] ?? 0);
        $wxUserId = (int) ($wxUser['id'] ?? 0);

        if (empty($type) || !in_array($type, ['favorite', 'like', 'collect'], true)) {
            throw new \InvalidArgumentException('不支持的操作类型');
        }

        if ($targetId <= 0) {
            throw new \InvalidArgumentException('目标ID不能为空');
        }

        if ($wxUserId <= 0) {
            throw new \RuntimeException('用户信息缺失');
        }

        $business = Business::where('status', 1)->find($targetId);
        if (!$business) {
            throw new \RuntimeException('目标内容不存在');
        }

        return BusinessInteraction::toggle($wxUserId, $targetId, $type);
    }
}
