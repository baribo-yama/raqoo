<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 予約申請したユーザーに見せるもの
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->tinyInteger('period'); //1～6限
            $table->enum('status', ['selecting', 'pending', 'reserved', 'in_use', 'finished', 'cancelled', 'rejected'])->default('pending');  // 時間選択中、予約申請中、予約済み、利用中、利用終了、キャンセル、却下
            $table->string('purpose', 100);
            $table->text('members')->nullable(); // カンマ区切り or JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};