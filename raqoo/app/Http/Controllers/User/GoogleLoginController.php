<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;


class GoogleLoginController extends Controller
{
    // 「Googleでログイン」ボタンが押されたときの処理
    // Googleログインの画面に変遷する
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    // Googleの画面から戻ってきたときの処理
    public function callback()
    {
        $googleUser = Socialite::driver('google')->user(); // Googleからアカウント情報を受け取る

        // 返ってきたメアドを見るためのデバッグコード
        // dd([
        //     'email' => $googleUser->email,
        //     'ends_with_check' => str_ends_with($googleUser->email, '@edu.sba.ac.jp'),
        // ]);

        // ドメイン制限チェック　受け取ったドメインが学校のメアド以外だったら弾く
        $allowedDomains = [
            '@edu.sba.ac.jp',
            '@edu.ssm.ac.jp',
            '@sba.ac.jp',
            '@ssm.ac.jp',
        ];
        
        // 判定
       $isAllowed = false;
       foreach ($allowedDomains as $domain) {
        if (str_ends_with($googleUser->email, $domain)) {
            $isAllowed = true;
            break;
            }
        }

        if (!$isAllowed) {
            return redirect('/login')->withErrors('学校のアカウントでログインしてください');
        }

        // ユーザー検索 or 新規作成
        // google_idがraqoo内に登録されていれば取得・されていなければ新規作成する
        $user = User::firstOrCreate(
            ['google_id' => $googleUser->id],
            [
                'email' => $googleUser->email,
                // 'email_verified_at' => now(),
                'password' => null,
            ]
        );

        Auth::login($user);

        // ログイン後のリダイレクト先を振り分け　未登録だったら新規登録画面へ　
        if (empty($user->student_id)) {
            return redirect('/signup');
        }

        return redirect('/home');
    }
}
