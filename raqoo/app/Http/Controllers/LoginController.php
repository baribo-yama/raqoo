<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    // ログインフォーム
    public function showLoginForm(){
        return view('user.login');
    }

    // ログイン処理
    public function login(Request $request){
    $credentials = $request->only('email','password');

        if (Auth::attempt($credentials)){
            // 認証成功
            return redirect()->intended('/home');
        } else {
            // 認証失敗
            return back()->withErrors([
                'login' => 'メールアドレスまたはパスワードが違います'
            ]);
        }
    }
}
