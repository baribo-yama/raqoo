<!DOCTYPE html>
<html lang="ja">

<head>
    {{-- =========================================================
        基本設定・外部ファイル読み込み
        - 文字コード / レスポンシブ設定
        - キャッシュ無効化
        - 共通CSS
        - Tailwind CSS
        - Ionicons
    ========================================================= --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>予約一覧 - Raqoo</title>

    {{-- 共通CSS --}}
    <link rel="stylesheet" href="{{ asset('user/home.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('user/home.mobile.css') }}?v={{ time() }}">

    {{-- ユーティリティCSS --}}
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

    {{-- アイコンライブラリ --}}
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons/ionicons.js"></script>
</head>

<body>
    {{-- =========================================================
        全体レイアウト
        - 左側にサイドバー
        - 右側に予約一覧
    ========================================================= --}}
    <div class="layout">
        {{-- 共通サイドバー --}}
        <x-user.sidebar />

        {{-- =====================================================
            メインコンテンツエリア
        ===================================================== --}}
        <div class="content-area">

            {{-- -------------------------------------------------
                ページ見出し
                - お知らせ一覧・利用履歴とタイトルデザインを統一
            ------------------------------------------------- --}}
            <div class="page-title-area">
                <h2 class="page-title">予約一覧（未来分）</h2>
            </div>

            {{-- -------------------------------------------------
                予約一覧
                - 未来分の予約がある場合はカード形式で表示
                - 予約がない場合は空状態メッセージを表示
            ------------------------------------------------- --}}
            @if ($reservations->count() > 0)
                <div class="space-y-4">
                    @foreach ($reservations as $reservation)
                        {{-- 予約詳細画面へのリンクカード --}}
                        <a
                            href="{{ route('user.reservations.show', $reservation->id) }}"
                            class="block bg-white p-4 rounded-lg shadow hover:shadow-md transition duration-200 cursor-pointer text-left"
                        >
                            <div class="flex justify-between items-start">
                                <div class="flex-1">

                                    {{-- =========================================
                                        カード上部
                                        - 教室名
                                        - 予約ステータス
                                    ========================================= --}}
                                    <div class="flex items-center space-x-4 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-800">
                                            {{ optional($reservation->room)->name ?? '教室情報なし' }}
                                        </h3>

                                        <span
                                            class="px-2 py-1 text-xs rounded-full
                                                @if ($reservation->status === 'pending') bg-yellow-100 text-yellow-800
                                                @elseif ($reservation->status === 'reserved') bg-green-100 text-green-800
                                                @elseif ($reservation->status === 'rejected') bg-red-100 text-red-800
                                                @elseif ($reservation->status === 'cancelled') bg-gray-100 text-gray-800
                                                @else bg-gray-100 text-gray-800
                                                @endif"
                                        >
                                            @if ($reservation->status === 'pending')
                                                承認待ち
                                            @elseif ($reservation->status === 'reserved')
                                                予約済み
                                            @elseif ($reservation->status === 'rejected')
                                                却下
                                            @elseif ($reservation->status === 'cancelled')
                                                キャンセル
                                            @else
                                                {{ $reservation->status }}
                                            @endif
                                        </span>
                                    </div>

                                    {{-- =========================================
                                        予約概要
                                        - 日付
                                        - 時限
                                        - 利用目的
                                        - 申請日
                                    ========================================= --}}
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-600">日付:</span>

                                            <span class="font-medium">
                                                {{ \Carbon\Carbon::parse($reservation->date)->format('m/d') }}({{ \Carbon\Carbon::parse($reservation->date)->locale('ja')->dayName }})
                                            </span>
                                        </div>

                                        <div>
                                            <span class="text-gray-600">時限:</span>

                                            <span class="font-medium">
                                                {{ $reservation->period }}限
                                            </span>
                                        </div>

                                        <div>
                                            <span class="text-gray-600">利用目的:</span>

                                            <span class="font-medium">
                                                {{ \Illuminate\Support\Str::limit($reservation->purpose, 20) }}
                                            </span>
                                        </div>

                                        <div>
                                            <span class="text-gray-600">申請日:</span>

                                            <span class="font-medium">
                                                {{ $reservation->created_at->format('m/d') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- 詳細画面へ進むことを示す矢印アイコン --}}
                                <div class="ml-4">
                                    <ion-icon name="chevron-forward-outline" class="text-gray-400"></ion-icon>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                {{-- =================================================
                    空状態表示
                    - 未来分の予約が存在しない場合
                ================================================= --}}
                <div class="text-center py-12">
                    <ion-icon name="calendar-outline" class="text-6xl text-gray-300 mb-4"></ion-icon>

                    <h3 class="text-lg font-medium text-gray-600 mb-2">
                        予約がありません
                    </h3>

                    <p class="text-gray-500">
                        新しい予約を作成してみましょう
                    </p>

                    <a
                        href="{{ route('user.home.index') }}"
                        class="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200"
                    >
                        教室を予約する
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- 共通JS：サイドバー・スマホメニュー操作用 --}}
    <script src="{{ asset('user/home.js') }}?v={{ time() }}"></script>
</body>

</html>