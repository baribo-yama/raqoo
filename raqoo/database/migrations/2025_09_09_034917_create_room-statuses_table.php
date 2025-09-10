<?php
// 教室の利用状況を日付と時限ごとに管理
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('room_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->tinyInteger('period'); // 1〜6限
            $table->enum('status', ['available', 'reserved', 'in_use', 'unavailable'])->default('available');  // 利用可能、予約済み、利用中、利用不可
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
