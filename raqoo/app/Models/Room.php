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


    // Room モデル
    public function getStatusForPeriod($date, $period)
    {
        return RoomStatus::getStatusForRoom($this->id, $date, $period);
    }
}
