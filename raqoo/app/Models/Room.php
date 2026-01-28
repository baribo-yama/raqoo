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
     * 
     * @param string $date 日付 (Y-m-d)
     * @param int $period 時限 (1-6)
     * @param int|null $userId ユーザーID（null=管理者）
     * @return string ステータス ('available', 'pending', 'reserved', 'unavailable')
     */
    public function getStatusForPeriod($date, $period, $userId = null)
    {
        if ($userId !== null) {
            return $this->getStatusForUser($date, $period, $userId);
        }
        
        return $this->getStatusForAdmin($date, $period);
    }

    /**
     * ユーザー向けステータス判定
     * 
     * @param string $date
     * @param int $period
     * @param int $userId
     * @return string
     */
    private function getStatusForUser($date, $period, $userId)
    {
        // 1. 自分の申請状態をチェック（優先度：高）
        $myReservation = Reservation::where('room_id', $this->id)
            ->where('date', $date)
            ->where('period', $period)
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'reserved', 'in_use'])
            ->first();

        if ($myReservation) {
            return $myReservation->status; // 'pending', 'reserved', 'in_use'
        }

        // 2. 他人の予約・利用状況をチェック
        $othersReservation = Reservation::where('room_id', $this->id)
            ->where('date', $date)
            ->where('period', $period)
            ->where('user_id', '!=', $userId)
            ->whereIn('status', ['pending', 'reserved', 'in_use'])
            ->exists();

        if ($othersReservation) {
            return 'unavailable'; // 他人が使用中・予約済み
        }

        // 3. どの条件にも該当しない場合
        return 'available';
    }

    /**
     * 管理者向けステータス判定
     * 
     * @param string $date
     * @param int $period
     * @return string
     */
    private function getStatusForAdmin($date, $period)
    {
        // 承認済みの予約のみを検出（pendingは表示しない）
        $approvedReservation = Reservation::where('room_id', $this->id)
            ->where('date', $date)
            ->where('period', $period)
            ->whereIn('status', ['reserved', 'in_use'])
            ->exists();

        if ($approvedReservation) {
            return 'unavailable'; // RoomStatusControllerで'reserved'に変換される
        }

        return 'available';
    }
}
