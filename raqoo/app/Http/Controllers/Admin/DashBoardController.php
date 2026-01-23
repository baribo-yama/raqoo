<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashBoardController extends Controller
{
    public function index()
    {
        $rooms = Room::all();

        $periods = [
            (object)['id' => 1, 'time' => '9:10-10:40'],
            (object)['id' => 2, 'time' => '10:50-12:20'],
            (object)['id' => 3, 'time' => '13:00-14:30'],
            (object)['id' => 4, 'time' => '14:40-16:10'],
            (object)['id' => 5, 'time' => '16:20-17:50'],
            (object)['id' => 6, 'time' => '18:00-19:30'],
        ];

        $rawReservations = Reservation::with(['user', 'room'])
            ->where('status', 'pending')
            ->orderBy('date')
            ->orderBy('room_id')
            // ▼▼▼ 修正：user_id順を追加してグループ化を確実に ▼▼▼
            ->orderBy('user_id')
            ->orderBy('period')
            ->get();

        $reservations = $this->groupReservations($rawReservations);

        return view('admin.dashboard', compact('rooms', 'periods', 'reservations'));
    }

    private function groupReservations($reservations)
    {
        $grouped = [];
        $temp = null;

        foreach ($reservations as $res) {
            $resDate = $res->date instanceof Carbon ? $res->date->format('Y-m-d') : substr($res->date, 0, 10);
            
            if ($temp) {
                $tempDate = $temp['date_raw'];

                if (
                    $temp['user_id'] == $res->user_id &&
                    $tempDate === $resDate &&
                    $temp['room_id'] == $res->room_id &&
                    $temp['purpose'] === $res->purpose &&
                    $temp['members'] === $res->members &&
                    (int)end($temp['periods']) + 1 === (int)$res->period
                ) {
                    $temp['periods'][] = (int)$res->period;
                    $temp['ids'][] = $res->id; 
                    continue; 
                } else {
                    $grouped[] = $this->formatGroup($temp);
                    $temp = null;
                }
            }

            $temp = [
                'user' => $res->user,
                'user_id' => $res->user_id,
                'room' => $res->room,
                'room_id' => $res->room_id,
                'date_raw' => $resDate,
                'date_display' => $resDate,
                'periods' => [(int)$res->period],
                'purpose' => $res->purpose,
                'members' => $res->members,
                'ids' => [$res->id],
            ];
        }

        if ($temp) {
            $grouped[] = $this->formatGroup($temp);
        }

        return $grouped;
    }

    private function formatGroup($group)
    {
        $start = $group['periods'][0];
        $end = end($group['periods']);
        
        if ($start == $end) {
            $periodDisplay = "{$start}";
        } else {
            $periodDisplay = "{$start}~{$end}";
        }

        return (object)[
            'user' => $group['user'],
            'room' => $group['room'],
            'date' => $group['date_display'], 
            'period' => $periodDisplay,
            'purpose' => $group['purpose'],
            'ids' => implode(',', $group['ids']),
            'count' => count($group['ids']),
        ];
    }
}