<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ★ Copilot指摘対応: SQLite等MySQL以外のドライバではスキップ
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // statusカラムにselectingを追加
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('selecting','pending','reserved','in_use','finished','cancelled','rejected') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // ★ Copilot指摘対応: SQLite等MySQL以外のドライバではスキップ
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // selectingレコードを先に削除してからenum定義を元に戻す
        // ★ Copilot指摘対応: 元のenum定義（rejected含まず）に正しく戻す
        DB::table('reservations')->where('status', 'selecting')->delete();
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('pending','reserved','in_use','cancelled','finished') NOT NULL DEFAULT 'pending'");
    }
};