<!DOCTYPE html>
<html lang="ja">

<head>
    {{-- =========================================================
        基本設定・外部ファイル読み込み
        - 文字コード / レスポンシブ設定
        - キャッシュ無効化
        - 共通CSS
        - お知らせ画面専用CSS
        - Tailwind CSS
        - Ionicons
    ========================================================= --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>お知らせ - Raqoo</title>

    {{-- 共通CSS --}}
    <link rel="stylesheet" href="{{ asset('user/home.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('user/home.mobile.css') }}?v={{ time() }}">

    {{-- お知らせ画面専用CSS --}}
    <link rel="stylesheet" href="{{ asset('user/notification.css') }}?v={{ time() }}">

    {{-- ユーティリティCSS --}}
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

    {{-- アイコンライブラリ --}}
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body class="notification-page">
    {{-- =========================================================
        全体レイアウト
        - 左側にサイドバー
        - 右側にお知らせ一覧のメインコンテンツ
    ========================================================= --}}
    <div class="layout">

        {{-- 共通サイドバー --}}
        <x-user.sidebar />

        {{-- =====================================================
            メインコンテンツエリア
        ===================================================== --}}
        <div class="content-area notification-content-area">
            <div class="content-container">

                {{-- -------------------------------------------------
                    ページタイトル
                    - 予約一覧・利用履歴とタイトルデザインを統一
                ------------------------------------------------- --}}
                <div class="page-title-area">
                    <h2 class="page-title">お知らせ一覧</h2>
                </div>

                {{-- -------------------------------------------------
                    検索エリア
                    - keyword パラメータでお知らせを検索
                    - GET送信なのでURLに検索条件が残る
                    - request('keyword') で検索後も入力値を保持
                ------------------------------------------------- --}}
                <div class="filter-card">
                    <div class="search-box-wrapper">
                        <label for="search" class="search-label">お知らせ検索</label>

                        <form method="GET" action="{{ url()->current() }}" class="notification-search-form">
                            <div class="search-input-group">
                                <input
                                    type="text"
                                    id="search"
                                    name="keyword"
                                    value="{{ request('keyword') }}"
                                    placeholder="キーワードを入力..."
                                    class="custom-input"
                                >

                                <button type="submit" class="notification-search-btn">
                                    検索
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- -------------------------------------------------
                    お知らせ一覧エリア
                    - $notifications がある場合はカード形式で表示
                    - データがない場合は空状態メッセージを表示
                ------------------------------------------------- --}}
                <div class="notification-section">
                    @if (isset($notifications) && $notifications->count() > 0)
                        {{-- お知らせカード一覧 --}}
                        <div class="notification-list">
                            @foreach ($notifications as $notification)
                                <div class="notification-card">

                                    {{-- 左側：作成時刻・日付 --}}
                                    <div class="card-left">
                                        <div class="time-badge">
                                            <span class="time-text">
                                                {{ $notification->created_at->format('H:i') }}
                                            </span>

                                            <span class="date-sub">
                                                {{ $notification->created_at->format('m/d') }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- 右側：お知らせ本文・ステータス --}}
                                    <div class="card-right">
                                        <p class="notif-message">
                                            {{ $notification->message }}
                                        </p>

                                        <div class="notif-footer">
                                            {{-- 現状は固定表示。重要度をDB管理する場合はここを条件分岐に変更する --}}
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
                        {{-- お知らせが存在しない場合の表示 --}}
                        <div class="empty-state">
                            <p>現在、新しいお知らせはありません。</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- お知らせ画面専用JavaScript --}}
    <script src="{{ asset('user/notification.js') }}?v={{ time() }}"></script>
</body>

</html>