<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Raqoo</title>
    <link rel="stylesheet" href="{{ asset('user/home.css') }}" />
    <link rel="stylesheet" href="{{ asset('user/home.mobile.css') }}">
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body>
    <div class="layout">
        {{-- ナビゲーション --}}
        <x-user.sidebar />

        {{-- スマホ用バックドロップ --}}
        <div class="m-backdrop" id="navBackdrop" aria-hidden="true"></div>

        {{-- 右：メインコンテンツ --}}
        <div class="content-area">

            <div class="header">
                <div class="logo-wrapper">
                    <img src="{{ asset('user/images/raqoo-logo.png') }}" alt="raqoo logo" class="raqoo-logo">
                </div>
            </div>

            {{-- プロフィールボックス --}}
            <div class="profile-box">
                <div class="profile-icon">
                    <svg width="32" height="32" viewBox="0 0 32 32">
                        <circle cx="16" cy="12" r="8" fill="#000" />
                        <ellipse cx="16" cy="26" rx="12" ry="6" fill="#000" />
                    </svg>
                </div>
                <div class="profile-detail">
                    <div class="profile-id">学籍番号 {{ Auth::user()->student_id ?? '未設定' }}</div>
                    <div class="profile-name">{{ Auth::user()->name ?? 'ゲスト' }}</div>
                </div>
            </div>

            <div class="max-w-4xl mx-auto mb-8">
                <form action="{{ route('user.home.index') }}" method="GET" class="search-card">
                    @if (request('floor'))
                        <input type="hidden" name="floor" value="{{ request('floor') }}">
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                        <div class="md:col-span-4">
                            <label class="block text-sm font-bold text-gray-700 mb-2">日付</label>
                            <input type="date" id="dateInput" name="date" value="{{ $date ?? '' }}"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2 border">
                        </div>

                        <div class="md:col-span-4">
                            <label class="block text-sm font-bold text-gray-700 mb-2">利用時限 (開始〜終了)</label>
                            <div class="flex items-center space-x-2">
                                <select name="start_period"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 border">
                                    <option value="">指定なし</option>
                                    @for ($i = 1; $i <= 6; $i++)
                                        <option value="{{ $i }}"
                                            {{ ($startPeriod ?? '') == $i ? 'selected' : '' }}>{{ $i }}限
                                        </option>
                                    @endfor
                                </select>
                                <span class="text-gray-400">〜</span>
                                <select name="end_period"
                                    class="w-full rounded-md border-gray-300 shadow-sm p-2 border">
                                    <option value="">指定なし</option>
                                    @for ($i = 1; $i <= 6; $i++)
                                        <option value="{{ $i }}"
                                            {{ ($endPeriod ?? '') == $i ? 'selected' : '' }}>{{ $i }}限
                                        </option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-gray-700 mb-2">利用人数</label>
                            <input type="number" name="people" placeholder="0" value="{{ $people ?? '' }}"
                                class="w-full rounded-md border-gray-300 shadow-sm p-2 border">
                        </div>

                        <div class="md:col-span-2">
                            <button type="submit"
                                class="w-full bg-gray-800 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-md transition duration-200">
                                検索
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- 中央エリア --}}
            <div class="center-wrap">
                <div class="day-tabs">
                    <button type="button" class="strip-btn strip-prev" aria-label="前日へ">
                        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                            <path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>

                    @php
                        $weekdayLabels = ['日', '月', '火', '水', '木', '金', '土'];
                        $baseDate = now();
                    @endphp

                    @for ($i = 0; $i < 7; $i++)
                        @php $d = $baseDate->copy()->addDays($i); @endphp
                        <div class="day-tab">
                            <div class="day-label">{{ $weekdayLabels[$d->dayOfWeek] }}</div>
                            <div class="date-label">{{ $d->format('m/d') }}</div>
                        </div>
                    @endfor

                    <button type="button" class="strip-btn strip-next" aria-label="翌日へ">
                        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
                            <path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>

                <div class="date-display">
                    @php
                        $week = ['日', '月', '火', '水', '木', '金', '土'];
                        $today = now();
                    @endphp
                    <span class="date-year" style="display:none;"></span>
                    <span class="date-main">{{ $today->format('m/d') }}</span>
                    <span class="date-weekday">（{{ $week[$today->dayOfWeek] }}）</span>
                </div>

                <div class="status-bar-2">
                    <span class="status-line"></span>･･･現在使用中
                    <span class="status-dot"></span>･･･利用可能
                </div>

                <button type="button" class="m-status-btn" id="openStatusSheet">予約状況を見る</button>

                <div class="m-status-sheet" id="statusSheet" aria-hidden="true">
                    <div class="sheet-head">
                        <div class="sheet-title">予約状況</div>
                        <button type="button" class="sheet-close" id="closeStatusSheet" aria-label="閉じる">×</button>
                    </div>
                    <div class="sheet-body" id="statusSheetBody">読み込み中...</div>
                </div>
            </div>

            @if (session('success'))
                <div
                    class="alert alert-success bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-error bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="floor-filter-container">
                <div class="floor-filter-label">階数で絞り込み</div>
                <div class="floor-filter-list">
                    <a href="{{ request()->fullUrlWithoutQuery('floor') }}"
                        class="floor-chip {{ (request()->query('floor') ?? '') === '' ? 'active' : '' }}">すべて</a>
                    @for ($f = 1; $f <= 5; $f++)
                        <a href="{{ request()->fullUrlWithQuery(['floor' => $f]) }}"
                            class="floor-chip {{ request()->query('floor') === (string) $f ? 'active' : '' }}">{{ $f }}F</a>
                    @endfor
                </div>
            </div>

            {{-- 教室カード --}}
            @php
                $displayDate = $date ?? request()->query('date', date('Y-m-d'));
                $loopStart = $startPeriod ?? 1;
                $loopEnd = $endPeriod ?? 6;
            @endphp

            @foreach ($rooms as $room)
                <div class="room-group" data-room-id="{{ $room->id }}">
                    <div class="room-title">
                        {{ $room->name }}
                        <span
                            style="font-size:0.8em; font-weight:normal; margin-left:10px;">(定員:{{ $room->capacity }}人)</span>
                    </div>

                    {{-- 修正箇所1: cards-grid クラスを削除 --}}
                    <div class="room-times">
                        @for ($period = $loopStart; $period <= $loopEnd; $period++)
                            @php
                                $status = $room->getStatusForPeriod($displayDate, $period, auth()->id());
                                $times = [
                                    1 => ['09:10', '10:40'],
                                    2 => ['10:50', '12:20'],
                                    3 => ['13:00', '14:30'],
                                    4 => ['14:40', '16:10'],
                                    5 => ['16:20', '17:50'],
                                    6 => ['18:00', '19:30'],
                                ];
                                $isTarget = isset($startPeriod) || isset($endPeriod);
                                $isAvailable = $status === 'available';

                                $nextPath = route(
                                    'user.reservations.create',
                                    ['room_id' => $room->id, 'date' => $displayDate, 'period' => $period],
                                    false,
                                );
                                $linkUrl = route('user.precautions', ['next' => $nextPath]);
                            @endphp

                            {{-- 修正箇所3: aria-disabled 属性の追加 --}}
                            <a class="time-card {{ $status }} {{ $isTarget ? 'target-period' : '' }} {{ !$isAvailable ? 'locked' : '' }}"
                                data-period="{{ $period }}" data-base-href="{{ $linkUrl }}"
                                @if ($isAvailable) href="{{ $linkUrl }}" @else aria-disabled="true" @endif>
                                <div class="pc-layout">
                                    <div class="time-main">{{ $times[$period][0] }}</div>
                                    <div class="time-sub">~{{ $times[$period][1] }}</div>
                                    <div class="floor pc-only">{{ $room->floor }}階</div>

                                    <div class="status-area">
                                        {{-- 修正箇所2: style="display: none;" をやめ、Tailwindの hidden クラスで制御 --}}
                                        <div class="available-label {{ !$isAvailable ? 'hidden' : '' }}">利用可能</div>
                                        <div class="unavailable-label {{ $isAvailable ? 'hidden' : '' }}">
                                            @if ($status === 'pending')
                                                申請中
                                            @elseif($status === 'reserved')
                                                予約済み
                                            @elseif($status === 'in_use')
                                                利用中
                                            @else
                                                利用不可
                                            @endif
                                        </div>
                                    </div>
                                    <span class="reserve-btn pc-only hidden" data-href="{{ $linkUrl }}"></span>
                                </div>

                                <div class="sp-layout">
                                    <div class="sp-period-box">
                                        <span class="p-num">{{ $period }}</span>
                                        <span class="p-text">限</span>
                                    </div>
                                    <div class="sp-info-box">
                                        <div class="sp-time-row">
                                            <span class="time-main">{{ $times[$period][0] }}</span>
                                            <span class="tilde">~</span>
                                            <span class="time-sub">{{ $times[$period][1] }}</span>
                                        </div>
                                        <div class="sp-status-area">
                                            {{-- スマホ版も同様に hidden クラスに変更 --}}
                                            <span
                                                class="available-label {{ !$isAvailable ? 'hidden' : '' }}">利用可能</span>
                                            <span class="unavailable-label {{ $isAvailable ? 'hidden' : '' }}">
                                                @if ($status === 'pending')
                                                    申請中
                                                @elseif($status === 'reserved')
                                                    予約済み
                                                @elseif($status === 'in_use')
                                                    利用中
                                                @else
                                                    利用不可
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <div class="sp-action-box">
                                        {{-- ボタンの非表示制御も同様 --}}
                                        <span class="sp-reserve-btn {{ !$isAvailable ? 'hidden' : '' }}">予約する</span>
                                    </div>
                                </div>
                            </a>
                        @endfor
                    </div>
                </div>
                @if (!$loop->last)
                    <div class="w-full mx-auto my-8 border-b-2 border-gray-400 md:hidden max-w-[720px]"></div>
                @endif
            @endforeach

        </div>
    </div>

    <script src="{{ asset('user/home.js') }}"></script>
</body>

</html>
