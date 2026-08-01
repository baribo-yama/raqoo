<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <link rel="stylesheet" href="{{ asset('user/login_sign.css') }}">
</head>

<body>
    <div class="main">
        <p align="center">
            <font size=+2>新規登録</font>
        </p>
        <hr>
        <div class="forms">
            <!-- バリデーションエラーメッセージの表示 -->
            @if ($errors->any())
                <div style="color:red;">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- サインアップフォーム -->
            <form method="POST" action="/signup">
                @csrf
                名字<br>
                <input type="text" id="family_name" name="family_name" placeholder="姓"><br>
                名前<br>
                <input type="text" id="first_name" name="first_name" placeholder="名"><br>
                学籍番号
                <input type="text" id="student_id" name="student_id" placeholder="学籍番号"><br>
                <!-- Major
                <input type="text" id="major" name="major" placeholder="専攻名(正式名称)"><br> -->
                メールアドレス<br>
                <input type="email" id="email" name="email" value="{{ auth()->user()?->email }}" readonly><br>
                <br>

                <button type="submit">登録</button>
            </form>
            <a href="{{ url('/login') }}">ログイン画面に戻る</a>
        </div>
    </div>
</body>

</html>
