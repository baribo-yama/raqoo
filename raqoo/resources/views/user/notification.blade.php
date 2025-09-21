<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>お知らせ - Raqoo</title>
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
                <li class="list">
                    <a href="{{ route('user.reservations.history') }}">
                        <span class="icon"><ion-icon name="time-outline"></ion-icon></span>
                        <span class="title">利用履歴</span>
                    </a>
                </li>
                <li class="list active">
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

        <!-- お知らせ一覧 -->
        <div class="details">
            <div class="recentOrders">
                <div class="cardHeader">
                    <h2>お知らせ</h2>
                </div>

                @if(isset($notifications) && $notifications->count() > 0)
                <div class="space-y-4">
                    @foreach($notifications as $notification)
                    <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition duration-200">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <span class="inline-block w-3 h-3 rounded-full 
                                        @if($notification->type === 'system') bg-blue-500
                                        @elseif($notification->type === 'reservation') bg-green-500
                                        @elseif($notification->type === 'urgent') bg-red-500
                                        @else bg-gray-500
                                        @endif">
                                    </span>
                                    <h3 class="text-lg font-semibold text-gray-800">{{ $notification->title }}</h3>
                                    <span class="text-xs text-gray-500">
                                        {{ $notification->created_at->format('Y/m/d H:i') }}
                                    </span>
                                </div>
                                
                                <p class="text-gray-600 mb-2">{{ $notification->message }}</p>
                                
                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                    <span>
                                        @if($notification->type === 'system') システム通知
                                        @elseif($notification->type === 'reservation') 予約関連
                                        @elseif($notification->type === 'urgent') 緊急通知
                                        @else 一般通知
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- ページネーション -->
                @if(isset($notifications) && $notifications->hasPages())
                <div class="mt-6">
                    {{ $notifications->links() }}
                </div>
                @endif

                @else
                <!-- サンプル通知データ -->
                <div class="space-y-4">
                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <span class="inline-block w-3 h-3 rounded-full bg-blue-500"></span>
                                    <h3 class="text-lg font-semibold text-gray-800">システムメンテナンスのお知らせ</h3>
                                    <span class="text-xs text-gray-500">2025/09/20 10:00</span>
                                </div>
                                <p class="text-gray-600 mb-2">2025年9月25日（水）2:00〜4:00の間、システムメンテナンスを実施いたします。この時間帯は予約システムをご利用いただけません。</p>
                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                    <span>システム通知</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span>
                                    <h3 class="text-lg font-semibold text-gray-800">予約が承認されました</h3>
                                    <span class="text-xs text-gray-500">2025/09/19 14:30</span>
                                </div>
                                <p class="text-gray-600 mb-2">101教室の予約申請が承認されました。利用日: 2025年9月22日 3限目</p>
                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                    <span>予約関連</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <span class="inline-block w-3 h-3 rounded-full bg-yellow-500"></span>
                                    <h3 class="text-lg font-semibold text-gray-800">新機能のご案内</h3>
                                    <span class="text-xs text-gray-500">2025/09/18 09:00</span>
                                </div>
                                <p class="text-gray-600 mb-2">教室の利用履歴機能を追加しました。これまでの利用実績を確認することができます。</p>
                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                    <span>システム通知</span>
                                </div>
                            </div>
                        </div>
                    </div>
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
      </ul>
    </div>

    <!-- メインパネル -->
    <div class="notice-panel">
      <div class="notice-list">
        <div class="notice-list-header">お知らせ</div>
        <div class="notice-list-item active">
          <div class="notice-avatar"><i class="fas fa-user"></i></div>
          <div class="notice-list-content">
            <div class="notice-list-title">機材庫</div>
            <div class="notice-list-message">教室の予約が<br>完了しました</div>
          </div>
        </div>
        <!-- 他の通知を追加する場合はここに同じ構造で -->
      </div>
      <div class="notice-detail">
        <div class="notice-detail-card">
          <div class="notice-detail-header">
            <i class="fas fa-user"></i>
            <span>機材庫</span>
          </div>
          <div class="notice-detail-message">
            ○○さん予約が完了しました。<br>
            当日の〇〇日〇〇時〇〇分までに機材庫まで本人確認と通知の受け取りに来てください
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Ionicons for sidebar -->
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script src="{{ asset('user/notification.js') }}"></script>
</body>
</html>
