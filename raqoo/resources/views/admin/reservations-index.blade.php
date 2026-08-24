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
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">予約申請管理</h1>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">申請者</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">教室</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">日付</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">時限</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">目的</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($reservations as $reservation)
                        <tr>
                            <td class="px-6 py-4">
                                {{ $reservation->user->family_name }}{{ $reservation->user->first_name }}<br>
                                <span class="text-sm text-gray-500">
                                    {{ $reservation->user->major }}<br>
                                    {{ $reservation->user->student_id }}
                                </span>
                            </td>
                            <td class="px-6 py-4">{{ $reservation->room->name }}</td>
                            <td class="px-6 py-4">{{ $reservation->date }}</td>
                            <td class="px-6 py-4">{{ $reservation->period }}限</td>
                            <td class="px-6 py-4">{{ $reservation->purpose }}</td>
                            <td class="px-6 py-4">
                                <div class="flex space-x-2">
                                    <form action="{{ route('admin.reservations.approve', $reservation) }}"
                                        method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                                            承認
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.reservations.reject', $reservation) }}"
                                        method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm"
                                            onclick="return confirm('この予約を却下しますか？')">
                                            却下
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                承認待ちの予約はありません
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
