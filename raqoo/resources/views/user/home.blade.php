<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Raqoo</title>
    <link rel="stylesheet" href="{{ asset('user/home.css') }}" />
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body>
    <div class="main-content">
        <div class="navigation">
            <ul>
                <li class="list active">
                    <a href="#">
                        <span class="icon"><ion-icon name="home-outline"></ion-icon></span>
                        <span class="title">ホーム</span>
                    </a>
                </li>
                <li class="list">
                    <a href="#">
                        <span class="icon"><ion-icon name="person-circle-outline"></ion-icon></span>
                        <span class="title">プロフィール</span>
                    </a>
                </li>
                <li class="list">
                    <a href="#">
                        <span class="icon"><ion-icon name="settings-outline"></ion-icon></span>
                        <span class="title">設定</span>
                    </a>
                </li>
                <li class="list">
                    <a href="#">
                        <span class="icon"><ion-icon name="radio-outline"></ion-icon></span>
                        <span class="title">お知らせ</span>
                    </a>
                </li>
                <li class="list">
                    <a href="#">
                        <span class="icon"><ion-icon name="newspaper-outline"></ion-icon></span>
                        <span class="title">利用規約</span>
                    </a>
                </li>
                <li class="list">
                    <a href="#">
                        <span class="icon"><ion-icon name="log-out-outline"></ion-icon></span>
                        <span class="title">サインアウト</span>
                    </a>
                </li>
            </ul>
        </div>

            <!-- ヘッダー（ロゴ画像＋プロフィール） -->
  <div class="header">
    {{-- ロゴ画像 --}}
    <div class="logo-wrapper">
        <img src="{{ asset('user/images/raqoo-logo.png') }}"
             alt="raqoo logo"
             class="raqoo-logo">
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
                    <div class="profile-id">学籍番号 ○○○○○○</div>
                    <div class="profile-name">名前〇〇〇〇</div>
                </div>
            </div>
        </div>


        <!-- 検索バー -->
      <!-- （中略）検索フォーム。フォームには .search-form、日付入力は type="date" -->
<form class="search-form">
  <div class="form-row">
    <!-- 日付 -->
    <div class="form-group">
      <label for="dateInput">日付</label>
      <input type="date" id="dateInput" placeholder="yyyy/mm/dd">
    </div>

    <!-- 何限～何限 -->
    <div class="form-group">
      <label for="periodInput">何限～何限利用するか</label>
      <input type="text" id="periodInput" placeholder="例：1限 ～ 3限">
    </div>

    <!-- 利用人数 -->
    <div class="form-group">
      <label for="peopleInput">利用人数</label>
      <input type="text" id="peopleInput" placeholder="〇人">
      <!-- 数字限定にしたいなら type="number" min="1" step="1" に変更可 -->
    </div>
  </div>

  <div class="form-row-btn">
    <button type="submit" class="search-btn">検索</button>
  </div>
</form>


<!-- ▼曜日ストリップ：.day-tabs の中に左右ボタンを置く -->
<div class="day-tabs">
  <!-- 左矢印（前日へ） -->
  <button type="button" class="strip-btn strip-prev" aria-label="前日へ">
    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
      <path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </button>

  <!-- ここに既存の .day-tab × 7 -->
  <!-- 例）.day-label が曜日, .date-label が MM/DD を表示 -->
  <div class="day-tab">
    <div class="day-label">日</div>
    <div class="date-label">05/18</div>
  </div>
  <div class="day-tab">
    <div class="day-label">月</div>
    <div class="date-label">05/19</div>
  </div>
  <div class="day-tab">
    <div class="day-label">火</div>
    <div class="date-label">05/20</div>
  </div>
  <div class="day-tab">
    <div class="day-label">水</div>
    <div class="date-label">05/21</div>
  </div>
  <div class="day-tab">
    <div class="day-label">木</div>
    <div class="date-label">05/22</div>
  </div>
  <div class="day-tab">
    <div class="day-label">金</div>
    <div class="date-label">05/23</div>
  </div>
  <div class="day-tab">
    <div class="day-label">土</div>
    <div class="date-label">05/24</div>
  </div>

  <!-- 右矢印（翌日へ） -->
  <button type="button" class="strip-btn strip-next" aria-label="翌日へ">
    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
      <path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </button>
</div>

<!-- ▼中央の大きい日付（クラス名は既存のままに合わせてください） -->
<div class="date-display">
  <span class="date-main">05/20</span>
  <span class="date-weekday">（火）</span>
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


        {{-- DBから繰り返し教室内容表示（本番向けの表示） --}}
        @foreach ($rooms as $room)
            <div class="room-group">
                <div class="room-title">{{ $room->name }}</div>
                <div class="room-times">
                    @for ($period = 1; $period <= 6; $period++)
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
                        <div class="time-card {{ $status === 'available' ? 'available' : 'unavailable' }}">
                            <div class="time-main">{{ $times[$period][0] }}</div>
                            <div class="time-sub">~{{ $times[$period][1] }}</div>
                            <div class="floor">{{ $room->floor }}</div>
                            @if ($status === 'available')
                                <div class="available-label">利用可能</div>
                                <a href="{{ route('user.reservations.create', ['room_id' => $room->id, 'date' => date('Y-m-d'), 'period' => $period]) }}"
                                    class="reserve-btn">予約</a>
                            @else
                                <div class="unavailable-label">
                                    {{ $status === 'in_use' ? '利用中' : ($status === 'reserved' ? '予約済み' : '利用不可') }}
                                </div>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>
        @endforeach

        <script src="{{ asset('user/home.js' )}}"></script>
</body>

</html>
