<?php
// 教室の利用状態をうまいこと処理するやつ
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RoomStatus;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RoomStatusController extends Controller
{
    public function updateStatus(Request $request)
    {
        // 送られてきたリクエストを全部ログに出力
        Log::info('updateStatus called', $request->all());

        // バリデーション
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date',
            'period' => 'required|integer|min:1|max:6',
            'status' => 'required|in:available,reserved,in_use,unavailable',
        ]);

        // バリデーション後のデータも出力
        Log::info('validated data', $validated);

        // 既存なら更新、なければ新規作成
        $status = RoomStatus::updateOrCreate(
            [
                'room_id' => $validated['room_id'],
                'date' => $validated['date'],
                'period' => $validated['period'],
            ],
            [
                'status' => $validated['status'],
            ]
        );

        Log::info('saved status', $status->toArray());

        return response()->json(['success' => true]);
    }


    public function getStatus(Request $request)
    {
        $date = $request->query('date');

        if (!$date) {
            return response()->json(['error' => '日付が指定されていません'], 400);
        }

        $userId = Auth::id(); // 認証済みユーザーIDを取得
        $rooms = Room::with('roomStatuses')->get();

        $statuses = [];
        foreach ($rooms as $room) {
            for ($period = 1; $period <= 6; $period++) {
                $status = $room->getStatusForPeriod($date, $period, $userId);
                $statuses[] = [
                    'room_id' => $room->id,
                    'period' => $period,
                    'status' => $status,
                ];
            }
        }

        return response()->json($statuses);
    }
}
