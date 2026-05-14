<?php
namespace app\admin\validate;

use app\common\BaseValidate;

class MenuValidate extends BaseValidate
{
    protected $rules = [
        'name' => 'require|max:50',
        'code' => 'require|max:100',
        'menu_type' => 'require|in:1,2,3',
        'parent_id' => 'integer',
        'path' => 'max:200',
        'icon' => 'max:100',
        'component' => 'max:255',
        'sort' => 'integer',
        'visible' => 'in:0,1',
        'status' => 'in:0,1',
        'keep_alive' => 'in:0,1',
        'always_show' => 'in:0,1',
        'breadcrumb' => 'in:0,1',
        'is_external' => 'in:0,1',
        'is_frame' => 'in:0,1',
        'active_menu' => 'max:255',
        'remark' => 'max:500',
    ];

    protected $message = [
        'name.require' => '菜单名称不能为空',
        'name.max' => '菜单名称不能超过50个字符',
        'code.require' => '菜单标识不能为空',
        'code.max' => '菜单标识不能超过100个字符',
        'menu_type.require' => '菜单类型不能为空',
        'menu_type.in' => '菜单类型无效：1目录 2菜单 3按钮',
        'parent_id.integer' => '父菜单ID必须为整数',
        'path.max' => '路由路径不能超过200个字符',
        'icon.max' => '图标不能超过100个字符',
        'component.max' => '组件路径不能超过255个字符',
        'sort.integer' => '排序必须为整数',
        'visible.in' => '显示状态值无效',
        'status.in' => '状态值无效',
        'keep_alive.in' => '缓存状态值无效',
        'always_show.in' => '总是显示状态值无效',
        'breadcrumb.in' => '面包屑状态值无效',
        'is_external.in' => '外链状态值无效',
        'is_frame.in' => 'iframe状态值无效',
        'active_menu.max' => '高亮菜单不能超过255个字符',
        'remark.max' => '备注不能超过500个字符',
    ];

    protected $scene = [
        'store' => ['name', 'code', 'menu_type', 'parent_id', 'path', 'icon', 'component', 'sort', 'visible', 'status', 'keep_alive', 'always_show', 'breadcrumb', 'is_external', 'is_frame', 'active_menu', 'remark'],
        'update' => ['name', 'code', 'menu_type', 'parent_id', 'path', 'icon', 'component', 'sort', 'visible', 'status', 'keep_alive', 'always_show', 'breadcrumb', 'is_external', 'is_frame', 'active_menu', 'remark'],
        'change_status' => ['status'],
    ];
}