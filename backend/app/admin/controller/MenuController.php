<?php
namespace app\admin\controller;

use app\common\BaseController;
use app\model\Menu as MenuModel;
use app\model\MenuButton;
use app\admin\validate\MenuValidate;
use app\admin\validate\MenuButtonValidate;
use app\common\AdminAuth;
use think\Request;
use think\facade\Db;

class MenuController extends BaseController
{
    private MenuButton $menuButton;

    public function __construct(\think\App $app)
    {
        parent::__construct($app);
        $this->menuButton = new MenuButton();
    }

    private function clearAllMenuCache(): void
    {
        AdminAuth::clearAllUserCache();
        AdminAuth::clearGlobalCache();
    }
    public function index(Request $request)
    {
        $status = $request->get('status');
        $menuType = $request->get('menu_type');
        $keyword = $request->get('keyword', '');

        $query = MenuModel::order('sort', 'asc');

        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        if ($menuType !== null && $menuType !== '') {
            $query->where('menu_type', (int) $menuType);
        }

        if (!empty($keyword)) {
            $query->where('name|code', 'like', "%{$keyword}%");
        }

        $list = $query->select()->toArray();

        $this->appendButtons($list);

        $tree = (new MenuModel())->buildTree($list);

        return $this->success([
            'list' => $tree,
        ], '获取成功');
    }

    public function tree(Request $request)
    {
        $status = $request->get('status');

        $menuModel = new MenuModel();
        $list = $menuModel->getMenuTreeList($status !== null && $status !== '' ? (int) $status : null);

        $this->appendButtons($list);

        return $this->success([
            'tree' => $list,
        ], '获取成功');
    }

    public function show(Request $request, int $id)
    {
        $menu = MenuModel::find($id);
        if (!$menu) {
            return $this->error('菜单不存在', 404);
        }

        $menuData = $menu->toArray();
        $menuData['buttons'] = $this->menuButton->getButtonsByMenu($id);

        $menuModel = new MenuModel();
        $menuData['path'] = $menuModel->getMenuPath($id);

        if ($menuData['parent_id'] > 0) {
            $parent = MenuModel::find($menuData['parent_id']);
            $menuData['parent_name'] = $parent ? $parent->name : '';
        } else {
            $menuData['parent_name'] = '顶级菜单';
        }

        return $this->success($menuData, '获取成功');
    }

