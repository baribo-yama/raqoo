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
    public function getStatusForPeriod($date, $period)
    {
        // room_statusesテーブルから、日付と時限が一致するデータを1つ取得
        $status = $this->roomStatuses()
            ->where('date', $date)
            ->where('period', $period)
            ->value('status'); // statusカラムの値を取得

        // データがない場合は 'available' (利用可能) を返す
        return $status ?? 'available';
    }
}