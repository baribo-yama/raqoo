<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約申請確認 - Raqoo</title>
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

        <!-- 予約申請確認 -->
        <div class="details">
            <div class="recentOrders">
                <div class="cardHeader">
                    <h2>予約申請確認</h2>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <!-- 確認内容 -->
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold mb-4 text-gray-800">申請内容をご確認ください</h3>
                        
                        <!-- 予約情報 -->
                        <div class="bg-gray-50 p-4 rounded-lg mb-6">
                            <h4 class="font-semibold text-lg mb-3">予約情報</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-gray-600">教室:</span>
                                    <span class="font-semibold">{{ $data['room']->name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">階層:</span>
                                    <span class="font-semibold">{{ $data['room']->floor }}階</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">日付:</span>
                                    <span class="font-semibold">{{ $data['date'] }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">時限:</span>
                                    <span class="font-semibold">{{ $data['period'] }}限</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">収容人数:</span>
                                    <span class="font-semibold">{{ $data['room']->capacity }}名</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">設備:</span>
                                    <span class="font-semibold">{{ $data['room']->equipment }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- 利用目的 -->
                        <div class="mb-6">
                            <h4 class="font-semibold text-lg mb-2">利用目的</h4>
                            <div class="p-3 bg-gray-50 rounded border">
                                {{ $data['purpose'] }}
                            </div>
                        </div>

                        <!-- 代表者 -->
                        <div class="mb-6">
                            <h4 class="font-semibold text-lg mb-2">代表者</h4>
                            <div class="p-3 bg-gray-50 rounded border">
                                {{ Auth::user()->name }} ({{ Auth::user()->student_id }})
                            </div>
                        </div>

                        <!-- 利用メンバー -->
                        @if($data['members'])
                        <div class="mb-6">
                            <h4 class="font-semibold text-lg mb-2">利用メンバー</h4>
                            <div class="p-3 bg-gray-50 rounded border">
                                <pre class="whitespace-pre-wrap">{{ $data['members'] }}</pre>
                            </div>
                        </div>
                        @endif

                        <!-- 利用人数計算 -->
                        <div class="mb-6">
                            <h4 class="font-semibold text-lg mb-2">利用人数</h4>
                            <div class="p-3 bg-blue-50 rounded border border-blue-200">
                                <span class="text-blue-800 font-semibold">
                                    合計 {{ $memberCount }}名
                                    @if($memberCount > $data['room']->capacity)
                                        <span class="text-red-600 ml-2">⚠️ 収容人数を超えています</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- エラーメッセージ -->
                    @if($memberCount > $data['room']->capacity)
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-red-800">
                            <strong>エラー:</strong> 利用人数が教室の収容人数を超えています。<br>
                            メンバー数を調整するか、別の教室をご利用ください。
                        </p>
                    </div>
                    @endif

                    <!-- ボタン -->
                    <div class="flex justify-between">
                        <button onclick="history.back()" 
                                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition duration-200">
                            戻る
                        </button>
                        
                        @if($memberCount <= $data['room']->capacity)
                        <form action="{{ route('user.reservations.store') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="room_id" value="{{ $data['room']->id }}">
                            <input type="hidden" name="date" value="{{ $data['date'] }}">
                            <input type="hidden" name="period" value="{{ $data['period'] }}">
                            <input type="hidden" name="purpose" value="{{ $data['purpose'] }}">
                            <input type="hidden" name="members" value="{{ $data['members'] }}">
                            
                            <button type="submit" 
                                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">
                                申請する
                            </button>
                        </form>
                        @else
                        <button disabled 
                                class="px-6 py-2 bg-gray-400 text-white rounded-md cursor-not-allowed">
                            申請する（人数超過）
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
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