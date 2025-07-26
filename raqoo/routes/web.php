<?php

use Illuminate\Support\Facades\Route;

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
