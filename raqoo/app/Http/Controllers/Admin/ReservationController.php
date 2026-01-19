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

    //===== 一括承認処理 =====//
    public function bulkApprove(Request $request)
    {
        $ids = explode(',', $request->input('ids'));
        $reservations = Reservation::with('room')->whereIn('id', $ids)->orderBy('period')->get();

        if ($reservations->isEmpty()) {
            return redirect()->back();
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
            
            $dateDisplay = $first->date instanceof Carbon 
                ? $first->date->format('Y-m-d') 
                : substr($first->date, 0, 10);

            Notification::create([
                'user_id' => $first->user_id,
                'message' => "予約が承認されました。{$first->room->name} - {$dateDisplay} {$periodDisplay}限",
                'is_read' => false
            ]);
        });

        return redirect()->back()->with('success', $reservations->count() . '件の予約を承認しました。');
    }

    //===== 一括却下処理 =====//
    public function bulkReject(Request $request)
    {
        $ids = explode(',', $request->input('ids'));
        $reservations = Reservation::with('room')->whereIn('id', $ids)->orderBy('period')->get();

        if ($reservations->isEmpty()) {
            return redirect()->back();
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
            
            $dateDisplay = $first->date instanceof Carbon 
                ? $first->date->format('Y-m-d') 
                : substr($first->date, 0, 10);

            Notification::create([
                'user_id' => $first->user_id,
                'message' => "予約が却下されました。{$first->room->name} - {$dateDisplay} {$periodDisplay}限",
                'is_read' => false
            ]);
        });

        return redirect()->back()->with('success', $reservations->count() . '件の予約を却下しました。');
    }

    //===== (既存) 単体承認 =====//
    public function approve(Reservation $reservation)
    {
        $reservation->update(['status' => 'reserved']);
        RoomStatus::updateOrCreate(
            ['room_id' => $reservation->room_id, 'date' => $reservation->date, 'period' => $reservation->period],
            ['status' => 'reserved']
        );
        $dateDisplay = $reservation->date instanceof Carbon ? $reservation->date->format('Y-m-d') : substr($reservation->date, 0, 10);
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
        $reservation->update(['status' => 'cancelled']);
        $dateDisplay = $reservation->date instanceof Carbon ? $reservation->date->format('Y-m-d') : substr($reservation->date, 0, 10);
        Notification::create([
            'user_id' => $reservation->user_id,
            'message' => "予約が却下されました。{$reservation->room->name} - {$dateDisplay} {$reservation->period}限",
            'is_read' => false,
        ]);
        return redirect()->back()->with('success', '予約を却下しました。');
    }

    //===== 予約リクエスト数取得（修正版）=====//
    public function count()
    {
        // 1. まず全ての承認待ちデータを取得（並び順をダッシュボードと同じにする）
        $rawReservations = Reservation::where('status', 'pending')
            ->orderBy('date')
            ->orderBy('room_id')
            ->orderBy('period')
            ->get();

        // 2. 連続データを1件としてカウントする
        $groupCount = 0;
        $temp = null;

        foreach ($rawReservations as $res) {
            $resDate = $res->date instanceof Carbon ? $res->date->format('Y-m-d') : substr($res->date, 0, 10);

            if ($temp) {
                // 直前のデータと「同じ人・同じ日・同じ教室・連続した時限」なら、同じグループ
                if (
                    $temp['user_id'] == $res->user_id &&
                    $temp['date'] === $resDate &&
                    $temp['room_id'] == $res->room_id &&
                    $temp['last_period'] + 1 === (int)$res->period
                ) {
                    // 連続しているのでカウントは増やさず、次の比較用に時限を更新
                    $temp['last_period'] = (int)$res->period;
                    continue;
                }
            }

            // 新しいグループの開始（ここでカウントアップ）
            $groupCount++;
            $temp = [
                'user_id' => $res->user_id,
                'room_id' => $res->room_id,
                'date' => $resDate,
                'last_period' => (int)$res->period,
            ];
        }

        return response()->json(['count' => $groupCount]);
    }
}