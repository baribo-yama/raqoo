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

                    <form action="{{ route('user.reservations.check') }}" method="POST">
                        @csrf
                        <input type="hidden" name="room_id" value="{{ $room->id }}">
                        <input type="hidden" name="date" value="{{ $date }}">

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
                                    {{-- ▼▼▼ 修正：ここに元の時限を大きく表示 ▼▼▼ --}}
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
                                    {{ $period }}限に加えて、前後のコマも予約する場合は選択してください。
                                </p>
                                <div class="flex flex-wrap gap-4">
                                    @for($i = 1; $i <= 6; $i++)
                                        @php
                                            // 元の時限かどうか
                                            $isOriginal = ($i == $period);
                                            
                                            // チェック状態の判定
                                            $isChecked = false;
                                            if ($isOriginal) {
                                                $isChecked = true;
                                            } elseif (old('periods')) {
                                                // バリデーションエラーなどで戻ってきた場合
                                                $isChecked = in_array($i, old('periods'));
                                            }
                                        @endphp

                                        <label class="flex items-center space-x-2 p-3 rounded border transition-colors duration-200 
                                            {{ $isOriginal ? 'bg-blue-100 border-blue-500 cursor-not-allowed' : 'hover:bg-gray-50 border-gray-200 cursor-pointer' }}
                                            {{ $isChecked && !$isOriginal ? 'bg-blue-50 border-blue-400' : '' }}">
                                            
                                            <input type="checkbox" name="periods[]" value="{{ $i }}" 
                                                class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-blue-500"
                                                {{ $isChecked ? 'checked' : '' }}
                                                {{-- 元の時限は操作不可にする --}}
                                                {{ $isOriginal ? 'disabled' : '' }}>
                                            
                                            <span class="font-medium {{ $isOriginal ? 'text-blue-800' : 'text-gray-900' }}">
                                                {{ $i }}限
                                                @if($isOriginal)
                                                    <span class="text-xs ml-1 font-normal text-blue-600">(選択中)</span>
                                                @endif
                                            </span>
                                        </label>

                                        {{-- disabledにしたチェックボックスは値が送信されないため、hiddenで送る --}}
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
                                    {{ Auth::user()->name }} ({{ Auth::user()->student_id }})
                                @else
                                    <span class="text-red-500">ログインが必要です</span>
                                @endauth
                            </div>
                        </div>

                        <div class="mb-6">
                            <label for="members" class="block text-sm font-medium text-gray-700 mb-2">
                                利用メンバー（代表者以外、最大30名）
                            </label>
                            <textarea id="members" name="members" rows="6"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="メンバーの氏名と学籍番号を入力してください&#10;例：&#10;山田太郎 (S12345)&#10;佐藤花子 (S67890)">{{ old('members') }}</textarea>
                            <div class="text-sm text-gray-500 mt-1">
                                1行につき1名ずつ入力してください
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

    <script>
        // 文字数カウント
        const purposeTextarea = document.getElementById('purpose');
        const purposeCount = document.getElementById('purposeCount');

        if(purposeTextarea){
            purposeTextarea.addEventListener('input', function() {
                purposeCount.textContent = this.value.length;
            });
            purposeCount.textContent = purposeTextarea.value.length;
        }

        // チェックボックスのデザイン連動（元の時限以外）
        const checkboxes = document.querySelectorAll('input[type="checkbox"][name="periods[]"]:not(:disabled)');
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                const label = this.closest('label');
                if(this.checked) {
                    label.classList.add('bg-blue-50', 'border-blue-400');
                    label.classList.remove('border-gray-200');
                } else {
                    label.classList.remove('bg-blue-50', 'border-blue-400');
                    label.classList.add('border-gray-200');
                }
            });
        });

        // ナビゲーション切り替え
        let list = document.querySelectorAll('.list');
        function activeLink() {
            list.forEach((item) => item.classList.remove('active'));
            this.classList.add('active');
        }
        list.forEach((item) => item.addEventListener('click', activeLink));

        let toggle = document.querySelector('.nav-toggle');
        let navigation = document.querySelector('.navigation');
        if(toggle){
            toggle.onclick = function() {
                navigation.classList.toggle('active');
            }
        }
    </script>
</body>
</html>