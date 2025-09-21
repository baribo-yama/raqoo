<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // 予約申請フォーム表示
    public function create(Request $request)
    {
        $room = Room::findOrFail($request->room_id);
        $date = $request->date;
        $period = $request->period;

        return view('user.reservation-form', compact('room', 'date', 'period'));
    }

    // 予約確認画面表示
    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date|after_or_equal:today',
            'period' => 'required|integer|min:1|max:6',
            'purpose' => 'required|string|max:100',
            'members' => 'nullable|string',
        ]);

        $room = Room::findOrFail($validated['room_id']);
        
        // メンバー数計算（代表者 + メンバー）
        $memberCount = 1; // 代表者
        if ($validated['members']) {
            $memberCount += count(array_filter(explode("\n", $validated['members'])));
        }

        $data = [
            'room' => $room,
            'date' => $validated['date'],
            'period' => $validated['period'],
            'purpose' => $validated['purpose'],
            'members' => $validated['members'],
        ];

        return view('user.reservation-confirm', compact('data', 'memberCount'));
    }

    // 予約申請保存
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date|after_or_equal:today',
            'period' => 'required|integer|min:1|max:6',
            'purpose' => 'required|string|max:100',
            'members' => 'nullable|string',
        ]);

        // 重複予約チェック
        $existing = Reservation::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->where('period', $validated['period'])
            ->whereIn('status', ['pending', 'approved', 'in_use'])
            ->exists();

        if ($existing) {
            return back()->withErrors(['error' => 'この時間帯は既に予約されています。']);
        }

        Reservation::create([
            'user_id' => Auth::id(),
            'room_id' => $validated['room_id'],
            'date' => $validated['date'],
            'period' => $validated['period'],
            'purpose' => $validated['purpose'],
            'members' => $validated['members'],
            'status' => 'pending',
        ]);

        return redirect()->route('user.reservations.index')
            ->with('success', '予約申請を送信しました。承認をお待ちください。');
    }

    // 予約一覧表示
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

    // 予約詳細表示
    public function show(Reservation $reservation)
    {
        // 自分の予約のみ表示可能
        if ($reservation->user_id !== Auth::id()) {
            abort(403);
        }

        return view('user.reservation-detail', compact('reservation'));
    }

    // 予約キャンセル
    public function cancel(Reservation $reservation)
    {
        // 自分の予約のみキャンセル可能
        if ($reservation->user_id !== Auth::id()) {
            abort(403);
        }

        // キャンセル可能な状態チェック
        if (!in_array($reservation->status, ['pending', 'approved'])) {
            return back()->withErrors(['error' => 'この予約はキャンセルできません。']);
        }

        $reservation->update(['status' => 'cancelled']);

        return redirect()->route('user.reservations.index')
            ->with('success', '予約をキャンセルしました。');
    }

    // 利用履歴表示
    public function history()
    {
        $historyReservations = Reservation::where('user_id', Auth::id())
            ->where('date', '<', Carbon::today())
            ->orWhere(function($query) {
                $query->where('user_id', Auth::id())
                      ->whereIn('status', ['completed', 'cancelled', 'rejected']);
            })
            ->with('room')
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('user.reservations-history', compact('historyReservations'));
    }
}