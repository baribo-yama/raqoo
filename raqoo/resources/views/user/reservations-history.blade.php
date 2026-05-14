<!DOCTYPE html>
<html lang="ja">

<head>
    {{-- =========================================================
        基本設定・外部ファイル読み込み
        - 文字コード / レスポンシブ設定
        - 共通CSS
        - 利用履歴ページ専用CSS
        - Tailwind CSS
        - Ionicons
    ========================================================= --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>利用履歴 - Raqoo</title>

    {{-- 共通CSS --}}
    <link rel="stylesheet" href="{{ asset('user/home.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('user/home.mobile.css') }}?v={{ time() }}">

    {{-- 利用履歴ページ専用CSS --}}
    <link rel="stylesheet" href="{{ asset('user/reservations-history.css') }}?v={{ time() }}">

    {{-- ユーティリティCSS --}}
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

    {{-- アイコンライブラリ --}}
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons/ionicons.js"></script>
</head>

<body>
    {{-- =========================================================
        全体レイアウト
        - スマホ用オーバーレイ
        - 左側にサイドバー
        - 右側に利用履歴一覧
    ========================================================= --}}

    {{-- スマホメニュー表示時の背景オーバーレイ --}}
    <div id="mobileOverlay" class="m-backdrop"></div>

    <div class="layout">
        {{-- 共通サイドバー --}}
        <x-user.sidebar />

        {{-- =====================================================
            メインコンテンツエリア
        ===================================================== --}}
        <div class="content-area">
            <div class="history-container">

                {{-- -------------------------------------------------
                    ページタイトル
                    - お知らせ一覧・予約一覧とタイトルデザインを統一
                ------------------------------------------------- --}}
                <div class="page-title-area">
                    <h2 class="page-title">利用履歴</h2>
                </div>

                {{-- -------------------------------------------------
                    利用履歴一覧
                    - 履歴がある場合はカード形式で表示
                    - 履歴がない場合は空状態メッセージを表示
                ------------------------------------------------- --}}
                <div class="history-list">
                    @if ($historyReservations->count() > 0)
                        @foreach ($historyReservations as $reservation)
                            <div class="history-card">

                                {{-- =================================================
                                    カード上部
                                    - 教室名
                                    - 利用ステータス
                                ================================================= --}}
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <h3 class="text-lg font-bold text-gray-800">
                                            {{ optional($reservation->room)->name ?? '教室情報なし' }}
                                        </h3>

                                        <span
                                            class="status-badge
                                                @if ($reservation->status === 'finished') status-completed
                                                @elseif ($reservation->status === 'cancelled') status-cancelled
                                                @else status-cancelled
                                                @endif"
                                        >
                                            @if ($reservation->status === 'finished')
                                                利用完了
                                            @elseif ($reservation->status === 'cancelled')
                                                キャンセル
                                            @else
                                                {{ $reservation->status }}
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                {{-- =================================================
                                    履歴の基本情報
                                    - 利用日
                                    - 時限
                                    - 申請日
                                    - 人数
                                ================================================= --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-2 gap-x-4 text-sm text-gray-600 mb-3">
                                    <div class="flex items-center">
                                        <span class="w-16 inline-block font-medium">日付:</span>

                                        <span class="text-black font-medium">
                                            {{ \Carbon\Carbon::parse($reservation->date)->format('Y/m/d') }}
                                            ({{ \Carbon\Carbon::parse($reservation->date)->locale('ja')->dayName }})
                                        </span>
                                    </div>

                                    <div class="flex items-center">
                                        <span class="w-16 inline-block font-medium">時限:</span>

                                        <span class="text-black font-medium">
                                            {{ $reservation->period }}限
                                        </span>
                                    </div>

                                    <div class="flex items-center">
                                        <span class="w-16 inline-block font-medium">申請日:</span>

                                        <span>
                                            {{ $reservation->created_at->format('Y/m/d') }}
                                        </span>
                                    </div>

                                    <div class="flex items-center">
                                        <span class="w-16 inline-block font-medium">人数:</span>

                                        <span>
                                            @if (!is_null($reservation->member_count))
                                                {{ $reservation->member_count }}名
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                {{-- =================================================
                                    利用目的
                                ================================================= --}}
                                <div class="mb-3">
                                    <p class="text-xs text-gray-500 mb-1">利用目的</p>

                                    <p class="text-sm text-gray-800 bg-gray-50 p-2 rounded border border-gray-100">
                                        {{ $reservation->purpose }}
                                    </p>
                                </div>

                                {{-- =================================================
                                    メンバー表示
                                    - 代表者を先頭に表示
                                    - members がある場合は改行区切りで分割して表示
                                ================================================= --}}
                                @if ($reservation->members)
                                    <div class="mb-3">
                                        <p class="text-xs text-gray-500 mb-1">メンバー</p>

                                        <div class="text-sm text-gray-700 bg-gray-50 p-2 rounded border border-gray-100">
                                            <span class="font-medium">
                                                {{ Auth::user()->name }}（代表）
                                            </span>

                                            @php
                                                $members = array_filter(explode("\n", $reservation->members));
                                            @endphp

                                            @if (count($members) > 0)
                                                @foreach ($members as $member)
                                                    <span class="mx-1 text-gray-300">|</span>
                                                    {{ trim($member) }}
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        {{-- ページネーション --}}
                        @if ($historyReservations->hasPages())
                            <div class="mt-6">
                                {{ $historyReservations->links() }}
                            </div>
                        @endif
                    @else
                        {{-- =================================================
                            空状態表示
                            - 利用履歴が存在しない場合
                        ================================================= --}}
                        <div class="text-center py-12 bg-white rounded-xl shadow-sm border border-gray-100">
                            <ion-icon name="document-text-outline" class="text-6xl text-gray-300 mb-4"></ion-icon>

                            <h3 class="text-lg font-medium text-gray-600 mb-2">
                                利用履歴がありません
                            </h3>

                            <p class="text-gray-500 mb-6">
                                過去の利用実績がここに表示されます
                            </p>

                            <a
                                href="{{ route('user.home.index') }}"
                                class="inline-block px-6 py-3 bg-blue-900 text-white rounded-lg hover:bg-blue-800 transition duration-200 font-bold shadow-md"
                            >
                                教室を予約する
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 共通JS：ハンバーガーメニューなど --}}
    <script src="{{ asset('user/home.js') }}?v={{ time() }}"></script>
</body>

</html>