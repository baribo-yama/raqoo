<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SignupController;

Route::get('/', function () {
    return view('welcome');
});

 // userフォルダのhomebladeに接続
Route::get('/home', function () {
    return view('user.home');  // userフォルダのhomebladeに接続
});

// テスト表示画面への接続
Route::get('/test', function () {
    return view('user.test');
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