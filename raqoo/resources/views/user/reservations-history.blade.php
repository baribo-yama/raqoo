<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>利用履歴 - Raqoo</title>
    <link rel="stylesheet" href="{{ asset('user/home.css') }}">
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
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
                <li class="list active">
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
                    <form method="POST" action="{{ route('logout') }}" style="margin: 0; width: 100%;">
                        @csrf
                        <button type="submit" style="background: none; border: none; padding: 0; width: 100%; text-align: left; display: block; color: inherit; font: inherit; cursor: pointer;">
                            <span class="icon"><ion-icon name="log-out-outline"></ion-icon></span>
                            <span class="title">ログアウト</span>
                        </button>
                    </form>
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

        <!-- 利用履歴 -->
        <div class="details">
            <div class="recentOrders">
                <div class="cardHeader">
                    <h2>利用履歴（過去分）</h2>
                </div>

                @if($historyReservations->count() > 0)
                <div class="space-y-4">
                    @foreach($historyReservations as $reservation)
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-4 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-800">{{ $reservation->room->name }}</h3>
                                    <span class="px-2 py-1 text-xs rounded-full
                                        @if($reservation->status === 'completed') bg-blue-100 text-blue-800
                                        @elseif($reservation->status === 'cancelled') bg-gray-100 text-gray-800
                                        @elseif($reservation->status === 'rejected') bg-red-100 text-red-800
                                        @endif">
                                        @if($reservation->status === 'completed') 利用完了
                                        @elseif($reservation->status === 'cancelled') キャンセル
                                        @elseif($reservation->status === 'rejected') 却下
                                        @endif
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-3">
                                    <div class="text-sm">
                                        <span class="text-gray-600">日付:</span>
                                        <span class="font-medium">{{ \Carbon\Carbon::parse($reservation->date)->format('Y/m/d') }}({{ \Carbon\Carbon::parse($reservation->date)->locale('ja')->dayName }})</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="text-gray-600">時限:</span>
                                        <span class="font-medium">{{ $reservation->period }}限</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="text-gray-600">申請日:</span>
                                        <span class="font-medium">{{ $reservation->created_at->format('Y/m/d') }}</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="text-gray-600">利用人数:</span>
                                        <span class="font-medium">
                                            {{ $reservation->member_count }}名
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <span class="text-gray-600 text-sm">利用目的:</span>
                                    <p class="text-gray-800">{{ $reservation->purpose }}</p>
                                </div>

                                @if($reservation->members)
                                <div class="mb-2">
                                    <span class="text-gray-600 text-sm">メンバー:</span>
                                    <div class="text-gray-700 text-sm">
                                        <span class="font-medium">{{ Auth::user()->name }} (代表者)</span>
                                        @php
                                            $members = array_filter(explode("\n", $reservation->members));
                                        @endphp
                                        @if(count($members) > 0)
                                            @foreach($members as $member)
                                                <br>{{ trim($member) }}
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                                @endif

                                @if($reservation->status === 'rejected' && $reservation->rejection_reason)
                                <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded">
                                    <span class="text-red-700 text-sm font-medium">却下理由:</span>
                                    <p class="text-red-700 text-sm">{{ $reservation->rejection_reason }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- ページネーション -->
                @if($historyReservations->hasPages())
                <div class="mt-6">
                    {{ $historyReservations->links() }}
                </div>
                @endif

                @else
                <div class="text-center py-12">
                    <ion-icon name="document-text-outline" class="text-6xl text-gray-300 mb-4"></ion-icon>
                    <h3 class="text-lg font-medium text-gray-600 mb-2">利用履歴がありません</h3>
                    <p class="text-gray-500">過去の利用実績がここに表示されます</p>
                    <a href="{{ route('user.home.index') }}"
                       class="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">
                        教室を予約する
                    </a>
                </div>
                @endif
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
