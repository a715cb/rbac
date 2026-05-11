<?php
return [
    'verify_user_exists' => env('AUTH_VERIFY_USER_EXISTS', true),

    'max_login_fail_times' => env('AUTH_MAX_LOGIN_FAIL_TIMES', 5),

    'login_lock_duration' => env('AUTH_LOGIN_LOCK_DURATION', 900),

    'super_admin_code' => env('AUTH_SUPER_ADMIN_CODE', 'super_admin'),

    'cache_time' => env('AUTH_CACHE_TIME', 3600),
];