    public function store(Request $request)
    {
        $data = $request->post();

        try {
            $validate = new MenuValidate();
            $validate->scene('store')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (MenuModel::where('code', $data['code'])->find()) {
            return $this->error('菜单标识已存在', 422);
        }

        if (isset($data['parent_id']) && $data['parent_id'] > 0) {
            $parent = MenuModel::find($data['parent_id']);
            if (!$parent) {
                return $this->error('父菜单不存在', 422);
            }
        }

        Db::startTrans();
        try {
            $menu = new MenuModel();
            $menu->name = $data['name'];
            $menu->code = $data['code'];
            $menu->menu_type = (int) $data['menu_type'];
            $menu->parent_id = (int) ($data['parent_id'] ?? 0);
            $menu->path = $data['path'] ?? '';
            $menu->icon = $data['icon'] ?? '';
            $menu->component = $data['component'] ?? '';
            $menu->sort = (int) ($data['sort'] ?? 0);
            $menu->visible = (int) ($data['visible'] ?? 1);
            $menu->keep_alive = (int) ($data['keep_alive'] ?? 1);
            $menu->always_show = (int) ($data['always_show'] ?? 1);
            $menu->breadcrumb = (int) ($data['breadcrumb'] ?? 1);
            $menu->active_menu = $data['active_menu'] ?? '';
            $menu->is_external = (int) ($data['is_external'] ?? 0);
            $menu->is_frame = (int) ($data['is_frame'] ?? 1);
            $menu->status = (int) ($data['status'] ?? 1);
            $menu->remark = $data['remark'] ?? '';
            $menu->created_by = $request->userInfo['id'] ?? null;
            $menu->save();

            $this->clearAllMenuCache();
            Db::commit();
            return $this->success(['id' => $menu->id], '创建成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('创建菜单失败：' . $e->getMessage());
        }
    }

    public function update(Request $request, int $id)
    {
        $menu = MenuModel::find($id);
        if (!$menu) {
            return $this->error('菜单不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new MenuValidate();
            $validate->scene('update')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (isset($data['code']) && $data['code'] !== $menu->code) {
            if (MenuModel::where('code', $data['code'])->where('id', '<>', $id)->find()) {
                return $this->error('菜单标识已存在', 422);
            }
        }

        if (isset($data['parent_id']) && (int) $data['parent_id'] === $id) {
            return $this->error('父菜单不能是自己', 422);
        }

        if (isset($data['parent_id']) && (int) $data['parent_id'] > 0) {
            $menuModel = new MenuModel();
            $childIds = $menuModel->getChildrenIds($id);
            if (in_array((int) $data['parent_id'], $childIds)) {
                return $this->error('父菜单不能是自己的子菜单', 422);
            }
        }

        Db::startTrans();
        try {
            $fields = ['name', 'code', 'menu_type', 'parent_id', 'path', 'icon', 'component',
                       'sort', 'visible', 'keep_alive', 'always_show', 'breadcrumb', 'active_menu',
                       'is_external', 'is_frame', 'status', 'remark'];

            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $menu->$field = is_numeric($data[$field]) && !in_array($field, ['name', 'code', 'path', 'icon', 'component', 'active_menu', 'remark'])
                        ? (int) $data[$field]
                        : $data[$field];
                }
            }

            $menu->updated_by = $request->userInfo['id'] ?? null;
            $menu->save();

            $this->clearAllMenuCache();
            Db::commit();
            return $this->success([], '更新成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('更新菜单失败：' . $e->getMessage());
        }
    }

    public function changeStatus(Request $request, int $id)
    {
        $menu = MenuModel::find($id);
        if (!$menu) {
            return $this->error('菜单不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new MenuValidate();
            $validate->scene('change_status')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $menu->status = (int) $data['status'];
        $menu->updated_by = $request->userInfo['id'] ?? null;
        $menu->save();

        $this->clearAllMenuCache();
        return $this->success([], $data['status'] == 1 ? '菜单已启用' : '菜单已禁用');
    }

    public function destroy(int $id)
    {
        if ($id <= 0) {
            return $this->error('参数错误', 422);
        }

        $menu = MenuModel::find($id);
        if (!$menu) {
            return $this->error('菜单不存在', 404);
        }

        $children = MenuModel::where('parent_id', $id)->count();
        if ($children > 0) {
            return $this->error('请先删除子菜单', 422);
        }

        Db::startTrans();
        try {
            MenuModel::destroy($id);
            Db::name('role_menu')->where('menu_id', $id)->delete();
            Db::name('role_menu_button')
                ->where('menu_button_id', 'in', function ($query) use ($id) {
                    $query->name('sys_menu_button')->where('menu_id', $id)->field('id');
                })
                ->delete();
            Db::name('menu_button')->where('menu_id', $id)->delete();

            $this->clearAllMenuCache();
            Db::commit();
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('删除菜单失败：' . $e->getMessage());
        }
    }

    public function getButtons(int $id)
    {
        $menu = MenuModel::find($id);
        if (!$menu) {
            return $this->error('菜单不存在', 404);
        }

        $buttons = $this->menuButton->getButtonsByMenu($id);

        return $this->success($buttons, '获取成功');
    }

    public function storeButton(Request $request, int $id)
    {
        $menu = MenuModel::find($id);
        if (!$menu) {
            return $this->error('菜单不存在', 404);
        }

        $data = $request->post();
        $data['menu_id'] = $id;

        try {
            $validate = new MenuButtonValidate();
            $validate->scene('store')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (MenuButton::where('menu_id', $id)->where('code', $data['code'])->find()) {
            return $this->error('按钮编码已存在', 422);
        }

        $button = new MenuButton();
        $button->menu_id = $id;
        $button->name = $data['name'];
        $button->code = $data['code'];
        $button->icon = $data['icon'] ?? '';
        $button->sort = (int) ($data['sort'] ?? 0);
        $button->status = (int) ($data['status'] ?? 1);
        $button->created_by = $request->userInfo['id'] ?? null;
        $button->save();

        $this->clearAllMenuCache();
        return $this->success(['id' => $button->id], '创建按钮成功');
    }

    public function updateButton(Request $request, int $id, int $buttonId)
    {
        $menu = MenuModel::find($id);
        if (!$menu) {
            return $this->error('菜单不存在', 404);
        }

        $button = MenuButton::find($buttonId);
        if (!$button || $button->menu_id != $id) {
            return $this->error('按钮不存在', 404);
        }

        $data = $request->put();

        try {
            $validate = new MenuButtonValidate();
            $validate->scene('update')->check($data);
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage(), 422);
        }

        if (isset($data['code']) && $data['code'] !== $button->code) {
            if (MenuButton::where('menu_id', $id)->where('code', $data['code'])->where('id', '<>', $buttonId)->find()) {
                return $this->error('按钮编码已存在', 422);
            }
        }

        $fields = ['name', 'code', 'icon', 'sort', 'status'];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $button->$field = is_numeric($data[$field]) && in_array($field, ['sort', 'status'])
                    ? (int) $data[$field]
                    : $data[$field];
            }
        }

        $button->updated_by = $request->userInfo['id'] ?? null;
        $button->save();

        $this->clearAllMenuCache();
        return $this->success([], '更新按钮成功');
    }

    public function destroyButton(int $id, int $buttonId)
    {
        $menu = MenuModel::find($id);
        if (!$menu) {
            return $this->error('菜单不存在', 404);
        }

        $button = MenuButton::find($buttonId);
        if (!$button || $button->menu_id != $id) {
            return $this->error('按钮不存在', 404);
        }

        Db::startTrans();
        try {
            MenuButton::destroy($buttonId);
            Db::name('role_menu_button')->where('menu_button_id', $buttonId)->delete();

            $this->clearAllMenuCache();
            Db::commit();
            return $this->success([], '删除按钮成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('删除按钮失败：' . $e->getMessage());
        }
    }

    private function appendButtons(array &$list): void
    {
        foreach ($list as &$item) {
            $item['buttons'] = $this->menuButton->getButtonsByMenu($item['id']);
            if (!empty($item['children'])) {
                $this->appendButtons($item['children']);
            }
        }
    }
}