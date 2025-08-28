<?php

use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\User\RoomController as UserRoomController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*======= 管理者画面 ========*/
// 管理者トップ画面への接続
Route::get('/admin-top', [AdminRoomController::class, 'index'])->name('admin.top');

// 教室のステータス更新
Route::post('/rooms/{id}/status', [AdminRoomController::class, 'updateStatus'])->name('rooms.updateStatus');




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
