<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use Illuminate\Support\Facades\Schema;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 念のためデータを空にしてから追加（重複エラー防止）
        Schema::disableForeignKeyConstraints();
        Room::truncate();
        Schema::enableForeignKeyConstraints();

        Room::insert([
            // --- 1階 ---
            [
                'name' => '101教室',
                'floor' => 1,
                'capacity' => 30,
                'equipment' => 'プロジェクター, ホワイトボード',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '102教室',
                'floor' => 1,
                'capacity' => 25,
                'equipment' => 'ホワイトボード',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // --- 2階 ---
            [
                'name' => '201教室',
                'floor' => 2,
                'capacity' => 30,
                'equipment' => 'プロジェクター, ホワイトボード',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '202教室',
                'floor' => 2,
                'capacity' => 25,
                'equipment' => 'ホワイトボード',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // --- 3階 ---
            [
                'name' => '301教室',
                'floor' => 3,
                'capacity' => 30,
                'equipment' => 'プロジェクター, ホワイトボード',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '302教室',
                'floor' => 3,
                'capacity' => 25,
                'equipment' => 'ホワイトボード',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // --- 4階 ---
            [
                'name' => '401教室',
                'floor' => 4,
                'capacity' => 30,
                'equipment' => 'プロジェクター, ホワイトボード',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '402教室',
                'floor' => 4,
                'capacity' => 25,
                'equipment' => 'ホワイトボード',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // --- 5階 ---
            [
                'name' => '501教室',
                'floor' => 5,
                'capacity' => 30,
                'equipment' => 'プロジェクター, ホワイトボード',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '502教室',
                'floor' => 5,
                'capacity' => 25,
                'equipment' => 'ホワイトボード',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}