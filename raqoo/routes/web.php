<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

 // userフォルダのhomebladeに接続
Route::get('/home', function () {
    return view('user.home'); 
});
Route::get('/osirase', function () {
    return view('user.osirase'); 
});

Route::get('/user-date', function () {
    return view('user.user-date');
});
// テスト表示画面への接続
Route::get('/test', function () {
    return view('user.test');
});
Route::get('/tyuuijikou', function () {
    return view('user.tyuuijikou');
});

Route::get('/yakan-tyuuijikou', function () {
    return view('user.yakan-tyuuijikou');
});
