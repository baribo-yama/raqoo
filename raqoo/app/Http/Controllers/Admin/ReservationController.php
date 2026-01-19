<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\RoomStatus;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    // 日付フォーマット用のヘルパーメソッド
    private function formatDate($date)
    {
        return $date instanceof Carbon ? $date->format('Y-m-d') : substr($date, 0, 10);
    }

    //===== 一括承認処理 =====//
    public function bulkApprove(Request $request)
    {
        $ids = explode(',', $request->input('ids'));
        
        // 修正: 'pending' 状態のものだけを対象にする（二重承認防止）
        $reservations = Reservation::with('room')
            ->whereIn('id', $ids)
            ->where('status', 'pending')
            ->orderBy('period')
            ->get();

        if ($reservations->isEmpty()) {
            // 修正: 何も処理しなかった場合のメッセージを追加
            return redirect()->back()->with('error', '承認待ちの予約が見つからないか、既に処理されています。');
        }

        DB::transaction(function () use ($reservations) {
            foreach ($reservations as $reservation) {
                $reservation->update(['status' => 'reserved']);
                RoomStatus::updateOrCreate(
                    [
                        'room_id' => $reservation->room_id,
                        'date' => $reservation->date,
                        'period' => $reservation->period,
                    ],
                    ['status' => 'reserved']
                );
            }

            $first = $reservations->first();
            $last = $reservations->last();
            
            $periodDisplay = ($first->period == $last->period) 
                ? "{$first->period}" 
                : "{$first->period}〜{$last->period}";
            
            $dateDisplay = $this->formatDate($first->date);

            Notification::create([
                'user_id' => $first->user_id,
                'message' => "予約（全{$reservations->count()}コマ）が承認されました。{$first->room->name} - {$dateDisplay} {$periodDisplay}限",
                'is_read' => false
            ]);
        });

        return redirect()->back()->with('success', $reservations->count() . '件の予約を承認しました。');
    }

    //===== 一括却下処理 =====//
    public function bulkReject(Request $request)
    {
        $ids = explode(',', $request->input('ids'));
        
        // 修正: 'pending' のみ対象
        $reservations = Reservation::with('room')
            ->whereIn('id', $ids)
            ->where('status', 'pending')
            ->orderBy('period')
            ->get();

        if ($reservations->isEmpty()) {
            return redirect()->back()->with('error', '承認待ちの予約が見つからないか、既に処理されています。');
        }

        DB::transaction(function () use ($reservations) {
            foreach ($reservations as $reservation) {
                $reservation->update(['status' => 'cancelled']);
            }

            $first = $reservations->first();
            $last = $reservations->last();
            
            $periodDisplay = ($first->period == $last->period) 
                ? "{$first->period}" 
                : "{$first->period}〜{$last->period}";
            
            $dateDisplay = $this->formatDate($first->date);

            Notification::create([
                'user_id' => $first->user_id,
                'message' => "予約（全{$reservations->count()}コマ）が却下されました。{$first->room->name} - {$dateDisplay} {$periodDisplay}限",
                'is_read' => false
            ]);
        });

        return redirect()->back()->with('success', $reservations->count() . '件の予約を却下しました。');
    }

    //===== (既存) 単体承認 =====//
    public function approve(Reservation $reservation)
    {
        if ($reservation->status !== 'pending') {
             return redirect()->back()->with('error', 'この予約は承認待ちではありません。');
        }

        $reservation->update(['status' => 'reserved']);
        RoomStatus::updateOrCreate(
            ['room_id' => $reservation->room_id, 'date' => $reservation->date, 'period' => $reservation->period],
            ['status' => 'reserved']
        );
        
        $dateDisplay = $this->formatDate($reservation->date);
        
        Notification::create([
            'user_id' => $reservation->user_id,
            'message' => "予約が承認されました。{$reservation->room->name} - {$dateDisplay} {$reservation->period}限",
            'is_read' => false
        ]);
        return redirect()->back()->with('success', '予約を承認しました。');
    }

    //===== (既存) 単体却下 =====//
    public function reject(Request $request, Reservation $reservation)
    {
        if ($reservation->status !== 'pending') {
             return redirect()->back()->with('error', 'この予約は承認待ちではありません。');
        }
        
        $reservation->update(['status' => 'cancelled']);
        
        $dateDisplay = $this->formatDate($reservation->date);

        Notification::create([
            'user_id' => $reservation->user_id,
            'message' => "予約が却下されました。{$reservation->room->name} - {$dateDisplay} {$reservation->period}限",
            'is_read' => false,
        ]);
        return redirect()->back()->with('success', '予約を却下しました。');
    }

    //===== 予約リクエスト数取得 =====//
    public function count()
    {
        $rawReservations = Reservation::where('status', 'pending')
            ->orderBy('date')
            ->orderBy('room_id')
            ->orderBy('period')
            ->get();

        $groupCount = 0;
        $temp = null;

        foreach ($rawReservations as $res) {
            $resDate = $this->formatDate($res->date);

            if ($temp) {
                // DashBoardControllerと同じロジックにする（目的とメンバーも一致確認）
                if (
                    $temp['user_id'] == $res->user_id &&
                    $temp['date'] === $resDate &&
                    $temp['room_id'] == $res->room_id &&
                    $temp['purpose'] === $res->purpose && // 追加: 目的も一致
                    $temp['members'] === $res->members && // 追加: メンバーも一致
                    $temp['last_period'] + 1 === (int)$res->period
                ) {
                    $temp['last_period'] = (int)$res->period;
                    continue;
                }
            }

            $groupCount++;
            $temp = [
                'user_id' => $res->user_id,
                'room_id' => $res->room_id,
                'date' => $resDate,
                'purpose' => $res->purpose,
                'members' => $res->members,
                'last_period' => (int)$res->period,
            ];
        }

        return response()->json(['count' => $groupCount]);
    }
}