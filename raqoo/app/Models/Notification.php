<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'is_read'
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // リレーション: ユーザー（nullableの場合は全体通知）
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
