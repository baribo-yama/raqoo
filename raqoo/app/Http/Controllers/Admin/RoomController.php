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
        return view('admin.dashboard', compact('rooms'));
    }
}
