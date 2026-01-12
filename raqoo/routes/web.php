<?php

use App\Http\Controllers\Admin\DashBoardController as AdminDashBoardController;
use App\Http\Controllers\User\RoomController as UserRoomController;
use App\Http\Controllers\Admin\RoomStatusController;
use App\Http\Controllers\User\ReservationController as UserReservationController;
use App\Http\Controllers\User\NotificationController as UserNotificationController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\User\TermsController;
use App\Http\Controllers\User\PrecautionsController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SignupController;

/*======= 認証関連 ========*/
// ユーザーログイン
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ユーザー新規登録
Route::get('/signup', function() {
    return view('user.signup');
});
Route::post('/signup', [SignupController::class, 'up']);

/*======= 管理者画面 ========*/
// 管理者ログイン
Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login.form');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login');
Route::post('/admin/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

// 管理者ダッシュボード
Route::get('/admin/dashboard', [AdminDashBoardController::class, 'index'])
    ->middleware('auth:admin')
    ->name('admin.dashboard');

// 予約リクエスト数取得
Route::get('/admin/reservations/count', [AdminReservationController::class, 'count'])
    ->middleware('auth:admin')
    ->name('admin.reservations.count');

// 教室ステータス更新API（管理者）
Route::post('/admin/room-status', [RoomStatusController::class, 'updateStatus'])->name('admin.room-status.update');
Route::get('/admin/room-status', [RoomStatusController::class, 'getStatus'])->name('admin.room-status.get');

// ユーザー向け教室ステータス更新API
Route::get('/user/room-status', [RoomStatusController::class, 'getStatus'])->name('api.room-status');

// 管理者予約管理
Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
    Route::post('/reservations/{reservation}/approve', [AdminReservationController::class, 'approve'])->name('reservations.approve');
    Route::post('/reservations/{reservation}/reject', [AdminReservationController::class, 'reject'])->name('reservations.reject');
});
// Route::get('/admin/reservations', [AdminReservationController::class, 'index'])->name('admin.reservations.index');
// Route::get('/admin/reservations/{reservation}', [AdminReservationController::class, 'show'])->name('admin.reservations.show');
// Route::patch('/admin/reservations/{reservation}/status', [AdminReservationController::class, 'updateStatus'])->name('admin.reservations.updateStatus');
// Route::patch('/admin/reservations/{reservation}/reject', [AdminReservationController::class, 'reject'])->name('admin.reservations.reject');

// 管理者教室管理
Route::get('/admin/rooms', [AdminDashBoardController::class, 'roomsIndex'])->name('admin.rooms.index');

// 管理者お知らせ管理
Route::get('/admin/notifications', function() {
    return view('admin.notifications-index');
})->name('admin.notifications.index');

/*======= ユーザー画面 ========*/
// ユーザーホーム
Route::get('/home', [UserRoomController::class, 'index'])->name('user.home.index');

// 夜間注意事項ページ
Route::get('/night-precautions', function () {
    return view('user.night-precautions');
})->name('user.night.precautions');

// 注意事項ページ（昼間/共通版がある場合）
Route::get('/precautions', function () {
    return view('user.precautions');
})->name('user.precautions');

// ユーザー予約関連
Route::get('/reservations/create', [UserReservationController::class, 'create'])->name('user.reservations.create');
Route::post('/reservations/confirm', [UserReservationController::class, 'confirm'])->name('user.reservations.confirm');
Route::post('/reservations', [UserReservationController::class, 'store'])->name('user.reservations.store');
Route::get('/reservations', [UserReservationController::class, 'index'])->name('user.reservations.index');
Route::get('/reservations/{reservation}', [UserReservationController::class, 'show'])->name('user.reservations.show');
Route::patch('/reservations/{reservation}/cancel', [UserReservationController::class, 'cancel'])->name('user.reservations.cancel');
Route::get('/reservations-history', [UserReservationController::class, 'history'])->name('user.reservations.history');

// ユーザー通知
Route::get('/notifications', [UserNotificationController::class, 'index'])->name('user.notifications.index');

// ユーザー利用規約
Route::get('/terms', function () {
    return view('user.precautions', [
        'showAgree' => false,
        'next' => null,
        'startStep' => 1,
    ]);
})->name('user.terms');

// ユーザーインフォメーション画面
Route::get('/info', function () {
    return view('user.info');
});

// ✅ 利用規約（同意ボタン無し）
Route::get('/terms', [TermsController::class, 'index'])
    ->middleware('auth')
    ->name('user.terms');

// ✅ 予約前 注意事項（同意ボタンあり）
Route::get('/precautions', [PrecautionsController::class, 'show'])
    ->middleware('auth')
    ->name('user.precautions');

// ✅ 同意POST（次回スキップもここで保存）
Route::post('/precautions/agree', [PrecautionsController::class, 'agree'])
    ->middleware('auth')
    ->name('user.precautions.agree');
