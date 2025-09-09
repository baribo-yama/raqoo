<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomStatus extends Model
{
    protected $fillable = [
        'room_id', 'date', 'period', 'status'
    ];
}
