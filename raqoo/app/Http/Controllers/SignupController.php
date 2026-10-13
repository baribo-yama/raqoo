<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Auth\Events\Registered;

class SignupController extends Controller
{
    public function up(Request $request)
    {
        // dd($request->all()); // ←フォームから送信されたデータを確認するためのデバッグコード

        $request->validate([
            'family_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'student_id' => 'required|string|max:255|unique:users,student_id',
            'major_id' => 'nullable|exists:majors,id',
            // 'email' => 'required|string|max:255',
        ]);

        // ドメイン制限（登録はtechもしくはssmの学生・先生のみができる）
        $allowedDomains = ['@edu.sba.ac.jp', '@edu.ssm.ac.jp', '@sba.ac.jp', '@ssm.ac.jp'];
        $emailDomain = substr(strrchr($request->email, "@"), 0); // 入力されたメールアドレスのドメイン

        if (!in_array($emailDomain, $allowedDomains)) {
            return back()->withErrors(['email' => '無効なメールアドレスです。学校のメールアドレスで登録してください。'])->withInput();
        }


        // 「Googleログインでログイン」しているユーザーの情報を登録
        $user = $request->user();
        $user->update([
            'family_name' => $request->family_name,
            'first_name' => $request->first_name,
            'student_id' => $request->student_id,
            'major_id' => $request -> major_id,
            // 'email' => $request->email,
            // 'password' => Hash::make($request->password),

        ]);

        // 自動ログインさせる場合はこれ
        // Auth::login($user);

        // 登録後ログインページへリダイレクト
        return redirect('/home');
    }
}
