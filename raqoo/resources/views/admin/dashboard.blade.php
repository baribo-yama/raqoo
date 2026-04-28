<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>管理ダッシュボード - Raqoo</title>
    <link rel="stylesheet" href="{{ asset('admin/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/cell-detail-modal.css') }}">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body>
    <header class="header">
        <div class="title">Raqoo 管理</div>
        <nav class="menu">
            <a class="menu-item" href="{{ route('admin.dashboard') }}">ダッシュボード</a>
            <a class="menu-item" href="{{ route('admin.rooms.index') }}">教室管理</a>
            <a class="menu-item" href="{{ route('admin.reservations.index') }}">予約管理</a>
        </nav>
        <form class="logout-form" action="{{ route('admin.logout') }}" method="POST">
            @csrf
            <button class="logout-btn" type="submit">
                <span class="logout-logo">
                    <ion-icon name="log-out-outline"></ion-icon>
                </span>
                <span class="logout-text">ログアウト</span>
            </button>
        </form>
        <button class="notification-btn" id="requestToggle">
            <span class="icon">🔔</span>
            <span class="badge" id="reservationBadge">{{ count($reservations) }}</span>
        </button>
    </header>

    <div class="dashboard-container">
        <aside class="calendar-section">
            <div class="calendar-header">
                <button id="prevMonth">◀</button>
                <h2 id="currentMonth"></h2>
                <button id="nextMonth">▶</button>
            </div>
            <div class="calendar-grid" id="calendar">
                </div>
        </aside>

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
                                <th>{{ $period->id }}限<br><small>{{ $period->time }}</small></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody id="roomTableBody">
                        @foreach ($rooms as $room)
                            <tr data-room="{{ $room->id }}">
                                <td class="room-col">{{ $room->name }}</td>
                                @foreach ($periods as $period)
                                    <td data-room-id="{{ $room->id }}" data-period-id="{{ $period->id }}"></td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </main>
    </div>


    <!-- リクエストサイドバー -->
    <div class="request-sidebar" id="requestSidebar">
        <div class="sidebar-header">
            <h3>予約リクエスト</h3>
            <button class="close-btn" id="closeSidebar">✕</button>
        </div>



        <div class="sidebar-content" id="requestList">
            @forelse($reservations as $group)
                <div class="request-card">
                    <div class="request-card-header">
                        <h4>{{ $group->user->name }}</h4>
                        <small>専攻名: {{ $group->user->major }}</small><br>
                        <small>学籍番号: {{ $group->user->student_id }}</small>
                    </div>
                    <div class="request-card-info">教室: {{ $group->room->name }}</div>
                    <div class="request-card-info">予約日: {{ $group->date }}</div>

                    {{-- ▼▼▼ 修正：まとめた時限を表示（例: 1~3限） ▼▼▼ --}}
                    <div class="request-card-info" style="font-weight:bold; color:#2563eb;">
                        時限: {{ $group->period }}限
                    </div>

                    <div class="request-card-info">利用目的: {{ $group->purpose }}</div>

                    <div class="request-actions">
                        {{-- ▼▼▼ 修正：一括承認フォーム ▼▼▼ --}}
                        <form action="{{ route('admin.reservations.bulk_approve') }}" method="POST">
                            @csrf
                            {{-- まとめたIDリストを送信 --}}
                            <input type="hidden" name="ids" value="{{ $group->ids }}">
                            <button class="btn btn-approve" type="submit">承認</button>
                        </form>

                        {{-- ▼▼▼ 修正：一括却下フォーム ▼▼▼ --}}
                        <form action="{{ route('admin.reservations.bulk_reject') }}" method="POST">
                            @csrf
                            <input type="hidden" name="ids" value="{{ $group->ids }}">
                            <button class="btn btn-reject" type="submit" onclick="return confirm('この予約（{{ $group->count }}コマ分）を全て却下しますか？')">
                                却下
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="request-card" style="text-align: center; color: gray;">
                    承認待ちの予約はありません
                </div>
            @endforelse
        </div>
    </div>

    <div class="overlay" id="overlay"></div>

    <!-- セル詳細モーダル -->
    @include('admin.cell-detail-modal')

    <script src="{{ asset('admin/dashboard.js') }}"></script>
    <script src="{{ asset('admin/cell-detail-modal.js') }}"></script>
</body>
</html>
