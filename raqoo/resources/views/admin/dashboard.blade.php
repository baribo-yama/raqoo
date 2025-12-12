<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理ダッシュボード - Raqoo</title>
    <link rel="stylesheet" href="{{ asset('admin/dashboard.css') }}">
</head>

<body>
    <!-- ヘッダー -->
    <header class="header">
        <div class="title">Raqoo 管理</div>
        <nav class="menu">
            <a class="menu-item" href="#">ダッシュボード</a>
            <a class="menu-item" href="#">教室管理</a>
            <a class="menu-item" href="#">予約管理</a>
        </nav>
        <!-- 予約リクエスト通知アイコン -->
        <button class="notification-btn" id="requestToggle">
            <span class="icon">🔔</span>
            <span class="badge">3</span>
        </button>
    </header>

    <!-- メインコンテンツ -->
    <div class="dashboard-container">
        <!-- 左カラム: カレンダー（30%） -->
        <aside class="calendar-section">
            <div class="calendar-header">
                <button id="prevMonth">◀</button>
                <h2 id="currentMonth">2025年11月</h2>
                <button id="nextMonth">▶</button>
            </div>
            <div class="calendar-grid" id="calendar">
                <!-- JavaScriptで生成 -->
            </div>
        </aside>

        <!-- 右カラム: 教室テーブル（70%） -->
        <main class="room-table-section">
            <div class="table-header">
                <h2 id="selectedDate">2025年11月15日（金）</h2>
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
                                    <td data-room-id="{{ $room->id }}"
                                        data-period-id="{{ $period->id }}">

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
            <!-- JavaScriptで生成 -->
        </div>
    </div>

    <!-- オーバーレイ -->
    <div class="overlay" id="overlay"></div>

    <script src="{{ asset('admin/dashboard.js') }}"></script>
</body>

</html>
