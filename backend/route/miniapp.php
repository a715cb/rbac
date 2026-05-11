<?php
use think\facade\Route;

Route::group('miniapp', function () {
    Route::post('auth/login', '\\app\\miniapp\\controller\\AuthController@login');
    Route::post('auth/refresh-token', '\\app\\miniapp\\controller\\AuthController@refreshToken');

    Route::group('', function () {
        Route::post('auth/phone', '\\app\\miniapp\\controller\\AuthController@phone');
        Route::post('auth/update-profile', '\\app\\miniapp\\controller\\AuthController@updateProfile');
        Route::post('auth/logout', '\\app\\miniapp\\controller\\AuthController@logout');

        Route::get('home/index', '\\app\\miniapp\\controller\\HomeController@index');

        Route::get('business/list', '\\app\\miniapp\\controller\\BusinessController@list');
        Route::get('business/detail/:id', '\\app\\miniapp\\controller\\BusinessController@detail');
        Route::post('business/operate', '\\app\\miniapp\\controller\\BusinessController@operate');

        Route::get('profile/show', '\\app\\miniapp\\controller\\ProfileController@show');
        Route::put('profile/update', '\\app\\miniapp\\controller\\ProfileController@update');
        Route::post('profile/avatar', '\\app\\miniapp\\controller\\ProfileController@uploadAvatar');
    })->middleware([
        \app\miniapp\middleware\MiniappAuth::class,
    ]);
});
