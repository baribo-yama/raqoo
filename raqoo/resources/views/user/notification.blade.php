<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>お知らせ - Raqoo</title>
  {{-- ベースのCSS --}}
  <link rel="stylesheet" href="{{ asset('user/home.css') }}">
  {{-- Tailwind (ユーティリティ用) --}}
  <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
  {{-- Ionicons --}}
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  
  {{-- 今回のCSS --}}
  <link rel="stylesheet" href="{{ asset('user/notification.css') }}">
</head>

{{-- class名を変更して既存CSSとの予期せぬ衝突を回避 --}}
<body class="with-sidebar notification-page">
  <div class="main-wrapper">

    {{-- サイドバーコンポーネント --}}
    <x-user.sidebar />

    {{-- メインコンテンツエリア --}}
    <div class="main-content">
      <div class="content-container">
          
        {{-- タイトルエリア --}}
        <div class="page-title-area">
          <h2 class="page-title">お知らせ一覧</h2>
        </div>

        {{-- 検索・フィルターエリア（フォーム化して機能するように修正） --}}
        <div class="filter-card">
          <div class="search-box-wrapper">
              <label for="search" class="search-label">お知らせ検索</label>
              
              {{-- GETメソッドで検索を実行 --}}
              <form method="GET" action="{{ url()->current() }}" class="search-form">
                  <div class="search-input-group">
                      <input type="text" id="search" name="keyword" value="{{ request('keyword') }}" placeholder="キーワードを入力..." class="custom-input">
                      <button type="submit" class="search-btn">検索</button>
                  </div>
              </form>
          </div>
        </div>

        {{-- お知らせリスト --}}
        <div class="notification-section">
          
          @if (isset($notifications) && $notifications->count() > 0)
            {{-- CSSで制御できるようクラスを明示 --}}
            <div class="notification-list">
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
                          {{-- バッジのデザインをCSSで制御 --}}
                          <span class="status-badge">重要</span>
                      </div>
                  </div>
                </div>
              @endforeach
            </div>

            {{-- ページネーション --}}
            @if ($notifications->hasPages())
              <div class="pagination-wrapper">
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

  </div>

  {{-- JS読み込み --}}
  <script src="{{ asset('user/notification.js') }}"></script>
</body>
</html>