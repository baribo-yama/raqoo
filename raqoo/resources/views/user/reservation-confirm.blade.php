<!DOCTYPE html>
<html lang="ja">

<head>
    {{-- =========================================================
        基本設定・外部ファイル読み込み
        - 文字コード / レスポンシブ設定
        - 共通CSS
        - スマホ用共通CSS
        - Tailwind CSS
        - Ionicons
        - CSRFトークン
    ========================================================= --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約申請確認 - Raqoo</title>

    {{-- 共通CSS --}}
    <link rel="stylesheet" href="{{ asset('user/home.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('user/home.mobile.css') }}?v={{ time() }}">

    {{-- ユーティリティCSS --}}
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

    {{-- アイコンライブラリ --}}
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons/ionicons.js"></script>

    {{-- Ajax通信などでCSRFトークンを参照する場合に使用 --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body class="reservation-check-page">
    {{-- =========================================================
        全体レイアウト
        - 左側に共通サイドバー
        - 右側に予約申請確認画面
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
            <div class="recentOrders">

                {{-- -------------------------------------------------
                    ページ見出し
                ------------------------------------------------- --}}
                <div class="cardHeader">
                    <h2>予約申請確認</h2>
                </div>

                {{-- -------------------------------------------------
                    確認カード
                    - 予約情報
                    - 利用目的
                    - 代表者
                    - 利用メンバー
                    - 修正 / 申請確定ボタン
                ------------------------------------------------- --}}
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <div class="mb-6">

                        {{-- 確認メッセージ --}}
                        <h3 class="text-xl font-semibold mb-4 text-gray-800">
                            申請内容をご確認ください
                        </h3>

                        {{-- =================================================
                            予約情報
                            - 教室名
                            - 階層
                            - 日付
                            - 時限
                            - 収容人数
                            - 設備
                        ================================================= --}}
                        <div class="bg-gray-50 p-4 rounded-lg mb-6 border border-gray-100">
                            <h4 class="font-semibold text-lg mb-3 text-gray-700">
                                予約情報
                            </h4>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-gray-600">教室:</span>
                                    <span class="font-bold text-gray-800 ml-2">
                                        {{ $selectedRoom->name }}
                                    </span>
                                </div>

                                <div>
                                    <span class="text-gray-600">階層:</span>
                                    <span class="font-bold text-gray-800 ml-2">
                                        {{ $selectedRoom->floor }}階
                                    </span>
                                </div>

                                <div>
                                    <span class="text-gray-600">日付:</span>
                                    <span class="font-bold text-gray-800 ml-2">
                                        {{ $reservationData['date'] }}
                                    </span>
                                </div>

                                <div>
                                    <span class="text-gray-600">時限:</span>
                                    <span class="font-bold text-blue-600 ml-2">
                                        @foreach ($reservationData['periods'] as $period)
                                        {{ $period }}限{{ !$loop->last ? ', ' : '' }}
                                        @endforeach
                                    </span>
                                </div>

                                <div>
                                    <span class="text-gray-600">収容人数:</span>
                                    <span class="font-bold text-gray-800 ml-2">
                                        {{ $selectedRoom->capacity }}名
                                    </span>
                                </div>

                                <div>
                                    <span class="text-gray-600">設備:</span>
                                    <span class="font-bold text-gray-800 ml-2">
                                        {{ $selectedRoom->equipment }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- =================================================
                            利用目的
                        ================================================= --}}
                        <div class="mb-6">
                            <h4 class="font-semibold text-lg mb-2 text-gray-700">
                                利用目的
                            </h4>

                            <div class="p-3 bg-gray-50 rounded border">
                                {{ $reservationData['purpose'] }}
                            </div>
                        </div>

                        {{-- =================================================
                            代表者
                            - 現在ログインしているユーザーを表示
                        ================================================= --}}
                        <div class="mb-6">
                            <h4 class="font-semibold text-lg mb-2 text-gray-700">
                                代表者
                            </h4>

                            <div class="p-3 bg-gray-50 rounded border">
                                {{ Auth::user()->name }} ({{ Auth::user()->student_id }})
                            </div>
                        </div>

                        {{-- =================================================
                            利用メンバー
                            - 入力がある場合のみ表示
                            - 改行を保持するため pre を使用
                        ================================================= --}}
                        @if (!empty($reservationData['members']))
                        <div class="mb-6">
                            <h4 class="font-semibold text-lg mb-2 text-gray-700">
                                利用メンバー
                            </h4>

                            <div class="p-3 bg-gray-50 rounded border">
                                <pre class="whitespace-pre-wrap font-sans">{{ $reservationData['members'] }}</pre>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- -------------------------------------------------
                        下部アクション
                        - 修正する：入力画面へ戻る
                        - 申請を確定する：予約申請を登録する
                    ------------------------------------------------- --}}
                    <div class="flex justify-between items-center pt-4 border-t">

                        {{-- =================================================
                            修正フォーム
                            - GETで予約入力画面へ戻る
                            - 入力済みの room_id / date / periods をhiddenで保持
                        ================================================= --}}
                        <form action="{{ route('user.reservations.create') }}" method="GET">
                            <input type="hidden" name="room_id" value="{{ $reservationData['room_id'] }}">
                            <input type="hidden" name="date" value="{{ $reservationData['date'] }}">

                            {{-- 選択済み時限を配列として保持して戻す --}}
                            @foreach ($reservationData['periods'] as $period)
                            <input type="hidden" name="periods[]" value="{{ $period }}">
                            @endforeach

                            <button
                                type="submit"
                                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition duration-200">
                                修正する
                            </button>
                        </form>

                        {{-- =================================================
                            申請確定フォーム
                            - POSTで予約申請を登録
                            - 確認画面で表示した内容をhiddenで送信
                        ================================================= --}}
                        <form action="{{ route('user.reservations.store') }}" method="POST">
                            @csrf

                            <input type="hidden" name="room_id" value="{{ $reservationData['room_id'] }}">
                            <input type="hidden" name="date" value="{{ $reservationData['date'] }}">
                            <input type="hidden" name="purpose" value="{{ $reservationData['purpose'] }}">
                            <input type="hidden" name="members" value="{{ $reservationData['members'] }}">

                            {{-- 選択済み時限を配列として送信 --}}
                            @foreach ($reservationData['periods'] as $period)
                            <input type="hidden" name="periods[]" value="{{ $period }}">
                            @endforeach

                            <button
                                type="submit"
                                class="px-8 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 shadow-lg transition duration-200 font-bold flex items-center">
                                <ion-icon name="paper-plane-outline" class="mr-2 text-xl"></ion-icon>
                                申請を確定する
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 共通サイドバー開閉用JavaScript --}}
    <script src="{{ asset('user/home.js') }}?v={{ time() }}"></script>
</body>

</html>