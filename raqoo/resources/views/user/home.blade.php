<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Raqoo</title>
    <link rel="stylesheet" href="{{ asset('user/home.css') }}">

    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body>
    <div class="main-content">
   @include('layouts.sidebar')

        <!-- ヘッダー -->
        <div class="header">
            <div class="logo-box">Raqoo</div>
            <div class="profile-box">
                <div class="profile-icon">
                    <svg width="32" height="32" viewBox="0 0 32 32">
                        <circle cx="16" cy="12" r="8" fill="#000" />
                        <ellipse cx="16" cy="26" rx="12" ry="6" fill="#000" />
                    </svg>
                </div>
                <div class="profile-detail">
                    <div class="profile-id">学籍番号 ○○○○○○</div>
                    <div class="profile-name">名前〇〇〇〇</div>
                </div>
            </div>
        </div>

        <!-- 検索バー -->
        <div class="search-container">
            <form class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>日付</label>
                        <input type="date" placeholder="yyyy/mm/dd">
                    </div>
                    <div class="form-group">
                        <label>何限～何限利用するか</label>
                        <input type="text" placeholder="例: 1限 〜 3限">
                    </div>
                    <div class="form-group">
                        <label>利用人数</label>
                        <input type="text" placeholder="〇人">
                    </div>
                </div>
                <div class="form-row-btn">
                    <button type="submit" class="search-btn">検索</button>
                </div>
            </form>
        </div>

        <!-- 曜日タブ -->
        <div>
            <div class="day-tabs">
                <div class="day-tab">
                    <div class="day-label">日</div>
                    <div class="date-label">05/19</div>
                </div>
                <div class="day-tab">
                    <div class="day-label">月</div>
                    <div class="date-label">05/20</div>
                </div>
                <div class="day-tab">
                    <div class="day-label">火</div>
                    <div class="date-label">05/21</div>
                </div>
                <div class="day-tab">
                    <div class="day-label">水</div>
                    <div class="date-label">05/22</div>
                </div>
                <div class="day-tab">
                    <div class="day-label">木</div>
                    <div class="date-label">05/23</div>
                </div>
                <div class="day-tab">
                    <div class="day-label">金</div>
                    <div class="date-label">05/24</div>
                </div>
                <div class="day-tab">
                    <div class="day-label">土</div>
                    <div class="date-label">05/25</div>
                </div>
            </div>
            <div class="date-section">
                <div class="date-title-row">
                    <span class="date-year">2025</span>
                    <span class="date-main">05/20</span>
                    <span class="date-weekday">（月）</span>
                </div>
                <div class="status-bar-2">
                    <span class="status-line"></span>
                    ･･･現在使用中
                    <span class="status-dot"></span>
                    ･･･利用可能
                </div>
            </div>
        </div>

        {{-- 予約申請の送信成功メッセージ --}}
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

<<<<<<< HEAD
        {{-- DBから繰り返し教室内容表示（表形式） --}}
        <table>
            <tr>
                <th>教室名</th>
                <th>収容人数</th>
                <th>ステータス</th>
            </tr>
            @foreach ($rooms as $room)
                <tr>
                    <td>{{ $room->name }}</td>
                    <td>{{ $room->capacity }}</td>
                    <td>
                        {{ $room->status === 'available' ? '利用可能' : ($room->status === 'in_use' ? '利用中' : '利用不可') }}
                    </td>
                </tr>
            @endforeach
        </table>
=======
>>>>>>> develop

        {{-- DBから繰り返し教室内容表示（本番向けの表示） --}}
        @foreach ($rooms as $room)
            <div class="room-group">
                <div class="room-title">{{ $room->name }}</div>
                <div class="room-times">
                    @for ($period = 1; $period <= 6; $period++)
<<<<<<< HEAD
    @php
        $status = $room->status; // ←ここを変更
        $times = [
            1 => ['09:10', '10:40'],
            2 => ['10:50', '12:20'],
            3 => ['13:00', '14:30'],
            4 => ['14:40', '16:10'],
            5 => ['16:20', '17:50'],
            6 => ['18:00', '19:30'],
        ];
    @endphp

=======
                        @php
                            $status = $room->getStatusForPeriod(date('Y-m-d'), $period);
                            $times = [
                                1 => ['09:10', '10:40'],
                                2 => ['10:50', '12:20'],
                                3 => ['13:00', '14:30'],
                                4 => ['14:40', '16:10'],
                                5 => ['16:20', '17:50'],
                                6 => ['18:00', '19:30'],
                            ];
                        @endphp
>>>>>>> develop
                        <div class="time-card {{ $status === 'available' ? 'available' : 'unavailable' }}">
                            <div class="time-main">{{ $times[$period][0] }}</div>
                            <div class="time-sub">~{{ $times[$period][1] }}</div>
                            <div class="floor">{{ $room->floor }}</div>
<<<<<<< HEAD
                           @if ($status === 'available')
    <div class="available-label">利用可能</div>
    {{-- 一時的にルート呼び出しを停止 --}}
    <a href="#" class="reserve-btn">予約</a>
@else
    <div class="unavailable-label">
        {{ $status === 'in_use' ? '利用中' : ($status === 'reserved' ? '予約済み' : '利用不可') }}
    </div>
@endif

=======
                            @if ($status === 'available')
                                <div class="available-label">利用可能</div>
                                <a href="{{ route('user.reservations.create', ['room_id' => $room->id, 'date' => date('Y-m-d'), 'period' => $period]) }}"
                                    class="reserve-btn">予約</a>
                            @else
                                <div class="unavailable-label">
                                    {{ $status === 'in_use' ? '利用中' : ($status === 'reserved' ? '予約済み' : '利用不可') }}
                                </div>
                            @endif
>>>>>>> develop
                        </div>
                    @endfor
                </div>
            </div>
        @endforeach

<<<<<<< HEAD




        <script>
            const list = document.querySelectorAll(".list");

            function activeLink() {
                list.forEach(item => item.classList.remove("active"));
                this.classList.add("active");
            }
            list.forEach(item => item.addEventListener("click", activeLink));
        </script>
        <script src="{{ asset('user/home.js') }}"></script>
=======
        <script src="{{ asset('user/home.js' )}}"></script>
>>>>>>> develop
</body>

</html>
