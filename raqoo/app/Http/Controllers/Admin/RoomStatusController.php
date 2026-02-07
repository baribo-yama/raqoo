<?php
// 管理者ダッシュボードで教室のステータスを管理する（予約状況、授業情報の有無）
namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RoomStatus;
use App\Models\Room;
use App\Models\RoomLesson;
use App\Models\Reservation;
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
     * 指定日付の全教室・全時限のステータスを取得
     * 管理者画面専用の仕様（ユーザー画面とは別実装）
     */
    public function getStatus(Request $request)
    {
        $date = $request->query('date');

        if (!$date) {
            return response()->json(['error' => '日付が指定されていません'], 400);
        }


        $rooms = Room::all();

        // 一括取得して look upを作る（検索して見つけるのではなくて表からキー指定して取得）
        // 対象日付の授業・予約をまとめて取得し、「room_id + period」で即引ける map（連想配列）」に変換している
        $lessonMap = [];
        foreach (RoomLesson::where('date', $date)->get() as $lesson) {
            $lessonMap[$lesson->room_id][$lesson->period] = $lesson;
        }

        $reservationMap = [];
        foreach (Reservation::where('date', $date)
            ->whereIn('status', ['pending', 'reserved', 'in_use'])  // statusカラムの値がこの三つのどれかであるレコードだけを取得する
            ->get() as $reservation) {
            $reservationMap[$reservation->room_id][$reservation->period][] = $reservation;
        }

        $statuses = [];
        foreach ($rooms as $room) {
            for ($period = 1; $period <= 6; $period++) {
                // Room.phpから基本的なステータスを取得
                $status = $room->getStatusForPeriod($date, $period, null, $lessonMap, $reservationMap);

                // 授業を優先的に取得（あるなら "lesson" 扱いにする）
                $lesson = $lessonMap[$room->id][$period] ?? null;
                if ($lesson) {
                    $status = 'lesson';
                }

                // =========== 管理者専用：ステータス変換処理 ===========
                // Room::getStatusForAdmin() は承認済み予約を 'unavailable' で返すため、
                // ここで 'reserved' に変換して管理画面に表示
                // ※ 'pending'（申請中）は管理画面には表示しない仕様
                if ($status === 'unavailable') {
                    $hasReservation = false;  // 予約があるかどうかのフラグ（初期値は'ない'=false）
                    foreach ($reservationMap[$room->id][$period] ?? [] as $r) {  // ないときは空配列[] つまりここから取り出すものは何もないのでループが一回も回らない
                        // $r->statusがreservedかin_useかどうかを判定
                        // in_array()は配列の中に値があるか調べる関数　
                        if (in_array($r->status, ['reserved', 'in_use'], true)) {  // 第三引数trueは厳密比較（型まで一致）を意味する
                            $hasReservation = true;
                            break;
                        }
                    }
                    if ($hasReservation) {  // 上で$hasReservation=trueになったものがここを通る
                        $status = 'reserved'; // 管理ダッシュボードに「予約」と表示
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

    /**
     * ダッシュボードのセル詳細（授業/予約/空）を取得
     */
    public function getCellDetail(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date',
            'period' => 'required|integer|min:1|max:6',
        ]);

        $roomId = $validated['room_id'];
        $date = $validated['date'];
        $period = $validated['period'];

        // 1.授業があるかチェック
        $lesson = \App\Models\RoomLesson::where('room_id', $roomId)
            ->where('date', $date)
            ->where('period', $period)
            ->first();

        if ($lesson) {
            return response()->json([
                'type' => 'lesson',
                'lesson' => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                ],
            ]);
        }

        // 2.予約があるかチェック（reserved/in_use）
        $reservation = \App\Models\Reservation::with('user')
            ->where('room_id', $roomId)
            ->where('date', $date)
            ->where('period', $period)
            ->whereIn('status', ['reserved', 'in_use'])  // whereIn : statusカラムの値がreservedかin_useのどちらかであるデータを探して
            ->first();

        // もし予約があれば予約情報を表示させる
        if ($reservation) {
            return response()->json([
                'type' => 'reservation',
                'reservation' => [
                    'id' => $reservation->id,
                    'representative' => $reservation->user?->name, // 代表者名
                    'members' => $reservation->members, // メンバー
                    'status' => $reservation->status, // ステータス
                ],
            ]);
        }

        // 3.授業とか予約とかなんもない場合（空セル）
        return response()->json([
            'type' => 'empty',
        ]);
    }

    /**
     * 授業登録（空セルに登録 / 既存なら上書き）
     */
    public function storeLesson(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date',
            'period' => 'required|integer|min:1|max:6',
            'title' => 'required|string|max:100',
        ]);

        $lesson = \App\Models\RoomLesson::updateOrCreate(
            [
                // すでにあれば以下の条件でlessonを取得する
                'room_id' => $validated['room_id'],
                'date' => $validated['date'],
                'period' => $validated['period'],
            ],
            [
                // なければ新たに作成する
                'title' => $validated['title'],
            ]
        );

        return response()->json(['success' => true, 'lesson_id' => $lesson->id]);  // このsuccessフラグは使ってないみたい

    }

    /**
     * 授業キャンセル（削除）
     */
    public function cancelLesson(Request $request)
    {
        // 削除するときは一意のレコードを特定すればいいので'title'はvalidationに含めない
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date',
            'period' => 'required|integer|min:1|max:6',
        ]);

        \App\Models\RoomLesson::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->where('period', $validated['period'])
            ->delete();

        return response()->json(['success' => true]);
    }


    /**
     * 予約キャンセル（削除）
     */
    // TODO:削除にしないでstatusをrejectにしたほうがいいか？その方が良い気がする
    public function cancelReservation(Request $request)
    {
        $validated = $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
        ]);

        // reservationテーブルから対象のreservation_idを取得して削除
        \App\Models\Reservation::where('id', $validated['reservation_id'])
            ->delete();

        return response()->json(['success' => true]);
    }
}
