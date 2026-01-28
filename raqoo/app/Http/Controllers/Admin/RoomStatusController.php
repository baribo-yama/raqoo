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

    /**
     * 指定日付の全教室・前時限のステータスを取得
     * ユーザー画面と管理者画面で共通仕様
     */
    public function getStatus(Request $request)
    {
        $date = $request->query('date');

        if (!$date) {
            return response()->json(['error' => '日付が指定されていません'], 400);
        }

        $rooms = Room::with('roomStatuses')->get();

        $statuses = [];
        foreach ($rooms as $room) {
            for ($period = 1; $period <= 6; $period++) {
                // Room.phpから基本的なステータスを取得
                $status = $room->getStatusForPeriod($date, $period, null);

                // =========== 管理者専用：ステータス変換処理 ===========
                // Room::getStatusForAdmin() は承認済み予約を 'unavailable' で返すため、
                // ここで 'reserved' に変換して管理画面に表示
                // ※ 'pending'（申請中）は管理画面には表示しない仕様
                if ($status === 'unavailable') {
                    $hasReservation = \App\Models\Reservation::where('room_id', $room->id)
                        ->where('date', $date)
                        ->where('period', $period)
                        ->whereIn('status', ['reserved', 'in_use'])
                        ->exists();

                    if ($hasReservation) {
                        $status = 'reserved'; // 管理ダッシュボードに「予約」と表示
                    }
                }



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
