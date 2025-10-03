<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomStatus extends Model
{
    protected $fillable = [
        'room_id', 'date', 'period', 'status'
    ];

    protected $casts = [
        'date' => 'date'
    ];

    // Roomとのリレーション
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
