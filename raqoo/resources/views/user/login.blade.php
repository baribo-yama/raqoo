<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Raqoo - Login</title>
  <!-- 先に既存のログイン用CSSを適用 -->
  <link rel="stylesheet" href="{{ asset('user/login_sign.css') }}">
  <!-- ホーム画面のスタイルを取り込み（ロゴ・背景など） -->
  <link rel="stylesheet" href="{{ asset('user/home.css') }}" />
  <!-- Tailwind（アラート等の簡易スタイル用） -->
  <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
  <!-- Ionicons（必要なら） -->
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
  <!-- ヘッダー（ホームと同様のロゴ表示） -->
  <div class="header">
    <div class="logo-wrapper">
      <img src="{{ asset('user/images/raqoo-logo.png') }}" alt="raqoo logo" class="raqoo-logo">
    </div>
  </div>

  <!-- ログインカード -->
  <div class="main">
    @if($errors->any())
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        @foreach ($errors->all() as $error)
          <p>{{ $error }}</p>
        @endforeach
      </div>
    @endif

    <div class="forms">
      <form method="POST" action="{{ route('login') }}">
        @csrf

        <label for="email">Email</label><br>
        <input type="text" id="email" name="email" placeholder="メールアドレス" required><br>

        <label for="password">Password</label><br>
        <input type="password" id="password" name="password" placeholder="パスワード" required><br>

                <br>
                <button type="submit" class="login-btn">ログイン</button>
      </form>
      <div id="up" class="mt-2">
        <a href="{{ url('/signup') }}">新規登録</a>
      </div>
    </div>
  </div>
</body>
</html>