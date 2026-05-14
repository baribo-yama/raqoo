<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE reservations
            MODIFY status ENUM('pending', 'reserved', 'in_use', 'cancelled', 'finished', 'selecting')
            NOT NULL DEFAULT 'pending'
        ");

        Schema::table('reservations', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'reservations_status_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex('reservations_status_created_at_index');
        });

        DB::table('reservations')
            ->where('status', 'selecting')
            ->delete();

        DB::statement("
            ALTER TABLE reservations
            MODIFY status ENUM('pending', 'reserved', 'in_use', 'cancelled', 'finished')
            NOT NULL DEFAULT 'pending'
        ");
    }
};