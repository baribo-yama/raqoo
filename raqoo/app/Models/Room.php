<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    // RoomStatus（空き状況）とのリレーション
    public function roomStatuses(): HasMany
    {
        return $this->hasMany(RoomStatus::class);
    }

    /**
     * 指定した日付・時限のステータスを取得するメソッド
     * * @param string $date 日付 (Y-m-d)
     * @param int $period 時限 (1-6)
     * @return string ステータス ('available', 'reserved', 'in_use' など)
     */
    public function getStatusForPeriod($date, $period, $userId = null)
    {
        // ログインユーザーが申請したpending状態の予約をチェック
        if ($userId !== null) {
            $pendingReservation = \App\Models\Reservation::where('room_id', $this->id)
                ->where('date', $date)
                ->where('period', $period)
                ->where('status', 'pending')
                ->where('user_id', $userId) // 自分の申請のみをチェック
                ->exists();

            if ($pendingReservation) {
                return 'pending';
            }

            // ログインユーザーが予約したreserved状態の予約をチェック
            $userReservation = \App\Models\Reservation::where('room_id', $this->id)
                ->where('date', $date)
                ->where('period', $period)
                ->where('status', 'reserved')
                ->where('user_id', $userId)
                ->exists();

            if ($userReservation) {
                return 'reserved'; // 自分の予約は「予約済み」として表示
            }
        }


        // room_statusesテーブルから、日付と時限が一致するデータを1つ取得
        // 注意：room_statusesテーブルには pendingはない。それはreservationsテーブルにある
        $status = $this->roomStatuses()
            ->where('date', $date)
            ->where('period', $period)
            ->value('status'); // statusカラムの値を取得

        // reservedステータスがroom_statusesにある場合、その教室に予約がある
        // ただし、予約者が誰かはreservationsテーブルで確認する必要ある
        if ($status === 'reserved') {
            // reservationsテーブルで該当する予約があるか確認
            $hasReservation = \App\Models\Reservation::where('room_id', $this->id)
                ->where('date', $date)
                ->where('period', $period)
                ->where('status', 'reserved')
                ->exists();

            if ($hasReservation) {
                // 予約があるが、現在のユーザーが予約者でない場合は「利用不可」
                if ($userId === null || !\App\Models\Reservation::where('room_id', $this->id)
                    ->where('date', $date)
                    ->where('period', $period)
                    ->where('status', 'reserved')
                    ->where('user_id', $userId)
                    ->exists()) {
                    return 'unavailable'; // 他人の予約は「利用不可」表示
                }
            }

            // 自分の予約の場合は既に上でreservedが返されているはず
            return 'reserved';
        }


        // データがない場合は 'available' (利用可能) を返す
        return $status ?? 'available';
    }
}
