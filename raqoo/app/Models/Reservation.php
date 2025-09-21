<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'user_id',
        'room_id', 
        'date',
        'period',
        'purpose',
        'members',
        'status',
        'rejection_reason'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // リレーション: ユーザー
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // リレーション: 教室
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
