<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約申請フォーム - Raqoo</title>
    <link rel="stylesheet" href="{{ asset('user/home.css') }}">
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div class="main-content">
        <!-- ナビゲーション -->
        <div class="navigation">
            <ul>
                <li class="list">
                    <a href="{{ route('user.home.index') }}">
                        <span class="icon"><ion-icon name="home-outline"></ion-icon></span>
                        <span class="title">ホーム</span>
                    </a>
                </li>
                <li class="list">
                    <a href="{{ route('user.reservations.index') }}">
                        <span class="icon"><ion-icon name="calendar-outline"></ion-icon></span>
                        <span class="title">予約一覧</span>
                    </a>
                </li>
                <li class="list">
                    <a href="{{ route('user.reservations.history') }}">
                        <span class="icon"><ion-icon name="time-outline"></ion-icon></span>
                        <span class="title">利用履歴</span>
                    </a>
                </li>
                <li class="list">
                    <a href="{{ route('user.notifications.index') }}">
                        <span class="icon"><ion-icon name="notifications-outline"></ion-icon></span>
                        <span class="title">お知らせ</span>
                    </a>
                </li>
                <li class="list">
                    <a href="{{ route('logout') }}">
                        <span class="icon"><ion-icon name="log-out-outline"></ion-icon></span>
                        <span class="title">ログアウト</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- メインコンテンツ -->
        <div class="topbar">
            <div class="toggle">
                <ion-icon name="menu-outline"></ion-icon>
            </div>
            <div class="search">
                <label>
                    <input type="text" placeholder="検索...">
                    <ion-icon name="search-outline"></ion-icon>
                </label>
            </div>
            <div class="user">
                <img src="assets/customer01.jpg" alt="">
            </div>
        </div>

        <!-- 予約申請フォーム -->
        <div class="details">
            <div class="recentOrders">
                <div class="cardHeader">
                    <h2>予約申請フォーム</h2>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <form action="{{ route('user.reservations.confirm') }}" method="POST">
                        @csrf
                        
                        <!-- 教室情報表示 -->
                        <div class="mb-6 p-4 bg-gray-100 rounded-lg">
                            <h3 class="text-lg font-semibold mb-2">予約詳細</h3>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <span class="text-gray-600">教室:</span>
                                    <span class="font-semibold">{{ $room->name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">日付:</span>
                                    <span class="font-semibold">{{ $date }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">時限:</span>
                                    <span class="font-semibold">{{ $period }}限</span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <span class="text-gray-600">収容人数:</span>
                                <span class="font-semibold">{{ $room->capacity }}名</span>
                            </div>
                            <div class="mt-2">
                                <span class="text-gray-600">設備:</span>
                                <span class="font-semibold">{{ $room->equipment }}</span>
                            </div>
                        </div>

                        <!-- 隠しフィールド -->
                        <input type="hidden" name="room_id" value="{{ $room->id }}">
                        <input type="hidden" name="date" value="{{ $date }}">
                        <input type="hidden" name="period" value="{{ $period }}">

                        <!-- 利用目的 -->
                        <div class="mb-6">
                            <label for="purpose" class="block text-sm font-medium text-gray-700 mb-2">
                                利用目的 <span class="text-red-500">*</span>
                            </label>
                            <textarea 
                                id="purpose" 
                                name="purpose" 
                                rows="3" 
                                maxlength="100"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="利用目的を入力してください（100文字以内）"
                                required
                            >{{ old('purpose') }}</textarea>
                            <div class="text-right text-sm text-gray-500 mt-1">
                                <span id="purposeCount">0</span>/100文字
                            </div>
                        </div>

                        <!-- 代表者情報 -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">代表者</label>
                            <div class="p-3 bg-gray-100 rounded-md">
                                {{ Auth::user()->name }} ({{ Auth::user()->student_id }})
                            </div>
                        </div>

                        <!-- 利用メンバー -->
                        <div class="mb-6">
                            <label for="members" class="block text-sm font-medium text-gray-700 mb-2">
                                利用メンバー（代表者以外、最大30名）
                            </label>
                            <textarea 
                                id="members" 
                                name="members" 
                                rows="6" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="メンバーの氏名と学籍番号を入力してください&#10;例：&#10;山田太郎 (S12345)&#10;佐藤花子 (S67890)"
                            >{{ old('members') }}</textarea>
                            <div class="text-sm text-gray-500 mt-1">
                                1行につき1名ずつ入力してください
                            </div>
                        </div>

                        <!-- ボタン -->
                        <div class="flex justify-between">
                            <a href="{{ route('user.home.index') }}" 
                               class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition duration-200">
                                キャンセル
                            </a>
                            <button type="submit" 
                                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">
                                確認画面へ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 文字数カウント
        const purposeTextarea = document.getElementById('purpose');
        const purposeCount = document.getElementById('purposeCount');
        
        purposeTextarea.addEventListener('input', function() {
            purposeCount.textContent = this.value.length;
        });

        // 初期値設定
        purposeCount.textContent = purposeTextarea.value.length;

        // ナビゲーション切り替え
        let list = document.querySelectorAll('.list');
        function activeLink() {
            list.forEach((item) => item.classList.remove('active'));
            this.classList.add('active');
        }
        list.forEach((item) => item.addEventListener('click', activeLink));

        // トグルメニュー
        let toggle = document.querySelector('.toggle');
        let navigation = document.querySelector('.navigation');
        let main = document.querySelector('.main-content');
        toggle.onclick = function() {
            navigation.classList.toggle('active');
            main.classList.toggle('active');
        }
    </script>
</body>
</html>