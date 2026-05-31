<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約申請フォーム - Raqoo</title>
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
                    <h2>予約申請フォーム</h2>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-lg">
                    @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="text-red-800 font-semibold mb-2">⚠️ エラーが発生しました</div>
                        @foreach ($errors->all() as $error)
                            <p class="text-red-700">• {{ $error }}</p>
                        @endforeach
                    </div>
                    @endif

                    @if (session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-green-800">✅ {{ session('success') }}</p>
                    </div>
                    @endif

                    <form action="{{ route('user.reservations.check') }}" method="POST" id="reservationForm">
                        @csrf
                        <input type="hidden" name="room_id" value="{{ $room->id }}">
                        <input type="hidden" name="date" value="{{ $date }}">

                        <div id="selectingMeta"
                            data-room-id="{{ $room->id }}"
                            data-date="{{ $date }}"
                            data-toggle-url="{{ route('user.reservations.selecting.toggle') }}"
                        ></div>

                        <div class="mb-6 p-4 bg-gray-100 rounded-lg border border-gray-200">
                            <h3 class="text-lg font-bold mb-3 text-gray-800 border-b pb-2">基本情報</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="text-gray-500 text-sm">教室</span>
                                    <div class="font-bold text-lg text-gray-800">{{ $room->name }}</div>
                                </div>
                                <div>
                                    <span class="text-gray-500 text-sm">日付</span>
                                    <div class="font-bold text-lg text-gray-800">{{ $date }}</div>
                                </div>
                                <div>
                                    <span class="text-gray-500 text-sm">選択中の時限</span>
                                    <div class="font-bold text-lg text-blue-600">
                                        {{ $period }}限
                                    </div>
                                </div>
                                <div>
                                    <span class="text-gray-500 text-sm">設備</span>
                                    <div class="font-medium text-gray-800">{{ $room->equipment }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-bold mb-2">
                                連続して予約する（オプション）
                            </label>
                            <div class="p-4 border rounded-md bg-white">
                                <p class="text-sm text-gray-600 mb-3">
                                    <span id="instructionText" class="font-bold text-orange-600">
                                        ※1名利用（メンバー未入力）の場合は連続予約できません。
                                    </span>
                                    <br>
                                    <span class="text-xs text-gray-500">
                                        ※2名以上での利用は最大2コマまで、かつ連続した時限のみ予約可能です。
                                    </span>
                                </p>
                                <div class="flex flex-wrap gap-4">
                                    @for($i = 1; $i <= 6; $i++)
                                        @php
                                            $isOriginal = ($i == $period);
                                            $isReservedOnly = in_array($i, $reservedPeriods) && !$isOriginal;
                                            $isLesson = in_array($i, $lessonPeriods) && !$isOriginal;
                                            $isDisabledSlot = ($isReservedOnly || $isLesson);
                                        @endphp

                                        <label class="period-label flex items-center space-x-2 p-3 rounded border transition-colors duration-200 
                                            {{ $isOriginal ? 'bg-blue-100 border-blue-500 cursor-not-allowed' : 'border-gray-200' }}
                                            {{ $isDisabledSlot ? 'bg-gray-100 opacity-50 cursor-not-allowed' : 'cursor-pointer hover:bg-gray-50' }}"
                                            id="label-{{ $i }}">
                                            
                                            <input type="checkbox" name="periods[]" value="{{ $i }}" 
                                                class="period-checkbox form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-blue-500"
                                                data-period="{{ $i }}"
                                                data-reserved="{{ $isDisabledSlot ? 1 : 0 }}"
                                                data-original="{{ $isOriginal ? 1 : 0 }}"
                                                {{ $isOriginal ? 'checked disabled' : '' }}
                                                {{ $isDisabledSlot ? 'disabled' : '' }}>
                                            
                                            <span class="font-medium {{ $isDisabledSlot ? 'text-gray-400' : 'text-gray-900' }} {{ $isOriginal ? 'text-blue-800' : '' }}">
                                                <span class="{{ $isDisabledSlot ? 'line-through' : '' }}">{{ $i }}限</span>
                                                @if($isOriginal)
                                                    <span class="text-xs ml-1 font-normal text-blue-600 inline-block">(選択中)</span>
                                                @elseif($isLesson)
                                                    <span class="text-xs ml-1 text-red-500 font-bold inline-block">(授業)</span>
                                                @elseif($isReservedOnly)
                                                    <span class="text-xs ml-1 font-normal text-red-400 inline-block">(予約済)</span>
                                                @endif
                                            </span>
                                        </label>

                                        @if($isOriginal)
                                            <input type="hidden" name="periods[]" value="{{ $i }}">
                                        @endif
                                    @endfor
                                </div>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label for="purpose" class="block text-sm font-medium text-gray-700 mb-2">
                                利用目的 <span class="text-red-500">*</span>
                            </label>
                            <textarea id="purpose" name="purpose" rows="3" maxlength="100"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="利用目的を入力してください（例：ゼミ発表の練習）" required>{{ old('purpose') }}</textarea>
                            <div class="text-right text-sm text-gray-500 mt-1">
                                <span id="purposeCount">0</span>/100文字
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">代表者</label>
                            <div class="p-3 bg-gray-100 rounded-md text-gray-600">
                                @auth
                                    {{ Auth::user()->major }}　{{ Auth::user()->name }} ({{ Auth::user()->student_id }})
                                @else
                                    <span class="text-red-500">ログインが必要です</span>
                                @endauth
                            </div>
                        </div>

                        <input type="hidden" id="members" value="">
                        <input type="hidden" name="members" id="membersHidden">

                        @php
                            $oldMembers = old('members') ? explode("\n", old('members')) : [];
                        @endphp

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                利用メンバー（代表者以外、最大30名）
                            </label>

                            <div class="border border-gray-300 rounded-md overflow-hidden">
                                <div class="grid grid-cols-12 bg-gray-100 border-b border-gray-300 px-3 py-2 text-sm font-bold text-gray-600">
                                    <div class="col-span-1 text-center">No.</div>
                                    <div class="col-span-11 pl-2">氏名</div>
                                </div>

                                <div class="divide-y divide-gray-200">
                                    @for ($i = 1; $i <= 30; $i++)
                                        <div class="grid grid-cols-12 items-center px-3 py-1">
                                            <div class="col-span-1 text-center text-sm text-gray-400 font-mono">{{ $i }}</div>
                                            <div class="col-span-11 pl-2">
                                                <input
                                                    type="text"
                                                    class="member-name-input w-full px-2 py-1 text-sm border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-blue-400 focus:border-blue-400"
                                                    placeholder="氏名を入力"
                                                    maxlength="50"
                                                    aria-label="利用メンバー {{ $i }}人目の氏名"
                                                    value="{{ $oldMembers[$i - 1] ?? '' }}"
                                                >
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>

                            <div class="text-sm text-gray-500 mt-1">
                                入力した分だけ送信されます（空欄はスキップ）
                            </div>
                        </div>

                        <div class="flex justify-between">
                            <a href="{{ route('user.home.index') }}"
                                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition duration-200">
                                キャンセル
                            </a>
                            <button type="submit"
                                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200 flex items-center shadow-lg">
                                確認画面へ
                                <ion-icon name="arrow-forward-outline" class="ml-2"></ion-icon>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ★ インラインJSを外部ファイルに移動 --}}
    <script src="{{ asset('user/reservation-form.js') }}?v={{ time() }}"></script>
</body>
</html>