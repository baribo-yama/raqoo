<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class SignupController extends Controller
{
    public function up(Request $request)
    {
        // dd($request->all()); // ←フォームから送信されたデータを確認するためのデバッグコード

        $request->validate([
            'email' => 'required|string|max:255|unique:users',
            'password' => 'required|min:6',// パスワードは英語と数字混ぜる
            'name' => 'required|string|max:255',
            'student_id' => 'required|string|max:255|unique:users,student_id',
        ]);

        // ドメイン制限
        $allowedDomains = ['@edu.sba.ac.jp', '@edu.ssm.ac.jp', '@sba.ac.jp', '`ssm.ac.jp'];
        // ↑登録はtechもしくはssmの学生・先生のみができる
        $emailDomain = substr(strrchr($request->email, "@"), 0);
        
        if (!in_array($emailDomain, $allowedDomains)) {
            return back()->withErrors(['email' => '無効なメールアドレスです。'])->withInput();
        }

        // ユーザー作成
        $user = \App\Models\User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'name' => $request->name,
            'student_id' => $request->student_id,
        ]);

        // 自動ログインさせる場合はこれ
        // Auth::login($user);

        // 登録後ログインページへリダイレクト
        return redirect('/login');
    }
}
