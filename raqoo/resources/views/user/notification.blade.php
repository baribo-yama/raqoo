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
  
  <link rel="stylesheet" href="{{ asset('user/notification.css') }}">
</head>

<body class="with-sidebar">
  <div class="main-content">

    {{-- ナビゲーション（サイドバー） --}}
    <x-user.sidebar />

    {{-- ★メインコンテンツエリア --}}
    <div class="content-container">
        
      {{-- タイトルエリア --}}
      <div class="page-title-area">
        <h2 class="page-title">お知らせ一覧</h2>
      </div>

      {{-- 検索・フィルターエリア --}}
      <div class="filter-card">
        <div class="search-box-wrapper">
            <label for="search" class="search-label">お知らせ検索</label>
            <div class="search-input-group">
                <input type="text" id="search" placeholder="キーワードを入力..." class="custom-input">
                <button class="search-btn">検索</button>
            </div>
        </div>
      </div>

      {{-- お知らせリスト --}}
      <div class="notification-section">
        
        @if (isset($notifications) && $notifications->count() > 0)
          <div class="notification-list space-y-4">
            @foreach ($notifications as $notification)
              <div class="notification-card">
                <div class="card-left">
                    <div class="time-badge">
                        <span class="time-text">{{ $notification->created_at->format('H:i') }}</span>
                        <span class="date-sub">{{ $notification->created_at->format('m/d') }}</span>
                    </div>
                </div>
                <div class="card-right">
                    <p class="notif-message">{{ $notification->message }}</p>
                    <div class="notif-footer">
                        <span class="status-badge">重要</span>
                    </div>
                </div>
              </div>
            @endforeach
          </div>

          @if ($notifications->hasPages())
            <div class="mt-8 flex justify-center">
              {{ $notifications->links() }}
            </div>
          @endif
        @else
          <div class="empty-state">
            <p>現在、新しいお知らせはありません。</p>
          </div>
        @endif
      </div>
    </div>

  </div>

  {{-- ★ここだけ残す（JSファイルの読み込み） --}}
  <script src="{{ asset('user/notification.js') }}"></script>
</body>
</html>