<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>利用履歴 - Raqoo</title>

    {{-- 共通CSS（ホームと同じデザイン） --}}
    <link rel="stylesheet" href="{{ asset('user/home.css') }}">
    <link rel="stylesheet" href="{{ asset('user/home.mobile.css') }}">

    {{-- Tailwind --}}
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

    {{-- Ionicons --}}
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

    <style>
        /* ===========================
           Layout
           利用履歴ページ全体の余白と中央寄せ
           =========================== */
        .content-area {
            padding-top: 40px;
            padding-left: 40px;
            padding-right: 40px;
            box-sizing: border-box;
        }

        .history-container {
            max-width: 1100px;
            margin: 0 auto;
            width: 100%;
        }

        /* ===========================
           History Card
           =========================== */
        .history-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        /* ステータスラベル */
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: bold;
            white-space: nowrap;
        }

        .status-completed {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-cancelled {
            background-color: #f3f4f6;
            color: #374151;
        }

        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* ===========================
           Responsive
           =========================== */
        @media (max-width: 1024px) {
            .content-area {
                padding-top: 30px;
                padding-left: 20px;
                padding-right: 20px;
            }
        }

        @media (max-width: 560px) {
            .content-area {
                padding-top: 24px;
                padding-left: 16px;
                padding-right: 16px;
            }
        }
    </style>
</head>

<body>
    {{-- スマホ用オーバーレイ --}}
    <div id="mobileOverlay" class="mobile-overlay"></div>

    <div class="layout">

        {{-- サイドバー --}}
        <x-user.sidebar />

        {{-- 右：メインコンテンツエリア --}}
        <div class="content-area">
            <div class="history-container">

                {{-- タイトルエリア：お知らせページと同じ構造 --}}
                <div class="page-title-area">
                    <h2 class="page-title">利用履歴</h2>
                </div>

                {{-- 利用履歴リスト --}}
                <div class="history-list">
                    @if($historyReservations->count() > 0)
                        @foreach($historyReservations as $reservation)
                            <div class="history-card">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <h3 class="text-lg font-bold text-gray-800">
                                            {{ $reservation->room->name }}
                                        </h3>

                                        <span class="status-badge
                                            @if($reservation->status === 'completed') status-completed
                                            @elseif($reservation->status === 'cancelled') status-cancelled
                                            @elseif($reservation->status === 'rejected') status-rejected
                                            @endif">

                                            @if($reservation->status === 'completed')
                                                利用完了
                                            @elseif($reservation->status === 'cancelled')
                                                キャンセル
                                            @elseif($reservation->status === 'rejected')
                                                却下
                                            @endif
                                        </span>
                                    </div>
                                </div>

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
                                            {{ $reservation->member_count }}名
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <p class="text-xs text-gray-500 mb-1">利用目的</p>
                                    <p class="text-sm text-gray-800 bg-gray-50 p-2 rounded border border-gray-100">
                                        {{ $reservation->purpose }}
                                    </p>
                                </div>

                                {{-- メンバー表示 --}}
                                @if($reservation->members)
                                    <div class="mb-3">
                                        <p class="text-xs text-gray-500 mb-1">メンバー</p>

                                        <div class="text-sm text-gray-700 bg-gray-50 p-2 rounded border border-gray-100">
                                            <span class="font-medium">
                                                {{ Auth::user()->family_name }}{{ Auth::user()->first_name }} (代表)
                                            </span>

                                            @php
                                                $members = array_filter(explode("\n", $reservation->members));
                                            @endphp

                                            @if(count($members) > 0)
                                                @foreach($members as $member)
                                                    <span class="mx-1 text-gray-300">|</span>
                                                    {{ trim($member) }}
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- 却下理由 --}}
                                @if($reservation->status === 'rejected' && $reservation->rejection_reason)
                                    <div class="mt-3 p-3 bg-red-50 border border-red-100 rounded-lg">
                                        <p class="text-xs font-bold text-red-800 mb-1">却下理由</p>
                                        <p class="text-sm text-red-700">
                                            {{ $reservation->rejection_reason }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        {{-- ページネーション --}}
                        @if($historyReservations->hasPages())
                            <div class="mt-6">
                                {{ $historyReservations->links() }}
                            </div>
                        @endif

                    @else
                        <div class="text-center py-12 bg-white rounded-xl shadow-sm border border-gray-100">
                            <ion-icon name="document-text-outline" class="text-6xl text-gray-300 mb-4"></ion-icon>

                            <h3 class="text-lg font-medium text-gray-600 mb-2">
                                利用履歴がありません
                            </h3>

                            <p class="text-gray-500 mb-6">
                                過去の利用実績がここに表示されます
                            </p>

                            <a href="{{ route('user.home.index') }}"
                               class="inline-block px-6 py-3 bg-blue-900 text-white rounded-lg hover:bg-blue-800 transition duration-200 font-bold shadow-md">
                                教室を予約する
                            </a>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- 共通JS（ハンバーガーメニュー機能） --}}
    <script src="{{ asset('user/home.js') }}"></script>
</body>

</html>