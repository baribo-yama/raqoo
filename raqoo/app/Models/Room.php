<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    // fillableはseederでcreate()でデータを入れるときに、このmodelで指定したカラムだけを保存する
    protected $fillable = ['name', 'floor', 'capacity'];

    // RoomStatusとのリレーション
    public function roomStatuses()
    {
        return $this->hasMany(RoomStatus::class);
    }

    // 特定の日付・次元のステータスを取得
    public function getStatusForPeriod($date, $period)
    {
        $roomStatus = $this->roomStatuses()
            ->where('date', $date)
            ->where('period', $period)
            ->first();

        return $roomStatus ? $roomStatus->status : 'available';
    }
}
