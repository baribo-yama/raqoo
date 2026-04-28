<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'message',
        'is_read'

    ];

    protected $casts = [
        'is_read' => 'boolean'
    ];

    // リレーション: ユーザー（nullableの場合は全体通知）
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // スコープ：未読通知
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    // 特定ユーザーの未読通知数を取得
    public static function getUnreadCountForUser($userId)
    {
        return static::where('user_id', $userId)->where('is_read', false)->count();
    }
}
