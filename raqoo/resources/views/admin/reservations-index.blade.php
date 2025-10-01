<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約管理 - Raqoo管理画面</title>
    <link rel="stylesheet" href="{{ asset('admin/dashboard.css') }}">
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div class="container">
        <!-- サイドバー -->
        <nav class="sidebar">
            <h2>Raqoo管理</h2>
            <ul>
                <li class="menu-item">
                    <a href="{{ route('admin.dashboard') }}">ダッシュボード</a>
                </li>
                <li class="menu-item active">
                    <a href="{{ route('admin.reservations.index') }}">予約管理</a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('admin.rooms.index') }}">教室管理</a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('admin.notifications.index') }}">お知らせ管理</a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('admin.logout') }}">ログアウト</a>
                </li>
            </ul>
        </nav>

        <!-- メイン画面 -->
        <main class="main">
            <div class="screen active">
                <h1>予約管理</h1>

                <!-- フィルター -->
                <div class="bg-white p-4 rounded-lg shadow mb-6">
                    <div class="flex flex-wrap gap-4 items-center">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ステータス</label>
                            <select id="statusFilter" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                                <option value="">全て</option>
                                <option value="pending">承認待ち</option>
                                <option value="approved">承認済み</option>
                                <option value="rejected">却下</option>
                                <option value="in_use">利用中</option>
                                <option value="completed">完了</option>
                                <option value="cancelled">キャンセル</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">並び替え</label>
                            <select id="sortFilter" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                                <option value="date_asc">日付順（昇順）</option>
                                <option value="date_desc">日付順（降順）</option>
                                <option value="status">ステータス順</option>
                                <option value="created_at">申請日順</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">教室</label>
                            <select id="roomFilter" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                                <option value="">全教室</option>
                                @foreach($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 予約一覧 -->
                <div class="content">
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            予約情報
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            申請者
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            利用目的
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ステータス
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            アクション
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="reservationTableBody">
                                    @foreach($reservations as $reservation)
                                    <tr class="reservation-row" 
                                        data-status="{{ $reservation->status }}" 
                                        data-room-id="{{ $reservation->room_id }}"
                                        data-date="{{ $reservation->date }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $reservation->room->name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ \Carbon\Carbon::parse($reservation->date)->format('Y/m/d') }}({{ \Carbon\Carbon::parse($reservation->date)->locale('ja')->dayName }}) {{ $reservation->period }}限
                                                </div>
                                                <div class="text-xs text-gray-400">
                                                    申請: {{ $reservation->created_at->format('m/d H:i') }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $reservation->user->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $reservation->user->student_id }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $reservation->purpose }}">
                                                {{ $reservation->purpose }}
                                            </div>
                                            @if($reservation->members)
                                                <div class="text-xs text-gray-500 mt-1">
                                                    利用人数: {{ 1 + count(array_filter(explode("\n", $reservation->members))) }}名
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if($reservation->status === 'pending') bg-yellow-100 text-yellow-800
                                                @elseif($reservation->status === 'approved') bg-green-100 text-green-800
                                                @elseif($reservation->status === 'rejected') bg-red-100 text-red-800
                                                @elseif($reservation->status === 'in_use') bg-blue-100 text-blue-800
                                                @elseif($reservation->status === 'completed') bg-gray-100 text-gray-800
                                                @elseif($reservation->status === 'cancelled') bg-gray-100 text-gray-800
                                                @endif">
                                                @if($reservation->status === 'pending') 承認待ち
                                                @elseif($reservation->status === 'approved') 承認済み
                                                @elseif($reservation->status === 'rejected') 却下
                                                @elseif($reservation->status === 'in_use') 利用中
                                                @elseif($reservation->status === 'completed') 完了
                                                @elseif($reservation->status === 'cancelled') キャンセル
                                                @endif
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            @if($reservation->status === 'pending')
                                                <button onclick="updateStatus({{ $reservation->id }}, 'approved')" 
                                                        class="text-green-600 hover:text-green-900 px-2 py-1 bg-green-50 rounded">
                                                    承認
                                                </button>
                                                <button onclick="showRejectModal({{ $reservation->id }})" 
                                                        class="text-red-600 hover:text-red-900 px-2 py-1 bg-red-50 rounded">
                                                    却下
                                                </button>
                                            @elseif($reservation->status === 'approved')
                                                <button onclick="updateStatus({{ $reservation->id }}, 'in_use')" 
                                                        class="text-blue-600 hover:text-blue-900 px-2 py-1 bg-blue-50 rounded">
                                                    利用開始
                                                </button>
                                                <button onclick="updateStatus({{ $reservation->id }}, 'cancelled')" 
                                                        class="text-gray-600 hover:text-gray-900 px-2 py-1 bg-gray-50 rounded">
                                                    キャンセル
                                                </button>
                                            @elseif($reservation->status === 'in_use')
                                                <button onclick="updateStatus({{ $reservation->id }}, 'completed')" 
                                                        class="text-gray-600 hover:text-gray-900 px-2 py-1 bg-gray-50 rounded">
                                                    利用完了
                                                </button>
                                            @endif
                                            <a href="{{ route('admin.reservations.show', $reservation->id) }}" 
                                               class="text-indigo-600 hover:text-indigo-900 px-2 py-1 bg-indigo-50 rounded">
                                                詳細
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ページネーション -->
                    @if($reservations->hasPages())
                    <div class="mt-6">
                        {{ $reservations->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </main>
    </div>

    <!-- 却下理由モーダル -->
    <div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg max-w-md mx-4">
            <h3 class="text-lg font-semibold mb-4">予約却下</h3>
            <form id="rejectForm">
                <div class="mb-4">
                    <label for="rejectionReason" class="block text-sm font-medium text-gray-700 mb-2">
                        却下理由 <span class="text-red-500">*</span>
                    </label>
                    <textarea id="rejectionReason" name="rejection_reason" rows="4" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                              placeholder="却下理由を入力してください"></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeRejectModal()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                        キャンセル
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                        却下する
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentReservationId = null;

        // ステータス更新
        function updateStatus(reservationId, status) {
            if (confirm('ステータスを変更しますか？')) {
                fetch(`/admin/reservations/${reservationId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ status: status })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('エラーが発生しました');
                    }
                });
            }
        }

        // 却下モーダル表示
        function showRejectModal(reservationId) {
            currentReservationId = reservationId;
            document.getElementById('rejectModal').classList.remove('hidden');
            document.getElementById('rejectModal').classList.add('flex');
        }

        // 却下モーダル閉じる
        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejectModal').classList.remove('flex');
            document.getElementById('rejectionReason').value = '';
        }

        // 却下処理
        document.getElementById('rejectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const reason = document.getElementById('rejectionReason').value;
            
            fetch(`/admin/reservations/${currentReservationId}/reject`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ rejection_reason: reason })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeRejectModal();
                    location.reload();
                } else {
                    alert('エラーが発生しました');
                }
            });
        });

        // フィルター機能
        function applyFilters() {
            const statusFilter = document.getElementById('statusFilter').value;
            const roomFilter = document.getElementById('roomFilter').value;
            const rows = document.querySelectorAll('.reservation-row');

            rows.forEach(row => {
                let show = true;
                
                if (statusFilter && row.dataset.status !== statusFilter) {
                    show = false;
                }
                
                if (roomFilter && row.dataset.roomId !== roomFilter) {
                    show = false;
                }
                
                row.style.display = show ? '' : 'none';
            });
        }

        // フィルターイベント
        document.getElementById('statusFilter').addEventListener('change', applyFilters);
        document.getElementById('roomFilter').addEventListener('change', applyFilters);
    </script>
</body>
</html>