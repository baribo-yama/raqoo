<?php

use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\User\RoomController as UserRoomController;
use App\Http\Controllers\Admin\RoomStatusController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SignupController;

/*======= 管理者画面 ========*/
// 管理者トップ画面への接続
Route::get('/admin-top', [AdminRoomController::class, 'index'])->name('admin.top');

// 教室ステータス（periodごと）の更新API
Route::post('/admin/room-status', [RoomStatusController::class, 'updateStatus'])->name('admin.room-status.update');



/*======= ユーザー画面 ========*/

Route::get('/home', [UserRoomController::class, 'index'])->name('user.home.index');



// ユーザーインフォメーション画面
Route::get('/info', function () {
    return view('user.info');
});

// ログイン画面
Route::get('/login',[
    LoginController::class,'showLoginForm'
])->name('login.form');
Route::post('/login',[
    LoginController::class,'login'
])->name('login');

// 新規登録
Route::get('/signup', function() {
    return view('user.signup');
});
Route::post('/signup', [
    SignupController::class, 'up'
]);
