<!DOCTYPE html>
<html lang="ja">

<head>
    {{-- =========================================================
        基本設定・外部ファイル読み込み

        読み込み順：
        1. 共通CSS
        2. Tailwind CSS
        3. 予約詳細画面専用CSS

        理由：
        - ホーム画面とサイドバーの見た目を揃える
        - reservation-detail.css を最後に読み込み、予約詳細画面だけの補正を効かせる
    ========================================================= --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>予約詳細 - Raqoo</title>

    {{-- 共通CSS --}}
    <link rel="stylesheet" href="{{ asset('user/home.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('user/home.mobile.css') }}?v={{ time() }}">

    {{-- ユーティリティCSS --}}
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

    {{-- 予約詳細画面専用CSS --}}
    <link rel="stylesheet" href="{{ asset('user/reservation-detail.css') }}?v={{ time() }}">

    {{-- アイコンライブラリ --}}
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons/ionicons.js"></script>

    {{-- Ajax通信などでCSRFトークンを参照する場合に使用 --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="reservation-detail-page">
    {{-- =========================================================
        全体レイアウト
        - 左側に共通サイドバー
        - 右側に予約詳細画面
    ========================================================= --}}
    <div class="layout">
        {{-- 共通サイドバー --}}
        <x-user.sidebar />

        {{-- スマホ用バックドロップ --}}
        <div class="m-backdrop" id="navBackdrop" aria-hidden="true"></div>

        {{-- =====================================================
            メインコンテンツエリア
        ===================================================== --}}
        <div class="content-area">

            {{-- -------------------------------------------------
                予約詳細エリア
            ------------------------------------------------- --}}
            <div class="details">
                <div class="recentOrders">

                    {{-- ページ見出し・戻るボタン --}}
                    <div class="cardHeader">
                        <h2>予約詳細</h2>

                        <a
                            href="{{ route('user.reservations.index') }}"
                            class="px-4 py-2 text-sm bg-gray-500 text-white rounded hover:bg-gray-600 transition duration-200">
                            一覧に戻る
                        </a>
                    </div>

                    {{-- -------------------------------------------------
                        詳細カード
                        - ステータス
                        - 予約情報
                        - 利用目的
                        - 代表者
                        - 利用メンバー
                        - 申請日時
                        - キャンセルボタン
                    ------------------------------------------------- --}}
                    <div class="bg-white p-6 rounded-lg shadow-lg">

                        {{-- =================================================
                            予約ステータス表示
                            - DB enum に合わせて status を判定する
                            - pending / reserved / in_use / cancelled / finished
                        ================================================= --}}
                        <div class="mb-6 text-center">
                            <span
                                class="inline-block px-4 py-2 text-lg font-semibold rounded-lg
                                    @if ($reservation->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif ($reservation->status === 'reserved') bg-green-100 text-green-800
                                    @elseif ($reservation->status === 'in_use') bg-blue-100 text-blue-800
                                    @elseif ($reservation->status === 'cancelled') bg-gray-100 text-gray-800
                                    @elseif ($reservation->status === 'finished') bg-indigo-100 text-indigo-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                @if ($reservation->status === 'pending')
                                承認待ち
                                @elseif ($reservation->status === 'reserved')
                                予約済み
                                @elseif ($reservation->status === 'in_use')
                                利用中
                                @elseif ($reservation->status === 'cancelled')
                                キャンセル済み
                                @elseif ($reservation->status === 'finished')
                                利用完了
                                @else
                                {{ $reservation->status }}
                                @endif
                            </span>
                        </div>

                        {{-- =================================================
                            予約情報
                            - 教室 / 階層 / 日付 / 時限 / 収容人数 / 設備
                        ================================================= --}}
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold mb-4">予約情報</h3>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-gray-600">教室:</span>
                                        <span class="font-semibold">
                                            {{ optional($reservation->room)->name ?? '教室情報なし' }}
                                        </span>
                                    </div>

                                    <div>
                                        <span class="text-gray-600">階層:</span>
                                        <span class="font-semibold">
                                            {{ optional($reservation->room)->floor ?? '-' }}階
                                        </span>
                                    </div>

                                    <div>
                                        <span class="text-gray-600">日付:</span>
                                        <span class="font-semibold">
                                            {{ \Carbon\Carbon::parse($reservation->date)->format('Y年m月d日') }}({{ \Carbon\Carbon::parse($reservation->date)->locale('ja')->dayName }})
                                        </span>
                                    </div>

                                    <div>
                                        <span class="text-gray-600">時限:</span>
                                        <span class="font-semibold">
                                            {{ $reservation->period }}限
                                        </span>
                                    </div>

                                    <div>
                                        <span class="text-gray-600">収容人数:</span>
                                        <span class="font-semibold">
                                            {{ optional($reservation->room)->capacity ?? '-' }}名
                                        </span>
                                    </div>

                                    <div>
                                        <span class="text-gray-600">設備:</span>
                                        <span class="font-semibold">
                                            {{ optional($reservation->room)->equipment ?? '-' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- =================================================
                            利用目的
                        ================================================= --}}
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold mb-2">利用目的</h3>

                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p>{{ $reservation->purpose }}</p>
                            </div>
                        </div>

                        {{-- =================================================
                            代表者情報
                        ================================================= --}}
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold mb-2">代表者</h3>

                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p>
                                    {{ optional($reservation->user)->name ?? 'ユーザー情報なし' }}
                                    ({{ optional($reservation->user)->student_id ?? '-' }})
                                </p>
                            </div>
                        </div>

                        {{-- =================================================
                            利用メンバー
                            - 入力がある場合のみ表示
                            - 改行を保持するため pre を使用
                        ================================================= --}}
                        @if ($reservation->members)
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold mb-2">利用メンバー</h3>

                            <div class="p-4 bg-gray-50 rounded-lg">
                                <pre class="whitespace-pre-wrap">{{ $reservation->members }}</pre>
                            </div>
                        </div>
                        @endif

                        {{-- =================================================
                            申請日時
                        ================================================= --}}
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold mb-2">申請日時</h3>

                            <div class="p-4 bg-gray-50 rounded-lg">
                                <p>{{ $reservation->created_at->format('Y年m月d日 H:i') }}</p>
                            </div>
                        </div>

                        {{-- =================================================
                            予約キャンセルボタン
                            - pending / reserved の予約のみキャンセル可能
                            - 押下時は確認モーダルを表示
                        ================================================= --}}
                        <div class="flex justify-center space-x-4">
                            @if (in_array($reservation->status, ['pending', 'reserved'], true))
                            <button
                                type="button"
                                id="openCancelModal"
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
    </div>

    {{-- =========================================================
        キャンセル確認モーダル
        - キャンセルボタン押下時に表示
        - 確定すると PATCH でキャンセル処理を実行
    ========================================================= --}}
    <div
        id="cancelModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50"
        role="dialog"
        aria-modal="true"
        aria-hidden="true"
        aria-labelledby="cancelModalTitle">
        <div class="bg-white p-6 rounded-lg max-w-md mx-4">
            <h3 id="cancelModalTitle" class="text-lg font-semibold mb-4">
                予約キャンセルの確認
            </h3>

            <p class="text-gray-600 mb-6">
                本当にこの予約をキャンセルしますか？<br>
                この操作は取り消すことができません。
            </p>

            <div class="flex justify-end space-x-3">
                <button
                    type="button"
                    id="closeCancelModal"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition duration-200">
                    戻る
                </button>

                <form action="{{ route('user.reservations.cancel', $reservation->id) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')

                    <button
                        type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition duration-200">
                        予約をキャンセル
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- 共通サイドバー開閉用JavaScript --}}
    <script src="{{ asset('user/home.js') }}?v={{ time() }}"></script>

    {{-- 予約詳細画面専用JavaScript --}}
    <script src="{{ asset('user/reservation-detail.js') }}?v={{ time() }}"></script>
</body>

</html>