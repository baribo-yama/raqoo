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
                Name<br>
                <input type="text" id="name" name="name" placeholder="名前"><br>
                StudentID
                <input type="text" id="student_id" name="student_id" placeholder="学籍番号"><br>
                Major
                <input type="text" id="major" name="major" placeholder="専攻名(正式名称)"><br>
                Email<br>
                <input type="email" id="email" name="email" placeholder="学校のメールアドレス"><br>
                Password<br>
                <input type="password" id="password" name="password" placeholder="パスワードを設定"><br>
                <font size="-1s">
                    パスワードは以下の条件を満たす必要があります。<br>
                    ・英数字大文字含む8文字以上20文字以内
                </font>
                <br>

                <button type="submit">登録</button>
            </form>
            <a href="{{ url('/login') }}">ログイン画面に戻る</a>
        </div>
    </div>
</body>

</html>
