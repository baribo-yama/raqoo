<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * 指定日付の全教室・全時限のステータスを取得（ユーザー専用）
     */
    public function getStatus(Request $request)
    {
        $date = $request->query('date');

        if (!$date) {
            return response()->json(['error' => '日付が指定されていません'], 400);
        }

        $userId = Auth::id();
        $rooms = Room::with('roomStatuses')->get();


        $statuses = [];
        foreach ($rooms as $room) {
            for ($period = 1; $period <= 6; $period++) {
                $status = $room->getStatusForPeriod($date, $period, $userId);
                $lesson = RoomLesson::where('room_id', $room->id)
                    ->where('date', $date)
                    ->where('period', $period)
                    ->first();

                $statuses[] = [
                    'room_id' => $room->id,
                    'period' => $period,
                    'status' => $status,
                    'lesson_title' => $lesson?->title,
                ];
            }
        }

        return response()->json($statuses);
    }
}
