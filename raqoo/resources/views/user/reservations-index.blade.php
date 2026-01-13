<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>予約一覧 - Raqoo</title>
    <link rel="stylesheet" href="{{ asset('user/home.css') }}?v={{ time() }}">
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body>
    <div class="layout">
        <!-- ナビゲーション -->
        <x-user.sidebar />

        <!-- メインコンテンツ -->
        <div class="content-area">
                <div class="cardHeader">
                    <h2>予約一覧（未来分）</h2>
                </div>

                @if ($reservations->count() > 0)
                    <div class="space-y-4">
                        @foreach ($reservations as $reservation)
                            <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition duration-200 cursor-pointer"
                                onclick="location.href='{{ route('user.reservations.show', $reservation->id) }}'">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-4 mb-2">
                                            <h3 class="text-lg font-semibold text-gray-800">
                                                {{ $reservation->room->name }}</h3>
                                            <span
                                                class="px-2 py-1 text-xs rounded-full
                                        @if ($reservation->status === 'pending') bg-yellow-100 text-yellow-800
                                        @elseif($reservation->status === 'approved') bg-green-100 text-green-800
                                        @elseif($reservation->status === 'rejected') bg-red-100 text-red-800
                                        @elseif($reservation->status === 'cancelled') bg-gray-100 text-gray-800 @endif">
                                                @if ($reservation->status === 'pending')
                                                    承認待ち
                                                @elseif($reservation->status === 'approved')
                                                    承認済み
                                                @elseif($reservation->status === 'rejected')
                                                    却下
                                                @elseif($reservation->status === 'cancelled')
                                                    キャンセル
                                                @endif
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-600">日付:</span>
                                                <span
                                                    class="font-medium">{{ \Carbon\Carbon::parse($reservation->date)->format('m/d') }}({{ \Carbon\Carbon::parse($reservation->date)->locale('ja')->dayName }})</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-600">時限:</span>
                                                <span class="font-medium">{{ $reservation->period }}限</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-600">利用目的:</span>
                                                <span
                                                    class="font-medium">{{ \Illuminate\Support\Str::limit($reservation->purpose, 20) }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-600">申請日:</span>
                                                <span
                                                    class="font-medium">{{ $reservation->created_at->format('m/d') }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="ml-4">
                                        <ion-icon name="chevron-forward-outline" class="text-gray-400"></ion-icon>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <ion-icon name="calendar-outline" class="text-6xl text-gray-300 mb-4"></ion-icon>
                        <h3 class="text-lg font-medium text-gray-600 mb-2">予約がありません</h3>
                        <p class="text-gray-500">新しい予約を作成してみましょう</p>
                        <a href="{{ route('user.home.index') }}"
                            class="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">
                            教室を予約する
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
                </div>
            </div>
        </div>
    </div> <!-- /.layout -->

    <script>
        // ナビゲーション切り替え
        let list = document.querySelectorAll('.list');

        function activeLink() {
            list.forEach((item) => item.classList.remove('active'));
            this.classList.add('active');
        }
        list.forEach((item) => item.addEventListener('click', activeLink));

        // トグルメニュー
        let toggle = document.querySelector('.nav-toggle');
        let navigation = document.querySelector('.navigation');
        toggle.onclick = function() {
            navigation.classList.toggle('active');
        }
    </script>
</body>

</html>

</html>
