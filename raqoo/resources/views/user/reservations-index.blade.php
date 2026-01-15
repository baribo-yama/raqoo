<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約一覧 - Raqoo</title>
    
    {{-- ▼▼▼ ホームと同じCSSを読み込む（これでデザイン統一） ▼▼▼ --}}
    <link rel="stylesheet" href="{{ asset('user/home.css') }}?v=999">
    <link rel="stylesheet" href="{{ asset('user/home.mobile.css') }}?v=999">
    
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

    <style>
        /* 予約一覧専用の微調整 */
        .search-area {
            background: #fff;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        /* カードのデザイン */
        .reservation-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
            transition: transform 0.2s;
        }
        .reservation-card:active {
            transform: scale(0.98);
        }
        
        /* スマホ時の調整 */
        @media (max-width: 560px) {
            .search-area {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }
            .header-title {
                /* ハンバーガーボタンと被らないように左に余白 */
                margin-right: 40px; 
            }
        }
    </style>
</head>

<body>
    {{-- ▼▼▼ 修正箇所：クラス名を m-backdrop に変更しました ▼▼▼ --}}
    <div id="mobileOverlay" class="m-backdrop"></div>

    <div class="layout">
        
        {{-- ▼▼▼ サイドバー：ホームと同じコンポーネントを使用 ▼▼▼ --}}
        <x-user.sidebar />

        {{-- 右：メインコンテンツエリア（home.cssのスタイルが適用されます） --}}
        <div class="content-area">

            {{-- ▼▼▼ ヘッダー（ホームと同じ構造 + 予約一覧タイトル） ▼▼▼ --}}
            <div class="header" style="justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div class="logo-wrapper">
                    {{-- ページタイトルとしてロゴまたはテキストを表示 --}}
                    <h1 class="header-title" style="font-size: 24px; font-weight: bold; color: #1c2b4a;">予約一覧</h1>
                </div>
            </div>

            {{-- ▼▼▼ ここから予約一覧の中身 ▼▼▼ --}}
            
            {{-- 検索バーなど（必要であれば） --}}
            <div class="mb-6">
                <form action="" method="GET" class="relative">
                    <input type="text" placeholder="予約を検索..." class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:border-blue-500">
                    <div class="absolute top-2.5 left-3 text-gray-400">
                        <ion-icon name="search-outline"></ion-icon>
                    </div>
                </form>
            </div>

            {{-- 予約リスト --}}
            <div class="reservations-list">
                @if ($reservations->count() > 0)
                    @foreach ($reservations as $reservation)
                      <a href="{{ route('user.reservations.show', $reservation->id) }}" class="reservation-card block"></a>
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-3">
                                        <h3 class="text-lg font-bold text-gray-800">
                                            {{ $reservation->room->name }}
                                        </h3>
                                        <span class="px-3 py-1 text-xs rounded-full font-bold
                                            @if ($reservation->status === 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($reservation->status === 'approved') bg-green-100 text-green-800
                                            @elseif($reservation->status === 'rejected') bg-red-100 text-red-800
                                            @elseif($reservation->status === 'cancelled') bg-gray-100 text-gray-800 @endif">
                                            @if ($reservation->status === 'pending') 承認待ち
                                            @elseif($reservation->status === 'approved') 承認済み
                                            @elseif($reservation->status === 'rejected') 却下
                                            @elseif($reservation->status === 'cancelled') キャンセル
                                            @endif
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-600">
                                        <div class="flex items-center">
                                            <ion-icon name="calendar-outline" class="mr-2"></ion-icon>
                                            <span class="font-medium text-black">
                                                {{ \Carbon\Carbon::parse($reservation->date)->format('m/d') }}
                                                ({{ \Carbon\Carbon::parse($reservation->date)->locale('ja')->dayName }})
                                            </span>
                                        </div>
                                        <div class="flex items-center">
                                            <ion-icon name="time-outline" class="mr-2"></ion-icon>
                                            <span class="font-medium text-black">{{ $reservation->period }}限</span>
                                        </div>
                                        <div class="flex items-center col-span-1 md:col-span-2 mt-1">
                                            <ion-icon name="document-text-outline" class="mr-2"></ion-icon>
                                            <span class="truncate">
                                                {{ \Illuminate\Support\Str::limit($reservation->purpose, 30) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="ml-3 flex items-center justify-center self-center">
                                    <ion-icon name="chevron-forward-outline" class="text-gray-400 text-xl"></ion-icon>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-12 bg-white rounded-xl shadow-sm border border-gray-100">
                        <ion-icon name="calendar-outline" class="text-6xl text-gray-300 mb-4"></ion-icon>
                        <h3 class="text-lg font-medium text-gray-600 mb-2">予約がありません</h3>
                        <p class="text-gray-500 mb-6">新しい予約を作成してみましょう</p>
                        <a href="{{ route('user.home.index') }}"
                            class="inline-block px-6 py-3 bg-blue-900 text-white rounded-lg hover:bg-blue-800 transition duration-200 font-bold shadow-md">
                            教室を予約する
                        </a>
                    </div>
                @endif
            </div>

        </div> {{-- /.content-area --}}
    </div> {{-- /.layout --}}

    {{-- ▼▼▼ ホームと同じJSを読み込む（これでハンバーガーメニューが動く） ▼▼▼ --}}
    <script src="{{ asset('user/home.js') }}"></script>
</body>

</html>