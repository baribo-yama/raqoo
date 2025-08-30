<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="{{ asset('user/login_sign.css') }}">
</head>
<body>
    <div class="main">
      <p align="center"><font size=+2>ログイン</font></p>
      <hr>
      @if($errors->any())
      <div style="color:red;">
        @foreach ($errors->all() as $error)
        <p>{{ $error }}</p>
        @endforeach
      </div>
      @endif
      <div class="forms">
      <form method="POST" action="{{ route('login') }}">
        @csrf
          Email<br>
              <input type="text" id="email" name="email" placeholder="メールアドレス" required><br>
              Password<br>
              <input type="password" id="password" name="password" placeholder="パスワード" required><br>
              <br>
              <button type="submit">ログイン</button>
      </form>
      <div id="up">
      <a href="{{ url('/signup') }}">サインアップ</a>
      </div>
      </div>
    </div>
</body>
</html>