<?php
namespace app\admin\controller;

use app\model\MenuButton as MenuButtonModel;
use app\model\Menu as MenuModel;
use app\admin\validate\MenuButtonValidate;
use app\common\SimpleCache;
use think\Request;
use think\facade\Db;

class MenuButtonController extends BaseController
{
    private MenuModel $menuModel;

    public function __construct(\think\App $app)
    {
        parent::__construct($app);
        $this->menuModel = new MenuModel();
    }

    public function index(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', 15);
        $keyword = $request->get('keyword', '');
        $status = $request->get('status');
        $menuId = $request->get('menu_id');

        $where = [];

        if (!empty($keyword)) {
            $where[] = ['name|code', 'like', "%{$keyword}%"];
        }

        if ($status !== null && $status !== '') {
            $where[] = ['status', '=', (int) $status];
        }

        if ($menuId !== null && $menuId !== '') {
            $where[] = ['menu_id', '=', (int) $menuId];
        }

        $total = MenuButtonModel::where($where)->count();
        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;

        $list = MenuButtonModel::where($where)
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $menuNames = [];
        foreach ($list as &$item) {
            if (!isset($menuNames[$item['menu_id']])) {
                $menu = MenuModel::find($item['menu_id']);
                $menuNames[$item['menu_id']] = $menu ? $menu->name : '未知菜单';
            }
            $item['menu_name'] = $menuNames[$item['menu_id']];
        }

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

    public function show(int $id)
    {
        $button = MenuButtonModel::find($id);
        if (!$button) {
            return $this->error('按钮不存在', 404);
        }

        $buttonData = $button->toArray();

        $menu = MenuModel::find($buttonData['menu_id']);
        $buttonData['menu_name'] = $menu ? $menu->name : '未知菜单';

        if ($menu && $menu->parent_id > 0) {
            $parent = MenuModel::find($menu->parent_id);
            $buttonData['menu_path'] = $parent ? $parent->name . ' / ' . $menu->name : $menu->name;
        } else {
            $buttonData['menu_path'] = $menu ? $menu->name : '';
        }

        return $this->success($buttonData, '获取成功');
    }

    public function changeStatus(Request $request, int $id)
    {
        $button = MenuButtonModel::find($id);
        if (!$button) {
            return $this->error('按钮不存在', 404);
        }

        $status = (int) $request->put('status', 0);

        $button->status = $status;
        $button->updated_by = $request->userInfo['id'] ?? null;
        $button->save();

        $this->clearAllMenuCache();
        return $this->success([], $status === 1 ? '按钮已启用' : '按钮已禁用');
    }

    public function batchStatus(Request $request)
    {
        $ids = $request->post('ids', []);
        $status = (int) $request->post('status', 0);

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要操作的按钮', 422);
        }

        Db::startTrans();
        try {
            MenuButtonModel::where('id', 'in', $ids)->update([
                'status' => $status,
                'updated_by' => $request->userInfo['id'] ?? null,
            ]);

            $this->clearAllMenuCache();
            Db::commit();
            return $this->success([], $status === 1 ? '批量启用成功' : '批量禁用成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('操作失败：' . $e->getMessage());
        }
    }

    public function batchDelete(Request $request)
    {
        $ids = $request->post('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要删除的按钮', 422);
        }

        Db::startTrans();
        try {
            MenuButtonModel::destroy($ids);
            Db::name('sys_role_menu_button')->where('menu_button_id', 'in', $ids)->delete();

            $this->clearAllMenuCache();
            Db::commit();
            return $this->success([], '批量删除成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('批量删除失败：' . $e->getMessage());
        }
    }

    private function clearAllMenuCache(): void
    {
        $userIds = Db::name('sys_user')->where('status', 1)->column('id');
        foreach ($userIds as $userId) {
            SimpleCache::delete('user_menu_tree_' . $userId);
            SimpleCache::delete('user_menu_codes_' . $userId);
            SimpleCache::delete('user_api_codes_' . $userId);
        }
    }
}
