<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>予約詳細 - Raqoo</title>
    <link rel="stylesheet" href="{{ asset('user/home.css') }}?v={{ time() }}">
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .topbar {
            height: 60px;
            display: none;
        }
        .details {
            width: 100%;
            margin-left: 0;
            padding: 24px;
        }
        .recentOrders {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .cardHeader {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .cardHeader h2 {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="layout">
        <!-- ナビゲーション -->
        <x-user.sidebar />

        <!-- メインコンテンツ -->
        <div class="content-area">
            <!-- トップバー -->
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

            <!-- 予約詳細 -->
            <div class="details">
                <div class="recentOrders">
                    <div class="cardHeader">
                        <h2>予約詳細</h2>
                        <a href="{{ route('user.reservations.index') }}"
                           class="px-4 py-2 text-sm bg-gray-500 text-white rounded hover:bg-gray-600 transition duration-200">
                            一覧に戻る
                        </a>
                    </div>

                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <!-- ステータス表示 -->
                    <div class="mb-6 text-center">
                        <span class="inline-block px-4 py-2 text-lg font-semibold rounded-lg
                            @if($reservation->status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($reservation->status === 'approved') bg-green-100 text-green-800
                            @elseif($reservation->status === 'rejected') bg-red-100 text-red-800
                            @elseif($reservation->status === 'cancelled') bg-gray-100 text-gray-800
                            @elseif($reservation->status === 'in_use') bg-blue-100 text-blue-800
                            @endif">
                            @if($reservation->status === 'pending') 承認待ち
                            @elseif($reservation->status === 'approved') 承認済み
                            @elseif($reservation->status === 'rejected') 却下
                            @elseif($reservation->status === 'cancelled') キャンセル済み
                            @elseif($reservation->status === 'in_use') 利用中
                            @endif
                        </span>
                    </div>

                    <!-- 予約情報 -->
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold mb-4">予約情報</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-gray-600">教室:</span>
                                    <span class="font-semibold">{{ $reservation->room->name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">階層:</span>
                                    <span class="font-semibold">{{ $reservation->room->floor }}階</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">日付:</span>
                                    <span class="font-semibold">{{ \Carbon\Carbon::parse($reservation->date)->format('Y年m月d日') }}({{ \Carbon\Carbon::parse($reservation->date)->locale('ja')->dayName }})</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">時限:</span>
                                    <span class="font-semibold">{{ $reservation->period }}限</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">収容人数:</span>
                                    <span class="font-semibold">{{ $reservation->room->capacity }}名</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">設備:</span>
                                    <span class="font-semibold">{{ $reservation->room->equipment }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 利用目的 -->
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold mb-2">利用目的</h3>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p>{{ $reservation->purpose }}</p>
                        </div>
                    </div>

                    <!-- 代表者情報 -->
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold mb-2">代表者</h3>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p>{{ $reservation->user->name }} ({{ $reservation->user->student_id }})</p>
                        </div>
                    </div>

                    <!-- 利用メンバー -->
                    @if($reservation->members)
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold mb-2">利用メンバー</h3>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <pre class="whitespace-pre-wrap">{{ $reservation->members }}</pre>
                        </div>
                    </div>
                    @endif

                    <!-- 申請日時 -->
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold mb-2">申請日時</h3>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p>{{ $reservation->created_at->format('Y年m月d日 H:i') }}</p>
                        </div>
                    </div>

                    <!-- 却下理由（却下の場合のみ） -->
                    @if($reservation->status === 'rejected' && $reservation->rejection_reason)
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold mb-2 text-red-700">却下理由</h3>
                        <div class="p-4 bg-red-50 rounded-lg border border-red-200">
                            <p class="text-red-800">{{ $reservation->rejection_reason }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- アクションボタン -->
                    <div class="flex justify-center space-x-4">
                        @if(in_array($reservation->status, ['pending', 'approved']) && \Carbon\Carbon::parse($reservation->date)->isFuture())
                            <!-- キャンセルボタン -->
                            <button onclick="confirmCancel()"
                                    class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200">
                                <ion-icon name="close-circle-outline" class="mr-2"></ion-icon>
                                予約をキャンセル
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- キャンセル確認モーダル -->
    <div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg max-w-md mx-4">
            <h3 class="text-lg font-semibold mb-4">予約キャンセルの確認</h3>
            <p class="text-gray-600 mb-6">本当にこの予約をキャンセルしますか？<br>この操作は取り消すことができません。</p>
            <div class="flex justify-end space-x-3">
                <button onclick="closeModal()"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition duration-200">
                    キャンセル
                </button>
                <form action="{{ route('user.reservations.cancel', $reservation->id) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition duration-200">
                        予約をキャンセル
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmCancel() {
            document.getElementById('cancelModal').classList.remove('hidden');
            document.getElementById('cancelModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('cancelModal').classList.add('hidden');
            document.getElementById('cancelModal').classList.remove('flex');
        }

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
        let main = document.querySelector('.layout');
        toggle.onclick = function() {
            navigation.classList.toggle('active');
            main.classList.toggle('nav-open');
        }
    </script>
</body>
</html>
