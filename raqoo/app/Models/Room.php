<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\RoomLesson;
use App\Models\Reservation;

class Room extends Model
{
    use HasFactory;

    public function roomStatuses(): HasMany
    {
        return $this->hasMany(RoomStatus::class);
    }

    public function roomLessons(): HasMany
    {
        return $this->hasMany(RoomLesson::class);
    }

    /**
     * 指定した日付・時限のステータスを取得するメソッド
     *
     * @param string $date 日付 (Y-m-d)
     * @param int $period 時限 (1-6)
     * @param int|null $userId ユーザーID（null=管理者）
     * @param array $lessonMap 授業マップ
     * @param array $reservationMap 予約マップ
     * @return string ステータス ('available', 'selecting', 'pending', 'reserved', 'in_use', 'unavailable')
     */
    public function getStatusForPeriod($date, $period, $userId = null, $lessonMap = [], $reservationMap = [])
    {
        if ($userId !== null) {
            return $this->getStatusForUser($date, $period, $userId, $lessonMap, $reservationMap);
        }

        return $this->getStatusForAdmin($date, $period, $lessonMap, $reservationMap);
    }

    private function getStatusForUser($date, $period, $userId, $lessonMap, $reservationMap)
    {
        if (isset($lessonMap[$this->id][$period])) {
            return 'unavailable';
        }

        // 自分の予約（優先度：高）
        // selecting/pending/reserved/in_use なら自分のステータスをそのまま返す
        foreach ($reservationMap[$this->id][$period] ?? [] as $r) {
            if ($r->user_id === $userId && in_array($r->status, ['selecting', 'pending', 'reserved', 'in_use'], true)) {
                return $r->status;
            }
        }

        // 他人の予約
        // ★ selecting（フォーム選択中）も利用不可にすることで予約重複を防ぐ
        foreach ($reservationMap[$this->id][$period] ?? [] as $r) {
            if ($r->user_id !== $userId && in_array($r->status, ['selecting', 'pending', 'reserved', 'in_use'], true)) {
                return 'unavailable';
            }
        }

        return 'available';
    }

    private function getStatusForAdmin($date, $period, $lessonMap, $reservationMap)
    {
        if (isset($lessonMap[$this->id][$period])) {
            return 'unavailable';
        }

        foreach ($reservationMap[$this->id][$period] ?? [] as $r) {
            if (in_array($r->status, ['reserved', 'in_use'], true)) {
                return 'unavailable';
            }
        }

        return 'available';
    }
}