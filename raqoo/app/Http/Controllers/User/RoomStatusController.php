<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomLesson;
use Carbon\Carbon;
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

        /*
            期限切れの selecting レコードを削除する。
            selecting は予約フォームで選択中の仮ロック状態。
            10分以上経過したものは古い選択状態として解除する。
        */
        Reservation::where('status', 'selecting')
            ->where('created_at', '<', now()->subMinutes(10))
            ->delete();

        /*
            授業情報を一括取得して lookup を作る。
            形式：
            $lessonMap[room_id][period] = RoomLesson
        */
        $lessonMap = [];

        foreach (RoomLesson::where('date', $date)->get() as $lesson) {
            $lessonMap[$lesson->room_id][$lesson->period] = $lesson;
        }

        /*
            予約情報を一括取得して lookup を作る。
            selecting も含めることで、他ユーザーがフォーム選択中の枠もロック対象にする。
            形式：
            $reservationMap[room_id][period][] = Reservation
        */
        $reservationMap = [];

        foreach (
            Reservation::where('date', $date)
                ->whereIn('status', ['selecting', 'pending', 'reserved', 'in_use'])
                ->get() as $reservation
        ) {
            $reservationMap[$reservation->room_id][$reservation->period][] = $reservation;
        }

        /*
            その日の自分がすでに予約・申請・利用中・選択中の時限を取得する。
            同じ時限に別教室を予約しようとした場合、available を conflict に変えるために使う。
        */
        $userReservedSet = array_flip(
            Reservation::where('user_id', $userId)
                ->where('date', $date)
                ->whereIn('status', ['selecting', 'pending', 'reserved', 'in_use'])
                ->pluck('period')
                ->map(fn ($period) => (int) $period)
                ->unique()
                ->all()
        );

        $statuses = [];

        foreach ($rooms as $room) {
            for ($period = 1; $period <= 6; $period++) {
                $status = $room->getStatusForPeriod(
                    $date,
                    $period,
                    $userId,
                    $lessonMap,
                    $reservationMap
                );

                $lesson = $lessonMap[$room->id][$period] ?? null;

                /*
                    その時限に自分がすでに別予約を持っている場合、
                    空き教室であっても conflict にする。
                */
                if ($status === 'available' && isset($userReservedSet[$period])) {
                    $status = 'conflict';
                }

                /*
                    今日の日付のみ、現在時刻がその時限の終了時刻を過ぎていたら利用不可にする。
                */
                if (Carbon::parse($date)->isSameDay(Carbon::today())) {
                    $endTime = Carbon::parse(config("period.{$period}.end_time"));

                    if (now()->greaterThan($endTime)) {
                        $status = 'unavailable';
                    }
                }

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