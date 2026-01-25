<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create(Request $request)
    {
        $selectedRoom = $request->room_id ? Room::find($request->room_id) : null;

        if (!$request->date) {
            return redirect()->route('user.home.index')->with('error', '日付が指定されていません。');
        }
        if (!$selectedRoom) {
            return redirect()->route('user.home.index')->with('error', '教室がみつかりません。');
        }

        $date = $request->date;
        $room = $selectedRoom;
        $period = (int)$request->period; // 数値型に変換

        // ▼▼▼ 追加：すでに埋まっている予約を取得（キャンセル以外） ▼▼▼
        $reservedPeriods = Reservation::where('room_id', $room->id)
            ->where('date', $date)
            ->where('status', '!=', 'cancelled')
            ->pluck('period')
            ->toArray(); // 配列にする (例: [1, 3])
        
        return view('user.reservation-form', compact('room', 'date', 'period', 'reservedPeriods'));
    }

    public function check(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date'    => 'required|date|after_or_equal:today',
            'periods' => 'required|array|min:1',
            'periods.*' => 'integer|min:1|max:6',
            'purpose' => 'required|string|max:100',
            'members' => 'nullable|string',
        ]);

        $selectedRoom = Room::find($validated['room_id']);

        // 重複チェック（ステータスがキャンセル以外すべてを対象）
        $existing = Reservation::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->whereIn('period', $validated['periods'])
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($existing) {
            return back()->withErrors(['periods' => '選択された時間帯の中に、すでに予約済みまたは申請中の枠が含まれています。'])
                         ->withInput();
        }

        $periods = $validated['periods'];
        sort($periods);

        $reservationData = [
            'room_id' => $validated['room_id'],
            'room_name' => $selectedRoom->name,
            'date'    => $validated['date'],
            'periods' => $periods,
            'period_display' => implode(', ', $periods),
            'purpose' => $validated['purpose'],
            'members' => $validated['members'],
            'user_name' => Auth::user()->name,
            'student_id' => Auth::user()->student_id,
        ];

        return view('user.reservation-confirm', compact('reservationData', 'selectedRoom'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date'    => 'required|date|after_or_equal:today',
            'periods' => 'required|array|min:1',
            'periods.*' => 'integer|min:1|max:6',
            'purpose' => 'required|string|max:500',
            'members' => 'nullable|string',
        ]);

        // トランザクション処理
        return DB::transaction(function () use ($validated) {
            
            // lockForUpdate() を使用して、同時実行時の重複を防ぐ
            $existing = Reservation::where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('period', $validated['periods'])
                ->where('status', '!=', 'cancelled')
                ->lockForUpdate()
                ->exists();

            if ($existing) {
                 return redirect()->route('user.reservations.create', [
                    'room_id' => $validated['room_id'],
                    'date' => $validated['date']
                ])->withErrors(['error' => 'タッチの差で予約が埋まってしまいました。']);
            }

            foreach ($validated['periods'] as $period) {
                Reservation::create([
                    'user_id' => Auth::id(),
                    'room_id' => $validated['room_id'],
                    'date'    => $validated['date'],
                    'period'  => $period,
                    'purpose' => $validated['purpose'],
                    'members' => $validated['members'],
                    'status'  => 'pending',
                ]);
            }

            return redirect()->route('user.home.index')
                ->with('success', '予約申請を送信しました。承認をお待ちください。');
        });
    }

    // index, show, cancel, history は変更なし
    public function index()
    {
        $reservations = Reservation::where('user_id', Auth::id())
            ->where('date', '>=', Carbon::today())
            ->with('room')
            ->orderBy('date')
            ->orderBy('period')
            ->get();
        return view('user.reservations-index', compact('reservations'));
    }

    public function show(Reservation $reservation)
    {
        if ($reservation->user_id !== Auth::id()) abort(403);
        return view('user.reservation-detail', compact('reservation'));
    }

    public function cancel(Reservation $reservation)
    {
        if ($reservation->user_id !== Auth::id()) abort(403);
        if (!in_array($reservation->status, ['pending', 'approved'])) {
            return back()->withErrors(['error' => 'この予約はキャンセルできません。']);
        }
        $reservation->update(['status' => 'cancelled']);
        return redirect()->route('user.reservations.index')->with('success', '予約をキャンセルしました。');
    }

    public function history()
    {
        $historyReservations = Reservation::where(function ($query) {
            $query->where('date', '<', Carbon::today())
                ->orWhereIn('status', ['completed', 'cancelled', 'rejected']);
        })
            ->where('user_id', Auth::id())
            ->with('room')
            ->orderBy('date', 'desc')
            ->paginate(10);
        return view('user.reservations-history', compact('historyReservations'));
    }
}