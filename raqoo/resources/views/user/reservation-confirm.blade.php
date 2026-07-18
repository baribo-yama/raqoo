<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約申請確認 - Raqoo</title>
    <link rel="stylesheet" href="{{ asset('user/home.css') }}?v={{ time() }}">
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div class="layout">
        <x-user.sidebar />

        <div class="content-area">
            <div class="recentOrders">
                <div class="cardHeader">
                    <h2>予約申請確認</h2>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold mb-4 text-gray-800">申請内容をご確認ください</h3>

                        <div class="bg-gray-50 p-4 rounded-lg mb-6 border border-gray-100">
                            <h4 class="font-semibold text-lg mb-3 text-gray-700">予約情報</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-gray-600">教室:</span>
                                    <span class="font-bold text-gray-800 ml-2">{{ $selectedRoom->name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">階層:</span>
                                    <span class="font-bold text-gray-800 ml-2">{{ $selectedRoom->floor }}階</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">日付:</span>
                                    <span class="font-bold text-gray-800 ml-2">{{ $reservationData['date'] }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">時限:</span>
                                    <span class="font-bold text-blue-600 ml-2">
                                        {{-- ▼▼▼ 修正：複数の時限を表示 ▼▼▼ --}}
                                        @foreach($reservationData['periods'] as $period)
                                            {{ $period }}限{{ !$loop->last ? ', ' : '' }}
                                        @endforeach
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-600">収容人数:</span>
                                    <span class="font-bold text-gray-800 ml-2">{{ $selectedRoom->capacity }}名</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">設備:</span>
                                    <span class="font-bold text-gray-800 ml-2">{{ $selectedRoom->equipment }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <h4 class="font-semibold text-lg mb-2 text-gray-700">利用目的</h4>
                            <div class="p-3 bg-gray-50 rounded border">
                                {{ $reservationData['purpose'] }}
                            </div>
                        </div>

                        <div class="mb-6">
                            <h4 class="font-semibold text-lg mb-2 text-gray-700">代表者</h4>
                            <div class="p-3 bg-gray-50 rounded border">
                                {{ Auth::user()->name }} ({{ Auth::user()->student_id }})
                            </div>
                        </div>

                        @if(!empty($reservationData['members']))
                        <div class="mb-6">
                            <h4 class="font-semibold text-lg mb-2 text-gray-700">利用メンバー</h4>
                            <div class="p-3 bg-gray-50 rounded border">
                                <pre class="whitespace-pre-wrap font-sans">{{ $reservationData['members'] }}</pre>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t">
                        
                        {{-- ▼▼▼ 修正：戻るボタン（入力値を保持して戻る） ▼▼▼ --}}
                        <form action="{{ route('user.reservations.create') }}" method="GET">
                            <input type="hidden" name="period" value="{{ $reservationData['periods'][0] }}">
                            <input type="hidden" name="room_id" value="{{ $reservationData['room_id'] }}">
                            <input type="hidden" name="date" value="{{ $reservationData['date'] }}">
                            @foreach($reservationData['periods'] as $period)
                                <input type="hidden" name="periods[]" value="{{ $period }}">
                            @endforeach
                            
                            <button type="submit"
                                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition duration-200">
                                修正する
                            </button>
                        </form>

                        {{-- ▼▼▼ 修正：申請ボタン（配列データを送信） ▼▼▼ --}}
                        <form action="{{ route('user.reservations.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="room_id" value="{{ $reservationData['room_id'] }}">
                            <input type="hidden" name="date" value="{{ $reservationData['date'] }}">
                            <input type="hidden" name="purpose" value="{{ $reservationData['purpose'] }}">
                            <input type="hidden" name="members" value="{{ $reservationData['members'] }}">
                            
                            {{-- 時限（配列）を送る --}}
                            @foreach($reservationData['periods'] as $period)
                                <input type="hidden" name="periods[]" value="{{ $period }}">
                            @endforeach

                            <button type="submit"
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
</body>
</html>