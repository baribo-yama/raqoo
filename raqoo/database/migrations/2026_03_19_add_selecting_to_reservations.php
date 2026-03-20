<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // statusカラムがenum型の場合はALTER TABLEで'selecting'を追加する
        // varchar型の場合はこのマイグレーションは不要（そのまま動く）
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('selecting','pending','reserved','in_use','finished','cancelled','rejected') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // selectingレコードを先に削除してからenum定義を元に戻す
        DB::table('reservations')->where('status', 'selecting')->delete();
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('pending','reserved','in_use','finished','cancelled','rejected') NOT NULL DEFAULT 'pending'");
    }
};