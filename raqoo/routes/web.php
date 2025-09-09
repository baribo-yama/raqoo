<?php

use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\User\RoomController as UserRoomController;
use App\Http\Controllers\Admin\RoomStatusController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

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

// テスト表示画面への接続
Route::get('/test', function () {
    return view('');
});
