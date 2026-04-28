<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoomStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = Room::all();
        $periods = range(1, 6); // 1〜6限

        // 今日から1週間分
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addDays(6); // 今日 + 6日 = 7日分

        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            foreach ($rooms as $room) {
                foreach ($periods as $period) {
                    DB::table('room_statuses')->insert([
                        'room_id' => $room->id,
                        'date' => $date->toDateString(),
                        'period' => $period,
                        'status' => 'available', // 初期値は利用可能
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
