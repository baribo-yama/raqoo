<!DOCTYPE html>
<html lang="ja">

<head>
    {{-- =========================================================
        基本設定・外部ファイル読み込み

        読み込み順：
        1. 共通CSS
        2. Tailwind CSS
        3. お知らせ画面専用CSS

        理由：
        - ホーム画面とサイドバーの見た目を近づける
        - notification.css を最後に読み込み、お知らせ画面だけの補正を効かせる
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

    {{-- ユーティリティCSS --}}
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

    {{-- お知らせ画面専用CSS --}}
    <link rel="stylesheet" href="{{ asset('user/notification.css') }}?v={{ time() }}">

    {{-- アイコンライブラリ --}}
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons/ionicons.js"></script>
</head>

<body class="notification-page">
    {{-- =========================================================
        全体レイアウト
        - 左側に共通サイドバー
        - 右側にお知らせ一覧
    ========================================================= --}}
    <div class="layout">

        {{-- 共通サイドバー --}}
        <x-user.sidebar />

        {{-- スマホ用バックドロップ --}}
        <div class="m-backdrop" id="navBackdrop" aria-hidden="true"></div>

        {{-- =====================================================
            お知らせ一覧メインコンテンツ
        ===================================================== --}}
        <main class="content-area notification-content-area">
            <div class="content-container">

                {{-- -------------------------------------------------
                    ページタイトル
                    - .page-title の基本デザインは home.css 側を使用
                    - notification.css 側では余白だけ調整
                ------------------------------------------------- --}}
                <div class="page-title-area">
                    <h2 class="page-title">お知らせ一覧</h2>
                </div>

                {{-- -------------------------------------------------
                    検索エリア
                    - GET送信で keyword パラメータを付ける
                    - 検索後も request('keyword') で入力値を保持する
                ------------------------------------------------- --}}
                <section class="filter-card" aria-label="お知らせ検索">
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
                                    class="custom-input">

                                <button type="submit" class="notification-search-btn">
                                    検索
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                {{-- -------------------------------------------------
                    お知らせ一覧
                    - お知らせがある場合はカード形式で表示
                    - お知らせがない場合は空状態メッセージを表示
                ------------------------------------------------- --}}
                <section class="notification-section" aria-label="お知らせ一覧">
                    @if (isset($notifications) && $notifications->count() > 0)
                    <div class="notification-list">
                        @foreach ($notifications as $notification)
                        <article class="notification-card">

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

                            {{-- 右側：お知らせ本文・ステータス表示 --}}
                            <div class="card-right">
                                <p class="notif-message">
                                    {{ $notification->message }}
                                </p>

                                <div class="notif-footer">
                                    {{-- 現状は固定表示。重要度をDBで管理する場合は、ここを条件分岐に変更する。 --}}
                                    <span class="status-badge">重要</span>
                                </div>
                            </div>
                        </article>
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
                </section>
            </div>
        </main>
    </div>

    {{--
        お知らせ画面用JS。
        サイドバーの active 制御と、スマホサイドバー開閉を行う。
        ホーム画面専用の予約ステータス取得処理は読み込まない。
    --}}
    <script src="{{ asset('user/notification.js') }}?v={{ time() }}"></script>
</body>

</html>