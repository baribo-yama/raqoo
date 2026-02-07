<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomLesson;
use App\Models\Reservation;
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
        $validated = $request->validate([
            'date' => 'required|date',
        ]);
        $date = $validated['date'];

        $userId = Auth::id();
        $rooms = Room::all();

        // 一括取得して look up を作る
        $lessonMap = [];
        foreach (RoomLesson::where('date', $date)->get() as $lesson) {
            $lessonMap[$lesson->room_id][$lesson->period] = $lesson;
        }

        $reservationMap = [];
        foreach (Reservation::where('date', $date)
            ->whereIn('status', ['pending', 'reserved', 'in_use'])
            ->get() as $reservation) {
            $reservationMap[$reservation->room_id][$reservation->period][] = $reservation;
        }


        $statuses = [];
        foreach ($rooms as $room) {
            for ($period = 1; $period <= 6; $period++) {
                $status = $room->getStatusForPeriod($date, $period, $userId, $lessonMap, $reservationMap);
                $lesson = $lessonMap[$room->id][$period] ?? null;

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
