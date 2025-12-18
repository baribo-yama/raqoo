<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>管理ダッシュボード - Raqoo</title>
    <link rel="stylesheet" href="{{ asset('admin/dashboard.css') }}">
</head>

<body>
    <!-- ヘッダー -->
    <header class="header">
        <div class="title">Raqoo 管理うにょにょーー</div>
        <nav class="menu">
            <a class="menu-item" href="#">ダッシュボード</a>
            <a class="menu-item" href="#">教室管理</a>
            <a class="menu-item" href="#">予約管理</a>
        </nav>
        <!-- 予約リクエスト通知アイコン -->
        <button class="notification-btn" id="requestToggle">
            <span class="icon">🔔</span>
            <span class="badge" id="reservationBadge">{{ $reservations->count() }}</span>
        </button>
    </header>

    <!-- メインコンテンツ -->
    <div class="dashboard-container">
        <!-- 左カラム: カレンダー（30%） -->
        <aside class="calendar-section">
            <div class="calendar-header">
                <button id="prevMonth">◀</button>
                <h2 id="currentMonth"></h2>
                <button id="nextMonth">▶</button>
            </div>
            <div class="calendar-grid" id="calendar">
                <!-- JavaScriptで生成 -->
            </div>
        </aside>

        <!-- 右カラム: 教室テーブル（70%） -->
        <main class="room-table-section">
            <div class="table-header">
                <h2 id="selectedDate"></h2>
            </div>

            <div class="table-wrapper">
                <table class="room-table">
                    <thead>
                        <tr>
                            <th class="room-col">教室</th>
                            @foreach ($periods as $period)
                                <th>{{ $period->id }}限
                                    <br>
                                    <small>{{ $period->time }}</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody id="roomTableBody">
                        @foreach ($rooms as $room)
                            <tr data-room="{{ $room->id }}">
                                <td class="room-col">{{ $room->name }}</td>
                                @foreach ($periods as $period)
                                    <td data-room-id="{{ $room->id }}" data-period-id="{{ $period->id }}">

                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- 予約リクエストサイドバー -->
    <div class="request-sidebar" id="requestSidebar">
        <div class="sidebar-header">
            <h3>予約リクエスト</h3>
            <button class="close-btn" id="closeSidebar">✕</button>
        </div>
        <div class="sidebar-content" id="requestList">
            @forelse($reservations as $reservation)
                <div class="request-card">
                    <div class="request-card-header">
                        <h4>{{ $reservation->user->name }}</h4>
                        <small>
                            学籍番号: {{ $reservation->user->student_id }}
                        </small>
                    </div>
                    <div class="request-card-info">教室: {{ $reservation->room->name }}</div>
                    <div class="request-card-info">日付: {{ $reservation->date }}</div>
                    <div class="request-card-info">時限: {{ $reservation->period }}限</div>
                    <div class="request-card-info">利用目的: {{ $reservation->purpose }}</div>

                    <div class="request-actions">
                        <form action="{{ route('admin.reservations.approve', $reservation) }}" method="POST">
                            @csrf
                            <button class="btn btn-approve" type="submit">
                                承認
                            </button>
                        </form>
                        <form action="{{ route('admin.reservations.reject', $reservation) }}" method="POST">
                            @csrf
                            <button class="btn btn-reject" type="submit" onclick="return confirm('この予約を却下しますか？')">
                                却下
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="request-card">
                    承認待ちの予約はありません
                </div>
            @endforelse

        </div>
    </div>

    <!-- オーバーレイ -->
    <div class="overlay" id="overlay"></div>

    <script src="{{ asset('admin/dashboard.js') }}"></script>
</body>

</html>
