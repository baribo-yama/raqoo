<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ReservationApproved;
use App\Mail\ReservationRejected;
use App\Models\Reservation;
use App\Models\RoomStatus;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;


class ReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    //===== 予約一覧（管理者） =====//
    public function index()
    {
        $reservations = Reservation::with(['user', 'room'])
            ->where('status', 'pending')
            ->orderBy('date')
            ->orderBy('room_id')
            ->orderBy('period')
            ->get();

        return view('admin.reservations-index', compact('reservations'));
    }

    // 日付フォーマット用のヘルパーメソッド
    private function formatDate($date)
    {
        return $date instanceof Carbon ? $date->format('Y-m-d') : substr($date, 0, 10);
    }

    //===== 一括承認処理 =====//
    public function bulkApprove(Request $request)
    {
        // ▼▼▼ 修正：バリデーションを追加 ▼▼▼
        $validated = $request->validate([
            'ids' => 'required|string',
        ]);
        $ids = explode(',', $validated['ids']);

        // 'pending' 状態のものだけを対象にする
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
        // ▼▼▼ 修正：バリデーションを追加 ▼▼▼
        $validated = $request->validate([
            'ids' => 'required|string',
        ]);
        $ids = explode(',', $validated['ids']);

        // 'pending' のみ対象
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

        // 予約承認メール送信
        Mail::to($reservation->user->email)->send(new ReservationApproved($reservation));

        return redirect()->back()->with('success', '予約を承認しました。');
    }

    //===== (既存) 単体却下 =====//
    public function reject(Request $request, Reservation $reservation)
    {
        // ▼▼▼ 修正：インデントの修正（余分な空白削除） ▼▼▼
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

        // 予約却下メール送信
        Mail::to($reservation->user->email)->send(new ReservationRejected($reservation));

        return redirect()->back()->with('success', '予約を却下しました。');
    }

    //===== 予約リクエスト数取得 =====//
    public function count()
    {
        $rawReservations = Reservation::where('status', 'pending')
            ->orderBy('date')
            ->orderBy('room_id')
            // ▼▼▼ 修正：user_idの順序指定を追加してグループ化を確実に ▼▼▼
            ->orderBy('user_id')
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
                    $temp['purpose'] === $res->purpose &&
                    $temp['members'] === $res->members &&
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
