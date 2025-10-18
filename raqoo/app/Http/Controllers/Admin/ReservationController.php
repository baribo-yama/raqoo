<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    public function __construct()
    {
        // 管理者認証ミドルウェアを有効化
        $this->middleware('auth:admin');
    }

    // 予約申請一覧
    public function index()
    {
        $reservations = Reservation::with(['user', 'room'])
            ->where('status', 'pending')
            ->orderBy('date')
            ->orderBy('period')
            ->get();

        return view('admin.reservations-index', compact('reservations'));
    }

    // 承認処理
    public function approve(Reservation $reservation)
    {
        $reservation->update(['status' => 'reserved']);

        // room_statusesテーブルに予約済み状態を追加
        RoomStatus::updateOrCreate(
            [
                'room_id' => $reservation->room_id,
                'date' => $reservation->date,
                'period' => $reservation->period,
            ],
            ['status' => 'reserved']
        );

        // ログ追加
        Log::info('予約承認完了', [
            'reservation_id' => $reservation->id,
            'room_id' => $reservation->room_id,
            'date' => $reservation->date,
            'period' => $reservation->period
        ]);

        return redirect()->back()->with('success', '予約を承認しました。');
    }

    // 予約却下（理由付き）
    public function reject(Request $request, Reservation $reservation)
    {
        $reservation->update(['status' => 'cancelled']);

        Log::info('予約却下完了', [
            'reservation_id' => $reservation->id,
            'reason' => 'admin_rejected'
        ]);

        return redirect()->back()->with(['success', '予約を却下しました。']);
    }
}
