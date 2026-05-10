<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>お知らせ - Raqoo</title>

    <link rel="stylesheet" href="{{ asset('user/home.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('user/home.mobile.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('user/notification.css') }}?v={{ time() }}">

    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons/ionicons.js"></script>
</head>

<body class="notification-page">
    <div class="layout">

        <x-user.sidebar />

        <div class="content-area notification-content-area">
            <div class="content-container">

                <div class="page-title-area">
                    <h2 class="page-title">お知らせ一覧</h2>
                </div>

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
                                <button type="submit" class="notification-search-btn">検索</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="notification-section">
                    @if (isset($notifications) && $notifications->count() > 0)
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
                                            <span class="status-badge">重要</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

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

    <script src="{{ asset('user/notification.js') }}?v={{ time() }}"></script>
</body>

</html>