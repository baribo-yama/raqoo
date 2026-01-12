<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>お知らせ - Raqoo</title>
    <link rel="stylesheet" href="{{ asset('user/home.css') }}">
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body>
    <div class="main-content">
        {{-- ナビゲーション --}}
        <x-user.sidebar></x-user.sidebar>

        {{-- 右：メインコンテンツ --}}
        <div class="main-content">

            <!-- 検索バー -->
            <div class="content-header">
                <div class="search-wrapper">
                    <input type="text" placeholder="検索..." class="search-input">
                    <ion-icon name="search-outline"></ion-icon>
                </div>
            </div>

        <!-- お知らせ一覧 -->
        <div class="details">
            <div class="recentOrders">
                <div class="cardHeader">
                    <h2>お知らせ</h2>
                </div>

                @if (isset($notifications) && $notifications->count() > 0)
                    <div class="space-y-4">
                        @foreach ($notifications as $notification)
                            <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition duration-200">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <span class="inline-block w-3 h-3 rounded-full bg-blue-500"></span>
                                            <span class="text-xs text-gray-599">
                                                {{ $notification->created_at->format('Y/m/d H:i') }}
                                            </span>

                                        </div>
                                        <p class="text-gray-600 mb-2">{{ $notification->message }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- ページネーション --}}
                    @if ($notifications->hasPages())
                        <div class="mt-6">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-8 text-gray-500">
                        <p>通知はありません</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

        </div> {{-- /.main-content --}}
    </div>
</body>

</html>
