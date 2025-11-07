<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomStatus;
use App\Models\Notification;
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

    //=====予約リクエスト承認処理=====//
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

        // 通知作成
        $roomName = optional($reservation->room)->name ?? '教室';
        $date = $reservation->date ?? '指定日';
        $period = $reservation->period ?? '指定時限';
        $message = "予約が承認されました。{$roomName} - {$date} {$period}時限目";

        Notification::create([
            'user_id' => $reservation->user_id,
            'message' => $message,
            'is_read' => false // 明示的に未読として作成
        ]);

        return redirect()->back()->with('success', '予約を承認しました。');
    }

    //=====予約リクエスト却下（理由付き）=====//
    public function reject(Request $request, Reservation $reservation)
    {
        $reservation->update(['status' => 'cancelled']);

        // 通知作成
        $roomName = optional($reservation->room)->name ?? '教室';
        $date = $reservation->date ?? '指定日';
        $period = $reservation->period ?? '指定時限';
        $message = "予約が拒否されました。{$roomName} - {$date} {$period}時限目";

        Notification::create([
            'user_id' => $reservation->user_id,
            'message' => $message,
            'is_read' => false,
        ]);

        return redirect()->back()->with('success', '予約を却下しました。');
    }
}
