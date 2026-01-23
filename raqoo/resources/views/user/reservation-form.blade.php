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
                                        ※2名以上での利用は最大2コマまで予約可能です。
                                    </span>
                                </p>
                                <div class="flex flex-wrap gap-4">
                                    @for($i = 1; $i <= 6; $i++)
                                        @php
                                            $isOriginal = ($i == $period);
                                            // すでに予約されているか判定（キャンセル以外）
                                            $isReserved = in_array($i, $reservedPeriods) && !$isOriginal;
                                        @endphp

                                        <label class="period-label flex items-center space-x-2 p-3 rounded border transition-colors duration-200 
                                            {{ $isOriginal ? 'bg-blue-100 border-blue-500 cursor-not-allowed' : 'border-gray-200' }}
                                            {{ $isReserved ? 'bg-gray-100 opacity-50 cursor-not-allowed' : 'cursor-pointer hover:bg-gray-50' }}"
                                            id="label-{{ $i }}">
                                            
                                            <input type="checkbox" name="periods[]" value="{{ $i }}" 
                                                class="period-checkbox form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-blue-500"
                                                data-period="{{ $i }}"
                                                data-reserved="{{ $isReserved ? 1 : 0 }}"
                                                data-original="{{ $isOriginal ? 1 : 0 }}"
                                                {{ $isOriginal ? 'checked disabled' : '' }}
                                                {{ $isReserved ? 'disabled' : '' }}>
                                            
                                            {{-- ▼▼▼ 修正：decoration-line-through を line-through に変更 ▼▼▼ --}}
                                            <span class="font-medium {{ $isReserved ? 'text-gray-400 line-through' : 'text-gray-900' }} {{ $isOriginal ? 'text-blue-800' : '' }}">
                                                {{ $i }}限
                                                @if($isOriginal)
                                                    <span class="text-xs ml-1 font-normal text-blue-600">(選択中)</span>
                                                @elseif($isReserved)
                                                    <span class="text-xs ml-1 font-normal text-red-400">(予約済)</span>
                                                @endif
                                            </span>
                                        </label>

                                        {{-- 元の時限はPOST値に含めるためにhiddenで送る --}}
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
                            {{-- JSで監視するためにID 'members' を使用 --}}
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
        document.addEventListener('DOMContentLoaded', function() {
            // 文字数カウント
            const purposeTextarea = document.getElementById('purpose');
            const purposeCount = document.getElementById('purposeCount');
            if(purposeTextarea){
                purposeTextarea.addEventListener('input', function() {
                    purposeCount.textContent = this.value.length;
                });
                purposeCount.textContent = purposeTextarea.value.length;
            }

            // 連続予約の制御ロジック
            const membersInput = document.getElementById('members');
            const checkboxes = document.querySelectorAll('.period-checkbox');
            const instructionText = document.getElementById('instructionText');
            
            function updateFormState() {
                const membersValue = membersInput.value.trim();
                const hasMembers = membersValue.length > 0; // メンバー入力があるか

                // メッセージの切り替え
                if (hasMembers) {
                    instructionText.textContent = "※最大2コマまで選択可能です。";
                    instructionText.classList.remove('text-orange-600');
                    instructionText.classList.add('text-blue-600');
                } else {
                    instructionText.textContent = "※1名利用（メンバー未入力）の場合は連続予約できません。";
                    instructionText.classList.add('text-orange-600');
                    instructionText.classList.remove('text-blue-600');
                }

                // 現在チェックされている追加のコマ数（メイン以外）を数える
                let extraCheckedCount = 0;
                checkboxes.forEach(cb => {
                    if (cb.dataset.original === "0" && cb.checked) {
                        extraCheckedCount++;
                    }
                });

                // 各チェックボックスの制御
                checkboxes.forEach(cb => {
                    const isOriginal = cb.dataset.original === "1";
                    const isReserved = cb.dataset.reserved === "1";
                    const label = document.getElementById('label-' + cb.value);

                    // 1. メインのコマは何もしない（常にdisabled checked）
                    if (isOriginal) return;

                    // 2. すでに予約済みのコマは常に無効
                    if (isReserved) {
                        cb.disabled = true;
                        // デザイン調整
                        label.classList.add('bg-gray-100', 'opacity-50', 'cursor-not-allowed');
                        return; 
                    }

                    // 3. メンバー入力がない場合（1人利用）
                    if (!hasMembers) {
                        cb.disabled = true;   // 無効化
                        cb.checked = false;   // チェックも外す
                        label.classList.remove('bg-blue-50', 'border-blue-400');
                        label.classList.add('border-gray-200');
                        return;
                    }

                    // 4. メンバー入力がある場合（2人以上利用）
                    // -> 追加で1つ選んだら（合計2コマ）、それ以外の未選択は選べなくする
                    if (extraCheckedCount >= 1 && !cb.checked) {
                        cb.disabled = true; 
                        label.classList.add('opacity-50', 'cursor-not-allowed');
                    } else {
                        // 選択可能な状態に戻す
                        cb.disabled = false;
                        label.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                });
            }

            // イベントリスナー
            if(membersInput) {
                membersInput.addEventListener('input', updateFormState);
            }
            
            checkboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    // デザイン更新
                    const label = document.getElementById('label-' + this.value);
                    if(this.checked) {
                        label.classList.add('bg-blue-50', 'border-blue-400');
                        label.classList.remove('border-gray-200');
                    } else {
                        label.classList.remove('bg-blue-50', 'border-blue-400');
                        label.classList.add('border-gray-200');
                    }
                    // ルール再計算
                    updateFormState();
                });
            });

            // サイドバー等の既存JS
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

            // 初期実行
            updateFormState();
        });
    </script>
</body>
</html>