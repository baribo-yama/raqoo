<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Room::insert([
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
            [
                'name' => '201教室',
                'floor' => 2,
                'capacity' => 40,
                'equipment' => 'プロジェクター, マイク',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // 必要なだけ追加
        ]);
    }
}
