<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable // implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // 一括代入を許可する許可リスト（以下で設定したものだけを入れる）
    // $fillableに追加されていないカラムは一括代入の対象から自動的に除外
    protected $fillable = [
        'family_name',
        'first_name',
        'email',
        'password',
        'student_id',
        'major_id',
        'google_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    // json化、配列化時に除外する秘匿カラム（パスワード・認証トークン）
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // 'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }
}
