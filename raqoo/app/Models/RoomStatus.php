<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomStatus extends Model
{
    protected $fillable = [
        'room_id',
        'date',
        'period',
        'status'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    // Roomとのリレーション
    public function room()
    {
        return $this->belongsTo(Room::class);
    }


    // 予約申請が通ったものをhome.bladeに返す
    public static function getStatusForRoom($roomId, $date, $period)
    {
        $roomStatus = self::where('room_id', $roomId)
            ->where('date', $date)
            ->where('period', $period)
            ->first();

        return $roomStatus ? $roomStatus->status : 'available';
    }
}
