<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

 // userフォルダのhomebladeに接続
Route::get('/home', function () {
    return view('user.home');  // userフォルダのhomebladeに接続
});
