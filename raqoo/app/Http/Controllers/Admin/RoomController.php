<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    // 教室一覧画面（トップ）
    public function index()
    {
        $rooms = Room::all();

        // 授業の時間帯定義
        $periods = [
            (object)['id' => 1, 'time' => '9:10-10:40'],
            (object)['id' => 2, 'time' => '10:50-12:20'],
            (object)['id' => 3, 'time' => '13:00-14:30'],
            (object)['id' => 4, 'time' => '14:40-16:10'],
            (object)['id' => 5, 'time' => '16:20-17:50'],
            (object)['id' => 6, 'time' => '18:00-19:30'],
        ];

        return view('admin.dashboard', compact('rooms', 'periods'));
    }
}
