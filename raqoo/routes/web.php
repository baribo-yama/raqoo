<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashBoardController as AdminDashBoardController;
use App\Http\Controllers\User\RoomController as UserRoomController;
use App\Http\Controllers\Admin\RoomStatusController;
use App\Http\Controllers\User\RoomStatusController as UserRoomStatusController;
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
use App\Http\Controllers\User\UnavailableController;
// use Illuminate\Auth\Events\Verified;
use App\Http\Controllers\User\GoogleLoginController;

require __DIR__ . '/auth.php';

/*======= 認証関連 ========*/
// ユーザーログイン
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ユーザー新規登録
Route::get('/signup', function () {
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

// 管理者向け教室ステータス更新API
Route::post('/admin/room-status', [RoomStatusController::class, 'updateStatus'])
    ->middleware('auth:admin')
    ->name('admin.room-status.update');

Route::get('/admin/room-status', [RoomStatusController::class, 'getStatus'])
    ->middleware('auth:admin')
    ->name('admin.room-status.get');

// 管理者ダッシュボードセル詳細取得
Route::get('/admin/room-cell-detail', [RoomStatusController::class, 'getCellDetail'])
    ->middleware('auth:admin');

// 管理者ダッシュボードセル授業登録
Route::post('/admin/room-lesson', [RoomStatusController::class, 'storeLesson'])
    ->middleware('auth:admin');

// 管理者ダッシュボード授業キャンセル
Route::delete('/admin/room-lesson/cancel', [RoomStatusController::class, 'cancelLesson'])
    ->middleware('auth:admin');

// 管理者ダッシュボード予約キャンセル
Route::post('/admin/reservations/cancel', [RoomStatusController::class, 'cancelReservation'])
    ->middleware('auth:admin');


// ユーザー向け教室ステータス更新API
Route::get('/user/room-status', [UserRoomStatusController::class, 'getStatus'])
    ->middleware('auth')
    ->name('api.room-status');

// 管理者予約管理（一括承認・却下対応版）
Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');

    // 一括操作
    Route::post('/reservations/bulk-approve', [AdminReservationController::class, 'bulkApprove'])->name('reservations.bulk_approve');
    Route::post('/reservations/bulk-reject', [AdminReservationController::class, 'bulkReject'])->name('reservations.bulk_reject');

    // 単体操作（念のため残す）
    Route::post('/reservations/{reservation}/approve', [AdminReservationController::class, 'approve'])->name('reservations.approve');
    Route::post('/reservations/{reservation}/reject', [AdminReservationController::class, 'reject'])->name('reservations.reject');
});

// 管理者教室管理
Route::get('/admin/rooms', [AdminDashBoardController::class, 'roomsIndex'])
    ->middleware('auth:admin')
    ->name('admin.rooms.index');

// 管理者お知らせ管理
Route::get('/admin/notifications', function () {
    return view('admin.notifications-index');
})->middleware('auth:admin')
    ->name('admin.notifications.index')
;


/*======= ユーザー画面 ========*/
Route::middleware('auth')->group(function () {
    // ユーザーホーム
    Route::get('/home', [UserRoomController::class, 'index'])
        ->middleware('time.guard')
        ->name('user.home.index');

    // 夜間注意事項ページ
    Route::get('/night-precautions', function () {
        return view('user.night-precautions');
    })->name('user.night.precautions');

    // ユーザー予約関連
    Route::get('/reservations/create', [UserReservationController::class, 'create'])->middleware('time.guard')->name('user.reservations.create');
    Route::post('/reservations/check', [UserReservationController::class, 'check'])->middleware('time.guard')->name('user.reservations.check'); // 確認画面
    Route::post('/reservations', [UserReservationController::class, 'store'])->middleware('time.guard')->name('user.reservations.store');       // 保存処理
    Route::get('/reservations', [UserReservationController::class, 'index'])->name('user.reservations.index');
    Route::get('/reservations/{reservation}', [UserReservationController::class, 'show'])->name('user.reservations.show');
    // 予約キャンセル
    Route::patch('/reservations/{reservation}/cancel', [UserReservationController::class, 'cancel'])->name('user.reservations.cancel');
    Route::get('/reservations-history', [UserReservationController::class, 'history'])->name('user.reservations.history');

    // ユーザー通知
    Route::get('/notifications', [UserNotificationController::class, 'index'])->name('user.notifications.index');

    // ユーザーインフォメーション画面
    Route::get('/info', function () {
        return view('user.info');
    });

    // 利用規約
    Route::get('/terms', [TermsController::class, 'index'])->name('user.terms');

    // 予約前 注意事項
    Route::get('/precautions', [PrecautionsController::class, 'show'])->name('user.precautions');

    // 同意POST
    Route::post('/precautions/agree', [PrecautionsController::class, 'agree'])->name('user.precautions.agree');

    // 自分の予約一覧取得 (JSON)
    Route::get('/user/my-reservations', [UserReservationController::class, 'myReservations'])
        ->name('user.reservations.my');

    // 予約申請できない時間帯に出す画面
    Route::get('/unavailable', [UnavailableController::class, 'showUnavailable'])->name('unavailable');
});

// Googleログイン用
Route::get('/auth/google/redirect', [GoogleLoginController::class, 'redirect']);
Route::get('/auth/google/callback', [GoogleLoginController::class, 'callback']);