<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomLesson extends Model
{
    protected $fillable = [
        'room_id',
        'date',
        'period',
        'title',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
