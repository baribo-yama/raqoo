<?php

namespace App\Http\Controllers;

abstract class Controller
//  ユーザーhome画面に飛ぶ
{
    public function redirectUserHome() {
        return view('home');
    }
}
