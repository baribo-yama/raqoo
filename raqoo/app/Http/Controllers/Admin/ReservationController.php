<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    // 予約一覧表示
    public function index()
    {
        $reservations = Reservation::with(['user', 'room'])
            ->orderBy('date')
            ->orderBy('period')
            ->get();
        
        $rooms = Room::all();

        return view('admin.reservations-index', compact('reservations', 'rooms'));
    }

    // 予約詳細表示
    public function show(Reservation $reservation)
    {
        $reservation->load(['user', 'room']);
        return view('admin.reservation-detail', compact('reservation'));
    }

    // 予約ステータス更新
    public function updateStatus(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,in_use,completed,cancelled'
        ]);

        $reservation->update([
            'status' => $validated['status']
        ]);

        return response()->json(['success' => true]);
    }

    // 予約却下（理由付き）
    public function reject(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        $reservation->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason']
        ]);

        return response()->json(['success' => true]);
    }
}