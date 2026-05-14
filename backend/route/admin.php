<?php
use think\facade\Route;

Route::group('admin', function () {
    Route::get('', function () {
        return json([
            'code' => 200,
            'msg' => 'Welcome to Admin API',
            'data' => []
        ]);
    });

    Route::post('login', '\\app\\admin\\controller\\AuthController@login');
    Route::post('logout', '\\app\\admin\\controller\\AuthController@logout');
    Route::post('refresh-token', '\\app\\admin\\controller\\AuthController@refreshToken');
});

Route::group('admin', function () {
    Route::get('profile', '\\app\\admin\\controller\\AuthController@profile');
    Route::put('password', '\\app\\admin\\controller\\AuthController@changePassword');

    Route::get('dashboard/statistics', '\\app\\admin\\controller\\DashboardController@statistics');

    Route::group('users', function () {
        Route::get('', '\\app\\admin\\controller\\UserController@index');
        Route::get('export', '\\app\\admin\\controller\\UserController@export');
        Route::post('import', '\\app\\admin\\controller\\UserController@import');
        Route::post('', '\\app\\admin\\controller\\UserController@store');
        Route::put(':id/status', '\\app\\admin\\controller\\UserController@changeStatus');
        Route::put(':id/depts', '\\app\\admin\\controller\\UserController@updateDepts');
        Route::post(':id/depts', '\\app\\admin\\controller\\UserController@addDepts');
        Route::post(':id/assign-roles', '\\app\\admin\\controller\\UserController@assignRoles');
        Route::post(':id/reset-password', '\\app\\admin\\controller\\UserController@resetPassword');
        Route::get(':id', '\\app\\admin\\controller\\UserController@show');
        Route::put(':id', '\\app\\admin\\controller\\UserController@update');
        Route::delete(':id', '\\app\\admin\\controller\\UserController@destroy');
        Route::delete(':id/depts/:deptId', '\\app\\admin\\controller\\UserController@removeDept');
    });

    Route::group('roles', function () {
        Route::get('', '\\app\\admin\\controller\\RoleController@index');
        Route::post('', '\\app\\admin\\controller\\RoleController@store');
        Route::post(':id/assign-menus', '\\app\\admin\\controller\\RoleController@assignMenus');
        Route::post(':id/assign-buttons', '\\app\\admin\\controller\\RoleController@assignButtons');
        Route::post(':id/assign-apis', '\\app\\admin\\controller\\RoleController@assignApis');
        Route::put(':id/data-scope', '\\app\\admin\\controller\\RoleController@setDataScope');
        Route::put(':id/status', '\\app\\admin\\controller\\RoleController@changeStatus');
        Route::get(':id', '\\app\\admin\\controller\\RoleController@show');
        Route::put(':id', '\\app\\admin\\controller\\RoleController@update');
        Route::delete(':id', '\\app\\admin\\controller\\RoleController@destroy');
    });

    Route::group('menus', function () {
        Route::get('', '\\app\\admin\\controller\\MenuController@index');
        Route::get('tree', '\\app\\admin\\controller\\MenuController@tree');
        Route::post('', '\\app\\admin\\controller\\MenuController@store');
        Route::put(':id/status', '\\app\\admin\\controller\\MenuController@changeStatus');
        Route::get(':id/buttons', '\\app\\admin\\controller\\MenuController@getButtons');
        Route::post(':id/buttons', '\\app\\admin\\controller\\MenuController@storeButton');
        Route::put(':id/buttons/:buttonId', '\\app\\admin\\controller\\MenuController@updateButton');
        Route::delete(':id/buttons/:buttonId', '\\app\\admin\\controller\\MenuController@destroyButton');
        Route::get(':id', '\\app\\admin\\controller\\MenuController@show');
        Route::put(':id', '\\app\\admin\\controller\\MenuController@update');
        Route::delete(':id', '\\app\\admin\\controller\\MenuController@destroy');
    });

    Route::group('menu-buttons', function () {
        Route::get('', '\\app\\admin\\controller\\MenuButtonController@index');
        Route::post('batch-status', '\\app\\admin\\controller\\MenuButtonController@batchStatus');
        Route::post('batch-delete', '\\app\\admin\\controller\\MenuButtonController@batchDelete');
        Route::put(':id/status', '\\app\\admin\\controller\\MenuButtonController@changeStatus');
        Route::get(':id', '\\app\\admin\\controller\\MenuButtonController@show');
    });

    Route::group('depts', function () {
        Route::get('', '\\app\\admin\\controller\\DepartmentController@index');
        Route::get('tree', '\\app\\admin\\controller\\DepartmentController@tree');
        Route::post('', '\\app\\admin\\controller\\DepartmentController@store');
        Route::put(':id/status', '\\app\\admin\\controller\\DepartmentController@setStatus');
        Route::put(':id/sort', '\\app\\admin\\controller\\DepartmentController@setSort');
        Route::get(':id/users', '\\app\\admin\\controller\\DepartmentController@getUsers');
        Route::get(':id', '\\app\\admin\\controller\\DepartmentController@show');
        Route::put(':id', '\\app\\admin\\controller\\DepartmentController@update');
        Route::delete(':id', '\\app\\admin\\controller\\DepartmentController@destroy');
    });

    Route::group('apis', function () {
        Route::get('', '\\app\\admin\\controller\\ApiController@index');
        Route::get('groups', '\\app\\admin\\controller\\ApiController@getGroups');
        Route::get('menu/:menuId', '\\app\\admin\\controller\\ApiController@getByMenu');
        Route::post('', '\\app\\admin\\controller\\ApiController@store');
        Route::put(':id/status', '\\app\\admin\\controller\\ApiController@setStatus');
        Route::get(':id', '\\app\\admin\\controller\\ApiController@show');
        Route::put(':id', '\\app\\admin\\controller\\ApiController@update');
        Route::delete(':id', '\\app\\admin\\controller\\ApiController@destroy');
    });

    Route::group('login-logs', function () {
        Route::get('', '\\app\\admin\\controller\\LoginLogController@index');
        Route::get('stats', '\\app\\admin\\controller\\LoginLogController@stats');
        Route::post('clean', '\\app\\admin\\controller\\LoginLogController@clean');
        Route::post('clear', '\\app\\admin\\controller\\LoginLogController@clear');
        Route::post('delete', '\\app\\admin\\controller\\LoginLogController@delete');
    });

    Route::group('operation-logs', function () {
        Route::get('', '\\app\\admin\\controller\\OperationLogController@index');
        Route::get('stats', '\\app\\admin\\controller\\OperationLogController@stats');
        Route::post('clean', '\\app\\admin\\controller\\OperationLogController@clean');
        Route::post('clear', '\\app\\admin\\controller\\OperationLogController@clear');
        Route::post('delete', '\\app\\admin\\controller\\OperationLogController@delete');
    });

    Route::group('dict/types', function () {
        Route::get('', '\\app\\admin\\controller\\DictController@typeList');
        Route::post('', '\\app\\admin\\controller\\DictController@typeStore');
        Route::put(':id/status', '\\app\\admin\\controller\\DictController@typeChangeStatus');
        Route::get(':id', '\\app\\admin\\controller\\DictController@typeDetail');
        Route::put(':id', '\\app\\admin\\controller\\DictController@typeUpdate');
        Route::delete(':id', '\\app\\admin\\controller\\DictController@typeDestroy');
    });

    Route::group('dict/data', function () {
        Route::get('', '\\app\\admin\\controller\\DictController@dataList');
        Route::post('', '\\app\\admin\\controller\\DictController@dataStore');
        Route::post('sort', '\\app\\admin\\controller\\DictController@dataUpdateSort');
        Route::put(':id/status', '\\app\\admin\\controller\\DictController@dataChangeStatus');
        Route::get(':id', '\\app\\admin\\controller\\DictController@dataDetail');
        Route::put(':id', '\\app\\admin\\controller\\DictController@dataUpdate');
        Route::delete(':id', '\\app\\admin\\controller\\DictController@dataDestroy');
    });

    Route::group('dict/code', function () {
        Route::get(':code', '\\app\\admin\\controller\\DictController@dictByCode');
    });

    Route::group('profile', function () {
        Route::get('', '\\app\\admin\\controller\\ProfileController@show');
        Route::put('', '\\app\\admin\\controller\\ProfileController@update');
        Route::post('avatar', '\\app\\admin\\controller\\ProfileController@uploadAvatar');
        Route::put('password', '\\app\\admin\\controller\\ProfileController@changePassword');
    });
})->middleware([
    \app\admin\middleware\AuthCheck::class,
    \app\admin\middleware\RecordOperate::class,
    \app\admin\middleware\ApiPermission::class,
]);
