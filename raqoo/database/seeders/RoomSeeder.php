<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ★修正: 本番環境での誤爆防止のため、ローカル環境のみリセットを実行 (Copilot指摘対応)
        if (app()->isLocal()) {
            Schema::disableForeignKeyConstraints();
            Room::truncate();
            Schema::enableForeignKeyConstraints();
        }

        // 5階層分の部屋データを作成
        $rooms = [];
        for ($floor = 1; $floor <= 5; $floor++) {
            // 各階に2部屋ずつ (例: 101, 102)
            for ($num = 1; $num <= 2; $num++) {
                $roomNumber = $floor . sprintf('%02d', $num); // 101, 102...
                
                // 101, 201... は30人・プロジェクターあり
                // 102, 202... は25人・ホワイトボードのみ
                $isRoom1 = ($num === 1);

                $rooms[] = [
                    'name'       => $roomNumber . '教室',
                    'capacity'   => $isRoom1 ? 30 : 25,
                    'floor'      => $floor,
                    'equipment'  => $isRoom1 ? 'プロジェクター, ホワイトボード' : 'ホワイトボード',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // 一括挿入
        DB::table('rooms')->insert($rooms);
    }
}