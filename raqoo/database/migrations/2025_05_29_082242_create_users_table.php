<?php

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
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // オートインクリメントの主キー
            $table->string('family_name', 50)->nullable();// 姓
            $table->string('first_name', 50)->nullable();// 名
            $table->string('student_id', 12)->nullable()->unique();
            $table->foreignId('major_id')->nullable()->constrained('majors'); // 専攻テーブルを参照
            $table->string('email')->unique();
            $table->string('password')->nullable();// charのpassからstringのpasswordに変更
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
