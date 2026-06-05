<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約申請不可</title>
    <link rel="stylesheet" href="{{ asset('user/home.css') }}" />   <!-- ホーム画面のやつを借りる -->
    <link rel="stylesheet" href="{{ asset('user/unavailable.css') }}">
    <link rel="stylesheet" href="{{ asset('user/home.mobile.css') }}">

        <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons/ionicons.js"></script>
</head>
<body>
    <!-- サイドバーを表示 -->
    <x-user.sidebar />

    <div class="main-un">
        <div class="unavailable">
            <div class="un-1">
            現在の時間は、予約申請を受け付けておりません。<br>
            お手数ですが、明日以降に申請をお願いいたします。<br>
            </div>

            <div class="un-2">
            予約申請可能時間帯<br>
            月～金曜日：9:10～19:30<br>
            土・日・長期休暇期間：10:50～17:50<br>
            <script src="{{ asset('user/home.js') }}"></script>
</body>
            </div>
        </div>
    </div>
</body>
</html>