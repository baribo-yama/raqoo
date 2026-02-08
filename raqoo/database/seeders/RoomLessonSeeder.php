<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoomLesson;
use App\Models\Room;

class RoomLessonSeeder extends Seeder
{
    public function run(): void
    {
        $room = Room::first();
        if (!$room) {
            return;
        }

        RoomLesson::updateOrCreate(
            [
                'room_id' => $room->id,
                'date' => '2026-01-29',
                'period' => 2,
            ],
            [
                'title' => 'python基礎',
            ]
        );

        RoomLesson::updateOrCreate(
            [
                'room_id' => $room->id,
                'date' => '2026-01-31',
                'period' => 2,
            ],
            [
                'title' => 'webアプリ開発',
            ]
        );
    }
}
