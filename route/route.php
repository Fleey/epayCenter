<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::group('api', function () {
    Route::controller('v1', 'api/ApiV1');
});
Route::group('Pay', function () {
    Route::controller('Yb', 'pay/YbApiV1');
    Route::controller('Xa', 'pay/XaApiV1');
    Route::controller('Ow', 'pay/OwApiV1');
    Route::controller('Xd', 'pay/XdApiV1');
    Route::controller('Eeb', 'pay/EebApiV1');
});