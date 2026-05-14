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

        // バリデーションエラー時のメッセージ
        $messages = [
            'password.required' => 'パスワードを入力してください。',
            'password.min' => 'パスワードは8文字以上20文字以内で入力してください。',
            'password.max' => 'パスワードは8文字以上20文字以内で入力してください。',
            'password.letters' => 'パスワードは最低でも英字を1文字以上含めてください。',
            'password.mixed' => '大文字と小文字を両方含めてください。',
            'password.numbers'    => '数字を1文字以上含めてください。',
            'password.symbols'    => '記号を1文字以上含めてください。',
            'password.uncompromised' => 'このパスワードは漏洩した可能性があるため使用できません。別のパスワードを設定してください。',
        ];

        $request->validate([
            'name' => 'required|string|max:255',
            'student_id' => 'required|string|max:255|unique:users,student_id',
            'major' => 'required',
            'email' => 'required|string|max:255|unique:users',
            'password' => [
                'required',
                Password::min(8)
                ->max(20)     // 最大20文字
                ->letters()   // 文字を入れる
                ->mixedCase() // 大文字小文字両方入れる
                ->numbers()   // 数字入れる
                // ->symbols()   // 記号
                ->uncompromised(), // 漏洩済みのパスワードじゃないかどうか
                ],
        ], $messages);

        // ドメイン制限（登録はtechもしくはssmの学生・先生のみができる）
        $allowedDomains = ['@edu.sba.ac.jp', '@edu.ssm.ac.jp', '@sba.ac.jp', '@ssm.ac.jp'];
        $emailDomain = substr(strrchr($request->email, "@"), 0); // 入力されたメールアドレスのドメイン

        if (!in_array($emailDomain, $allowedDomains)) {
            return back()->withErrors(['email' => '無効なメールアドレスです。学校のメールアドレスで登録してください。'])->withInput();
        }


        // ユーザー作成
        $user = \App\Models\User::create([
            'name' => $request->name,
            'student_id' => $request->student_id,
            'major' => $request -> major,
            'email' => $request->email,
            'password' => Hash::make($request->password),

        ]);
        // メールを送信
        event(new Registered($user));

        // 自動ログインさせる場合はこれ
        // Auth::login($user);

        // 登録後ログインページへリダイレクト
        return redirect('/login');
    }
}
