<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Carbon\Carbon;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::with(['roomStatuses' => function($query) {
            $query->where('date', Carbon::today());
        }])->get();

        return view('user.home', compact('rooms')); // home.blade.php に一覧を渡す
    }
}
