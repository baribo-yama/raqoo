<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomLesson;
use App\Models\Reservation;
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

        clock($validated);

        $date = $validated['date'];

        $userId = Auth::id();
        $rooms = Room::all();

        // 授業情報をRoomLessonテーブルから一括取得して look up を作る
        $lessonMap = [];
        foreach (RoomLesson::where('date', $date)->get() as $lesson) {
            $lessonMap[$lesson->room_id][$lesson->period] = $lesson;  // [どの教室][何限目か]をセットにして保存
        }

        $reservationMap = [];
        foreach (
            Reservation::where('date', $date)
                ->whereIn('status', ['pending', 'reserved', 'in_use'])
                ->get() as $reservation
        ) {
            $reservationMap[$reservation->room_id][$reservation->period][] = $reservation;  // 最後の空の[]は「教室×時限」という枠のなかにある1つ以上の予約データの塊
        }

        // その日の自分が予約している時限のセット
        $userReservedSet = array_flip(
            Reservation::where('user_id', $userId)
                ->where('date', $date)
                ->whereIn('status', ['pending', 'reserved', 'in_use'])
                ->pluck('period') // 部屋名などの余計な情報を除外してperiod（時限）の数字だけを取り出す ex) [1, 2]
                ->map(fn($p) => (int) $p) // 文字列の"1"などを数値の1に変換する（後の比較を正確にするため
                ->unique()
                ->all()
        );


        // 各部屋の各時限に対しての予約状況をチェックする
        $statuses = [];
        $now = env("APP_FAKE_NOW") ? \Carbon\Carbon::parse(env('APP_FAKE_NOW')) : now();
        foreach ($rooms as $room) {
            for ($period = 1; $period <= 6; $period++) {
                $status = $room->getStatusForPeriod($date, $period, $userId, $lessonMap, $reservationMap);
                $lesson = $lessonMap[$room->id][$period] ?? null;

                // もし同時刻に自分が予約をいれていればavailableをconflictに上書き
                // conflictはすでに同時刻に予約あるよ、っていうステータス
                if ($status === 'available' && isset($userReservedSet[$period])) {
                    $status = 'conflict';
                }

                // 今の時刻が、各roomの終了時刻より後なら、その部屋を利用不可にする
                // 今日に対してのみ行う
                if (Carbon::parse($date)->isSameDay(Carbon::today())) {
                    $startTime = Carbon::parse(config("period.{$period}.start_time"));  // startTime使われてないよ
                    $endTime = Carbon::parse(config("period.{$period}.end_time"));
                    if ($now->greaterThan($endTime)) {
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
